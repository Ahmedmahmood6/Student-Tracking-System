<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentReportsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Report::query()
                    ->with('student')
                    ->latest('generated_at')
                    ->limit(5)
            )
            ->heading('Recent Weekly Reports')
            ->emptyStateHeading('No weekly reports generated yet')
            ->emptyStateIcon(Heroicon::OutlinedDocumentChartBar)
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->weight('bold'),

                TextColumn::make('period')
                    ->label('Period')
                    ->state(fn (Report $record): string => $record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y')),

                TextColumn::make('attendance_rate')
                    ->label('Attendance')
                    ->badge()
                    ->color('success')
                    ->state(fn (Report $record): string => ($record->snapshot['attendance_summary']['attendance_percentage'] ?? 'N/A').'%'),

                TextColumn::make('academic_avg')
                    ->label('Assessment Avg')
                    ->badge()
                    ->color('info')
                    ->state(fn (Report $record): string => ($record->snapshot['assessment_summary']['overall_average_percentage'] ?? 'N/A').'%'),

                IconColumn::make('is_valid')
                    ->label('Active Link')
                    ->boolean()
                    ->state(fn (Report $record): bool => $record->isValid()),
            ])
            ->recordActions([
                Action::make('viewStudent')
                    ->label('Student Profile')
                    ->icon(Heroicon::OutlinedUser)
                    ->url(fn (Report $record): string => $record->student ? StudentResource::getUrl('view', ['record' => $record->student]) : '#'),
            ]);
    }
}
