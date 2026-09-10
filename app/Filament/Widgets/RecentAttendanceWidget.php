<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\Attendance;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Attendance::query()
                    ->with(['classSession.student', 'classSession.subject'])
                    ->latest('updated_at')
                    ->limit(5)
            )
            ->heading('Recent Attendance Records')
            ->emptyStateHeading('No attendance records yet')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->columns([
                TextColumn::make('classSession.student.name')
                    ->label('Student')
                    ->weight('bold'),

                TextColumn::make('classSession.subject.name')
                    ->label('Subject')
                    ->badge()
                    ->color('info'),

                TextColumn::make('classSession.date')
                    ->label('Class Date')
                    ->date('M d, Y'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AttendanceStatus|string|null $state): string => match ($state instanceof AttendanceStatus ? $state : AttendanceStatus::tryFrom((string) $state)) {
                        AttendanceStatus::Present => 'success',
                        AttendanceStatus::Late => 'warning',
                        AttendanceStatus::Absent => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('viewClass')
                    ->label('Class')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Attendance $record): string => $record->classSession ? ClassSessionResource::getUrl('view', ['record' => $record->classSession]) : '#'),
            ]);
    }
}
