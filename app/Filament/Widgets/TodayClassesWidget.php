<?php

namespace App\Filament\Widgets;

use App\Enums\ClassSessionStatus;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\ClassSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayClassesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassSession::query()
                    ->whereDate('date', now()->toDateString())
                    ->with(['student', 'subject', 'attendance'])
                    ->orderBy('start_time')
            )
            ->heading('حصص اليوم')
            ->emptyStateHeading('لا توجد حصص مجدولة لليوم')
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays)
            ->columns([
                TextColumn::make('time')
                    ->label('الوقت')
                    ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('المادة')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                        ClassSessionStatus::Scheduled => 'warning',
                        ClassSessionStatus::Completed => 'success',
                        ClassSessionStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('attendance.status')
                    ->label('الحضور')
                    ->badge()
                    ->placeholder('لم يُسجل')
                    ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
                    ->label('تقييم الأداء')
                    ->state(fn (ClassSession $record): string => $record->rating ? str_repeat('⭐', $record->rating) : '—')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('rateSession')
                    ->label('تقييم الأداء')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->modalHeading('تقييم أداء ومستوى الطالب في الحصة')
                    ->form([
                        Select::make('rating')
                            ->label('تقييم الحصة (من 5 نجوم)')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (5/5) ممتاز جداً',
                                4 => '⭐⭐⭐⭐ (4/5) جيد جداً',
                                3 => '⭐⭐⭐ (3/5) جيد',
                                2 => '⭐⭐ (2/5) مقبول',
                                1 => '⭐ (1/5) يحتاج لمتابعة',
                            ])
                            ->default(fn (ClassSession $record) => $record->rating)
                            ->nullable()
                            ->placeholder('اختر التقييم (اختياري)'),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->update([
                            'rating' => $data['rating'] ?? null,
                        ]);
                    }),

                Action::make('manage')
                    ->label('فتح الحصة')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ClassSession $record): string => ClassSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
