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

class UpcomingClassesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassSession::query()
                    ->whereDate('date', '>=', now()->toDateString())
                    ->with(['student', 'subject', 'attendance'])
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->limit(6)
            )
            ->heading('Upcoming & Today\'s Classes')
            ->emptyStateHeading('No upcoming classes found')
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays)
            ->columns([
                TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (ClassSession $record): string => $record->date?->isToday() ? 'primary' : 'gray'),

                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge()
                    ->color('info'),

                TextColumn::make('time')
                    ->label('Time')
                    ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                        ClassSessionStatus::Scheduled => 'warning',
                        ClassSessionStatus::Completed => 'success',
                        ClassSessionStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('attendance.status')
                    ->label('Attendance')
                    ->badge()
                    ->placeholder('Not Recorded')
                    ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Open Class')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ClassSession $record): string => ClassSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
