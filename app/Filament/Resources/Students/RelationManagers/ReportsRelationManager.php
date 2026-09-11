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

    protected static ?string $title = 'التقارير الأسبوعية ومشاركتها';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('week_start')->label('بداية الأسبوع')->required(),
                DatePicker::make('week_end')->label('نهاية الأسبوع')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('week_start')
            ->defaultSort('generated_at', 'desc')
            ->columns([
                TextColumn::make('period')
                    ->label('فترة التقرير')
                    ->state(fn (Report $record): string => $record->week_start?->format('M d, Y').' - '.$record->week_end?->format('M d, Y'))
                    ->weight('bold'),

                TextColumn::make('generated_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('M d, Y H:i'),

                TextColumn::make('attendance_rate')
                    ->label('نسبة الحضور')
                    ->badge()
                    ->color('success')
                    ->state(fn (Report $record): string => ($record->snapshot['attendance_summary']['attendance_percentage'] ?? '0').'%'),

                TextColumn::make('academic_avg')
                    ->label('متوسط التقييمات')
                    ->badge()
                    ->color('info')
                    ->state(fn (Report $record): string => ($record->snapshot['assessment_summary']['overall_average_percentage'] ?? '0').'%'),

                IconColumn::make('status')
                    ->label('الرابط نشط')
                    ->boolean()
                    ->state(fn (Report $record): bool => $record->isValid()),
            ])
            ->headerActions([
                Action::make('generateReport')
                    ->label('إنشاء تقرير أسبوعي')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('week_start')
                                ->label('بداية الأسبوع')
                                ->default(now()->startOfWeek())
                                ->required(),

                            DatePicker::make('week_end')
                                ->label('نهاية الأسبوع')
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
                            ->title('تم إنشاء التقرير الأسبوعي بنجاح')
                            ->body("رابط التقرير المباشر لولي الأمر: {$reportUrl}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('whatsappShare')
                    ->label('واتساب')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('مشاركة التقرير عبر واتساب')
                    ->modalDescription('سيتم إنشاء رابط آمن وفتح واتساب لمراسلة ولي الأمر مباشرة.')
                    ->action(function (Report $record, WeeklyReportService $service) {
                        /** @var Student $student */
                        $student = $this->getOwnerRecord();
                        $token = $service->regenerateToken($record);
                        $url = route('parent.report.show', ['token' => $token]);

                        $parentPhone = preg_replace('/[^0-9]/', '', (string) ($student->parent_phone ?? $student->phone));
                        $parentName = $student->parent_name ?: 'ولي الأمر المحترم';
                        $studentName = $student->name;
                        $period = $record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y');
                        $attRate = $record->snapshot['attendance_summary']['attendance_percentage'] ?? 0;
                        $acadRate = $record->snapshot['assessment_summary']['overall_average_percentage'] ?? 0;

                        $text = "السلام عليكم ورحمة الله،\nالسيد/ة {$parentName}\n\nإليكم التقرير الأسبوعي لمتابعة الطالب/ة {$studentName} للفترة ({$period}):\n- نسبة الحضور: {$attRate}%\n- متوسط التقييمات: {$acadRate}%\n\nيمكنكم الاطلاع على التقرير التفصيلي من الرابط التالي:\n{$url}\n\nمع خالص التقدير والتحية.";

                        $encoded = urlencode($text);
                        $waUrl = ! empty($parentPhone)
                            ? "https://wa.me/{$parentPhone}?text={$encoded}"
                            : "https://wa.me/?text={$encoded}";

                        return redirect()->away($waUrl);
                    }),

                Action::make('regenerateLink')
                    ->label('تحديث / نسخ الرابط')
                    ->icon(Heroicon::OutlinedLink)
                    ->color('info')
                    ->action(function (Report $record, WeeklyReportService $service): void {
                        $token = $service->regenerateToken($record);
                        $url = route('parent.report.show', ['token' => $token]);

                        Notification::make()
                            ->title('تم تجديد رابط التقرير')
                            ->body("الرابط النشط: {$url}")
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                Action::make('viewSnapshot')
                    ->label('معاينة سريعة')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Report $record) => 'ملخص التقرير الأسبوعي: '.$record->week_start?->format('M d').' - '.$record->week_end?->format('M d, Y'))
                    ->modalContent(function (Report $record) {
                        $snap = $record->snapshot ?? [];
                        $att = $snap['attendance_summary'] ?? [];
                        $asmt = $snap['assessment_summary'] ?? [];
                        $sessions = $snap['sessions'] ?? [];

                        $html = '<div class="space-y-4 text-sm" dir="rtl">';
                        $html .= '<div class="grid grid-cols-2 gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                        $html .= '<div><strong>نسبة الحضور:</strong> '.($att['attendance_percentage'] ?? 0).'% ('.($att['present'] ?? 0).' حاضر، '.($att['late'] ?? 0).' متأخر، '.($att['absent'] ?? 0).' غائب)</div>';
                        $html .= '<div><strong>متوسط التقييمات:</strong> '.($asmt['overall_average_percentage'] ?? 0).'% ('.($asmt['total_assessments'] ?? 0).' مهمة مقيمة)</div>';
                        $html .= '</div>';

                        $html .= '<h4 class="font-bold mt-3">الحصص خلال الفترة:</h4>';
                        $html .= '<ul class="divide-y divide-gray-200 dark:divide-gray-700">';
                        foreach ($sessions as $s) {
                            $html .= '<li class="py-2 flex justify-between items-center">';
                            $html .= '<span><strong>'.htmlspecialchars($s['subject_name'] ?? 'حصة').'</strong> ('.htmlspecialchars($s['date'] ?? '').' '.htmlspecialchars($s['start_time'] ?? '').')</span>';
                            $attStatus = $s['attendance']['status'] ?? 'غير مسجل';
                            $html .= '<span class="px-2 py-0.5 rounded text-xs bg-primary-100 text-primary-800 dark:bg-primary-900">الحضور: '.htmlspecialchars($attStatus).'</span>';
                            $html .= '</li>';
                        }
                        $html .= '</ul>';
                        $html .= '</div>';

                        return new HtmlString($html);
                    }),

                Action::make('revokeToken')
                    ->label('تعطيل الرابط')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تعطيل رابط التقرير')
                    ->modalDescription('هل أنت متأكد من تعطيل هذا الرابط؟ لن يتمكن ولي الأمر من فتحه بعد ذلك.')
                    ->visible(fn (Report $record): bool => $record->isValid())
                    ->action(function (Report $record, WeeklyReportService $service): void {
                        $service->revokeToken($record);
                        Notification::make()
                            ->title('تم تعطيل رابط التقرير بنجاح')
                            ->danger()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف التقرير')
                    ->modalDescription('هل أنت متأكد من حذف هذا التقرير نهائياً؟')
                    ->action(fn (Report $record) => $record->delete()),
            ]);
    }
}
