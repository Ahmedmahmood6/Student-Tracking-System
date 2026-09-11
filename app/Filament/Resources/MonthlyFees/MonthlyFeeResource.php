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

    protected static ?string $modelLabel = 'مصروف شهري';

    protected static ?string $pluralModelLabel = 'المصروفات الشهرية';

    protected static ?string $navigationLabel = 'المصروفات الشهرية';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'الإدارة المالية';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل المصروفات')
                    ->schema([
                        Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Grid::make(2)->schema([
                            Select::make('month')
                                ->label('الشهر')
                                ->options([
                                    1 => 'يناير (01)',
                                    2 => 'فبراير (02)',
                                    3 => 'مارس (03)',
                                    4 => 'أبريل (04)',
                                    5 => 'مايو (05)',
                                    6 => 'يونيو (06)',
                                    7 => 'يوليو (07)',
                                    8 => 'أغسطس (08)',
                                    9 => 'سبتمبر (09)',
                                    10 => 'أكتوبر (10)',
                                    11 => 'نوفمبر (11)',
                                    12 => 'ديسمبر (12)',
                                ])
                                ->default(now()->month)
                                ->required(),

                            TextInput::make('year')
                                ->label('السنة')
                                ->numeric()
                                ->default(now()->year)
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('amount')
                                ->label('المبلغ ($)')
                                ->numeric()
                                ->prefix('$')
                                ->required(),

                            Select::make('status')
                                ->label('حالة السداد')
                                ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()]))
                                ->default(MonthlyFeeStatus::Unpaid->value)
                                ->required(),
                        ]),

                        DateTimePicker::make('paid_at')
                            ->label('تاريخ ووقت السداد'),

                        Textarea::make('notes')
                            ->label('ملاحظات السداد / المعاملة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('period')
                    ->label('الشهر / السنة')
                    ->state(fn (MonthlyFee $record): string => ($months[$record->month] ?? $record->month).' '.$record->year)
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (MonthlyFeeStatus|string|null $state): string => match ($state instanceof MonthlyFeeStatus ? $state : MonthlyFeeStatus::tryFrom((string) $state)) {
                        MonthlyFeeStatus::Paid => 'success',
                        MonthlyFeeStatus::PartiallyPaid => 'warning',
                        MonthlyFeeStatus::Unpaid => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('paid_at')
                    ->label('تاريخ السداد')
                    ->dateTime('M d, Y')
                    ->placeholder('لم يتم السداد'),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('تصفية حسب الطالب')
                    ->options(fn () => Student::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('تصفية حسب الحالة')
                    ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()])),

                SelectFilter::make('month')
                    ->label('تصفية حسب الشهر')
                    ->options([
                        1 => 'يناير',
                        2 => 'فبراير',
                        3 => 'مارس',
                        4 => 'أبريل',
                        5 => 'مايو',
                        6 => 'يونيو',
                        7 => 'يوليو',
                        8 => 'أغسطس',
                        9 => 'سبتمبر',
                        10 => 'أكتوبر',
                        11 => 'نوفمبر',
                        12 => 'ديسمبر',
                    ]),
            ])
            ->recordActions([
                Action::make('markAsPaid')
                    ->label('تم السداد')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (MonthlyFee $record) => $record->status !== MonthlyFeeStatus::Paid)
                    ->action(fn (MonthlyFee $record) => $record->update([
                        'status' => MonthlyFeeStatus::Paid,
                        'paid_at' => now(),
                    ])),

                Action::make('markAsUnpaid')
                    ->label('إلغاء السداد')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (MonthlyFee $record) => $record->status === MonthlyFeeStatus::Paid)
                    ->action(fn (MonthlyFee $record) => $record->update([
                        'status' => MonthlyFeeStatus::Unpaid,
                        'paid_at' => null,
                    ])),

                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
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
