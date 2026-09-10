<?php

namespace App\Filament\Resources\MonthlyFees;

use App\Enums\MonthlyFeeStatus;
use App\Filament\Resources\MonthlyFees\Pages\CreateMonthlyFee;
use App\Filament\Resources\MonthlyFees\Pages\EditMonthlyFee;
use App\Filament\Resources\MonthlyFees\Pages\ListMonthlyFees;
use App\Models\MonthlyFee;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MonthlyFeeResource extends Resource
{
    protected static ?string $model = MonthlyFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Financial Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fee Details')
                    ->schema([
                        Select::make('student_id')
                            ->label('Student')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Grid::make(2)->schema([
                            Select::make('month')
                                ->label('Month')
                                ->options([
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December',
                                ])
                                ->default(now()->month)
                                ->required(),

                            TextInput::make('year')
                                ->label('Year')
                                ->numeric()
                                ->default(now()->year)
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('amount')
                                ->label('Amount ($)')
                                ->numeric()
                                ->prefix('$')
                                ->required(),

                            Select::make('status')
                                ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()]))
                                ->default(MonthlyFeeStatus::Unpaid->value)
                                ->required(),
                        ]),

                        DateTimePicker::make('paid_at')
                            ->label('Paid At Date & Time'),

                        Textarea::make('notes')
                            ->label('Payment / Transaction Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('period')
                    ->label('Month / Year')
                    ->state(fn (MonthlyFee $record): string => date('F', mktime(0, 0, 0, $record->month, 1)).' '.$record->year)
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MonthlyFeeStatus|string|null $state): string => match ($state instanceof MonthlyFeeStatus ? $state : MonthlyFeeStatus::tryFrom((string) $state)) {
                        MonthlyFeeStatus::Paid => 'success',
                        MonthlyFeeStatus::PartiallyPaid => 'warning',
                        MonthlyFeeStatus::Unpaid => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('paid_at')
                    ->dateTime('M d, Y')
                    ->placeholder('Not Paid'),

                TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('Student')
                    ->options(fn () => Student::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()])),

                SelectFilter::make('month')
                    ->options([
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December',
                    ]),
            ])
            ->recordActions([
                Action::make('markAsPaid')
                    ->label('Mark Paid')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (MonthlyFee $record) => $record->status !== MonthlyFeeStatus::Paid)
                    ->action(fn (MonthlyFee $record) => $record->update([
                        'status' => MonthlyFeeStatus::Paid,
                        'paid_at' => now(),
                    ])),

                Action::make('markAsUnpaid')
                    ->label('Mark Unpaid')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (MonthlyFee $record) => $record->status === MonthlyFeeStatus::Paid)
                    ->action(fn (MonthlyFee $record) => $record->update([
                        'status' => MonthlyFeeStatus::Unpaid,
                        'paid_at' => null,
                    ])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlyFees::route('/'),
            'create' => CreateMonthlyFee::route('/create'),
            'edit' => EditMonthlyFee::route('/{record}/edit'),
        ];
    }
}
