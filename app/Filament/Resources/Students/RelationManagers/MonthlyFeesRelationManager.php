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

    protected static ?string $title = 'المصروفات الشهرية وسجل السداد';

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
                        ->label('المبلغ')
                        ->numeric()
                        ->prefix('$')
                        ->required(),

                    Select::make('status')
                        ->label('حالة السداد')
                        ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()]))
                        ->default(MonthlyFeeStatus::Unpaid->value)
                        ->required(),
                ]),

                Textarea::make('notes')
                    ->label('ملاحظات السداد / الإيصال')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $table
            ->recordTitleAttribute('month')
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('period')
                    ->label('الشهر / السنة')
                    ->state(fn (MonthlyFee $record): string => ($months[$record->month] ?? $record->month).' '.$record->year)
                    ->weight('bold'),

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
                    ->limit(25)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('تصفية حسب الحالة')
                    ->options(collect(MonthlyFeeStatus::cases())->mapWithKeys(fn (MonthlyFeeStatus $status) => [$status->value => $status->label()])),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة استحقاق شهري'),
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
            ]);
    }
}
