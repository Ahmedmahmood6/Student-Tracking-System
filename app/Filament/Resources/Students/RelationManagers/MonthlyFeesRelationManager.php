<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\MonthlyFeeStatus;
use App\Models\MonthlyFee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MonthlyFeesRelationManager extends RelationManager
{
    protected static string $relationship = 'monthlyFees';

    protected static ?string $title = 'Monthly Fees & Payments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('month')
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('period')
                    ->label('Month / Year')
                    ->state(fn (MonthlyFee $record): string => date('F', mktime(0, 0, 0, $record->month, 1)).' '.$record->year)
                    ->weight('bold'),

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
                    ->limit(25)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()])),
            ])
            ->headerActions([
                CreateAction::make(),
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
            ]);
    }
}
