<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Report;
use App\Models\Student;
use App\Services\WeeklyReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Weekly Reports';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('week_start')->required(),
                DatePicker::make('week_end')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('week_start')
            ->defaultSort('generated_at', 'desc')
            ->columns([
                TextColumn::make('period')
                    ->label('Report Period')
                    ->state(fn (Report $record): string => $record->week_start?->format('M d, Y').' - '.$record->week_end?->format('M d, Y'))
                    ->weight('bold'),

                TextColumn::make('generated_at')
                    ->label('Generated At')
                    ->dateTime('M d, Y H:i'),

                TextColumn::make('attendance_rate')
                    ->label('Attendance')
                    ->badge()
                    ->color('success')
                    ->state(fn (Report $record): string => ($record->snapshot['attendance_summary']['attendance_percentage'] ?? '0').'%'),

                TextColumn::make('academic_avg')
                    ->label('Assessment Avg')
                    ->badge()
                    ->color('info')
                    ->state(fn (Report $record): string => ($record->snapshot['assessment_summary']['overall_average_percentage'] ?? '0').'%'),

                IconColumn::make('status')
                    ->label('Link Active')
                    ->boolean()
                    ->state(fn (Report $record): bool => $record->isValid()),
            ])
            ->headerActions([
                Action::make('generateReport')
                    ->label('Generate Weekly Report')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('week_start')
                                ->label('Week Start')
                                ->default(now()->startOfWeek())
                                ->required(),

                            DatePicker::make('week_end')
                                ->label('Week End')
                                ->default(now()->endOfWeek())
                                ->required()
                                ->afterOrEqual('week_start'),
                        ]),
                    ])
                    ->action(function (array $data, WeeklyReportService $service): void {
                        /** @var Student $student */
                        $student = $this->getOwnerRecord();
                        $result = $service->generate($student, $data['week_start'], $data['week_end']);
                        $report = $result['report'];
                        $plainToken = $result['plain_token'];
                        $reportUrl = route('parent.report.show', ['token' => $plainToken]);

                        Notification::make()
                            ->title('Weekly Report Generated Successfully')
                            ->body("Permanent public report link created: {$reportUrl}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('whatsappShare')
                    ->label('Send via WhatsApp')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Share Report on WhatsApp')
                    ->modalDescription('This will generate a fresh secure permanent link and open WhatsApp with the parent.')
                    ->action(function (Report $record, WeeklyReportService $service) {
                        /** @var Student $student */
                        $student = $this->getOwnerRecord();
                        $token = $service->regenerateToken($record);
                        $url = route('parent.report.show', ['token' => $token]);

                        $parentPhone = preg_replace('/[^0-9]/', '', (string) ($student->parent_phone ?? $student->phone));
                        $parentName = $student->parent_name ?: 'Parent/Guardian';
                        $studentName = $student->name;
                        $period = $record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y');
                        $attRate = $record->snapshot['attendance_summary']['attendance_percentage'] ?? 0;
                        $acadRate = $record->snapshot['assessment_summary']['overall_average_percentage'] ?? 0;

                        $text = "Dear {$parentName},\n\nHere is the weekly progress report for {$studentName} ({$period}):\n- Attendance Rate: {$attRate}%\n- Academic Average: {$acadRate}%\n\nYou can view the full detailed report here:\n{$url}\n\nBest regards,\nStudent Tracking & Reporting";

                        $encoded = urlencode($text);
                        $waUrl = ! empty($parentPhone)
                            ? "https://wa.me/{$parentPhone}?text={$encoded}"
                            : "https://wa.me/?text={$encoded}";

                        return redirect()->away($waUrl);
                    }),

                Action::make('regenerateLink')
                    ->label('Get / Regenerate Link')
                    ->icon(Heroicon::OutlinedLink)
                    ->color('info')
                    ->action(function (Report $record, WeeklyReportService $service): void {
                        $token = $service->regenerateToken($record);
                        $url = route('parent.report.show', ['token' => $token]);

                        Notification::make()
                            ->title('New Report Link Generated')
                            ->body("Active Report Link: {$url}")
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                Action::make('viewSnapshot')
                    ->label('Snapshot')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Report $record) => 'Weekly Report Snapshot: '.$record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y'))
                    ->modalContent(function (Report $record) {
                        $snap = $record->snapshot ?? [];
                        $att = $snap['attendance_summary'] ?? [];
                        $asmt = $snap['assessment_summary'] ?? [];
                        $sessions = $snap['sessions'] ?? [];

                        $html = '<div class="space-y-4 text-sm">';
                        $html .= '<div class="grid grid-cols-2 gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                        $html .= '<div><strong>Attendance:</strong> '.($att['attendance_percentage'] ?? 0).'% ('.($att['present'] ?? 0).' Present, '.($att['late'] ?? 0).' Late, '.($att['absent'] ?? 0).' Absent)</div>';
                        $html .= '<div><strong>Assessment Avg:</strong> '.($asmt['overall_average_percentage'] ?? 0).'% ('.($asmt['total_assessments'] ?? 0).' Graded Tasks)</div>';
                        $html .= '</div>';

                        $html .= '<h4 class="font-bold mt-3">Sessions in Period:</h4>';
                        $html .= '<ul class="divide-y divide-gray-200 dark:divide-gray-700">';
                        foreach ($sessions as $s) {
                            $html .= '<li class="py-2 flex justify-between items-center">';
                            $html .= '<span><strong>'.htmlspecialchars($s['subject_name'] ?? 'Class').'</strong> ('.htmlspecialchars($s['date'] ?? '').' '.htmlspecialchars($s['start_time'] ?? '').')</span>';
                            $attStatus = $s['attendance']['status'] ?? 'None';
                            $html .= '<span class="px-2 py-0.5 rounded text-xs bg-primary-100 text-primary-800 dark:bg-primary-900">Attendance: '.htmlspecialchars(ucfirst($attStatus)).'</span>';
                            $html .= '</li>';
                        }
                        $html .= '</ul>';
                        $html .= '</div>';

                        return new HtmlString($html);
                    }),

                Action::make('revokeToken')
                    ->label('Revoke')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record): bool => $record->isValid())
                    ->action(function (Report $record, WeeklyReportService $service): void {
                        $service->revokeToken($record);
                        Notification::make()
                            ->title('Report Link Revoked')
                            ->danger()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Report')
                    ->modalDescription('Are you sure you want to permanently delete this report? This action cannot be undone.')
                    ->action(fn (Report $record) => $record->delete()),
            ]);
    }
}
