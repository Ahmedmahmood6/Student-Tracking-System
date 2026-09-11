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

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Report::query()
                    ->with('student')
                    ->latest('generated_at')
                    ->limit(5)
            )
            ->heading('أحدث التقارير الأسبوعية')
            ->emptyStateHeading('لم يتم إنشاء تقارير أسبوعية بعد')
            ->emptyStateIcon(Heroicon::OutlinedDocumentChartBar)
            ->columns([
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->weight('bold'),

                TextColumn::make('period')
                    ->label('فترة التقرير')
                    ->state(fn (Report $record): string => $record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y')),

                TextColumn::make('attendance_rate')
                    ->label('نسبة الحضور')
                    ->badge()
                    ->color('success')
                    ->state(fn (Report $record): string => ($record->snapshot['attendance_summary']['attendance_percentage'] ?? 'N/A').'%'),

                TextColumn::make('academic_avg')
                    ->label('متوسط التقييمات')
                    ->badge()
                    ->color('info')
                    ->state(fn (Report $record): string => ($record->snapshot['assessment_summary']['overall_average_percentage'] ?? 'N/A').'%'),

                IconColumn::make('is_valid')
                    ->label('الرابط فعال')
                    ->boolean()
                    ->state(fn (Report $record): bool => $record->isValid()),
            ])
            ->recordActions([
                Action::make('viewStudent')
                    ->label('الملف التعريفي')
                    ->icon(Heroicon::OutlinedUser)
                    ->url(fn (Report $record): string => $record->student ? StudentResource::getUrl('view', ['record' => $record->student]) : '#'),
            ]);
    }
}
