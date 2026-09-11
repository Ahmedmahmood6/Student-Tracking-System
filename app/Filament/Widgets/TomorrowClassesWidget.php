<?php

namespace App\Filament\Widgets;

use App\Enums\ClassSessionStatus;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\ClassSession;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TomorrowClassesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassSession::query()
                    ->whereDate('date', now()->addDay()->toDateString())
                    ->with(['student', 'subject', 'attendance'])
                    ->orderBy('start_time')
            )
            ->heading('حصص غداً')
            ->emptyStateHeading('لا توجد حصص مجدولة للغد')
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
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('فتح الحصة')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ClassSession $record): string => ClassSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
