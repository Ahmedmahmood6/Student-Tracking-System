<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير الأسبوعي لمتابعة الطالب - {{ $student['name'] ?? 'الطالب' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .print-shadow-none {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
            }
        }
    </style>
</head>
<body class="min-h-full py-6 sm:py-10 px-4 sm:px-6 lg:px-8 text-slate-800">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Top Header Controls (No Print) -->
        <div class="no-print flex flex-col sm:flex-row items-center justify-between gap-4 pb-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-sm">
                    ST
                </span>
                <span class="text-sm font-semibold text-slate-700 tracking-wide">نظام متابعة الطلاب والتقارير</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    طباعة / حفظ PDF
                </button>
            </div>
        </div>

        <!-- Main Report Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden print-shadow-none">
            
            <!-- Hero Header Banner -->
            <div class="bg-gradient-to-l from-indigo-700 via-indigo-600 to-blue-600 px-6 sm:px-8 py-8 text-white">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-semibold tracking-wide text-indigo-100 mb-2">
                            تقرير المتابعة الأسبوعي الرسمي
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $student['name'] ?? 'الطالب' }}</h1>
                        <p class="text-indigo-100 text-sm mt-1">
                            ولي الأمر: <span class="font-medium text-white">{{ $student['parent_name'] ?? 'غير محدد' }}</span>
                            @if(!empty($student['parent_phone']))
                                &bull; هاتف: <span class="font-medium text-white" dir="ltr">{{ $student['parent_phone'] }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="sm:text-left bg-white/10 backdrop-blur-md px-4 py-3 rounded-xl border border-white/10 w-full sm:w-auto">
                        <div class="text-xs tracking-wider text-indigo-200 font-semibold mb-1">فترة التقرير</div>
                        <div class="text-base font-bold text-white">
                            {{ !empty($period['week_start']) ? \Carbon\Carbon::parse($period['week_start'])->format('Y/m/d') : '' }}
                            &mdash;
                            {{ !empty($period['week_end']) ? \Carbon\Carbon::parse($period['week_end'])->format('Y/m/d') : '' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8 space-y-8">

                <!-- KPI Overview Grid -->
                <div class="grid grid-cols-2 {{ !empty($performanceSummary['average_rating']) ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
                    <!-- Attendance Rate -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">نسبة الحضور</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-emerald-600">
                                {{ $attendanceSummary['attendance_percentage'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            <span class="text-emerald-700 font-semibold">{{ $attendanceSummary['present'] ?? 0 }} حاضر</span> &bull; 
                            <span class="text-amber-700 font-semibold">{{ $attendanceSummary['late'] ?? 0 }} متأخر</span> &bull; 
                            <span class="text-rose-700 font-semibold">{{ $attendanceSummary['absent'] ?? 0 }} غائب</span>
                        </div>
                    </div>

                    <!-- Academic Average -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">متوسط التقييمات</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-indigo-600">
                                {{ $assessmentSummary['overall_average_percentage'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            بناءً على {{ $assessmentSummary['total_assessments'] ?? 0 }} تقييم / واجب
                        </div>
                    </div>

                    <!-- Session Performance Rating -->
                    @if(!empty($performanceSummary['average_rating']))
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">تقييم مستوى الحصة</span>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-3xl font-extrabold text-amber-500">
                                {{ $performanceSummary['average_rating'] }}
                            </span>
                            <span class="text-sm font-bold text-amber-500">/ 5 ⭐</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            متوسط تقييم المعلم في الحصص
                        </div>
                    </div>
                    @endif

                    <!-- Total Classes -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">إجمالي الحصص</span>
                        <div class="mt-2">
                            <span class="text-3xl font-extrabold text-slate-800">
                                {{ $attendanceSummary['total_sessions'] ?? 0 }}
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            حصة مجدولة هذا الأسبوع
                        </div>
                    </div>

                    <!-- Total Assessments -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">المهام والاختبارات</span>
                        <div class="mt-2">
                            <span class="text-3xl font-extrabold text-slate-800">
                                {{ $assessmentSummary['total_assessments'] ?? 0 }}
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            واجبات، اختبارات قصيرة وامتحانات
                        </div>
                    </div>
                </div>

                <!-- Subjects Progress Table / Cards -->
                @if(!empty($subjects))
                <div class="space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        ملخص الأداء حسب المواد الدراسية
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($subjects as $subj)
                        <div class="p-4 rounded-xl border border-slate-200 bg-white hover:border-indigo-200 transition">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-bold text-slate-900">{{ $subj['subject_name'] }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $subj['total_sessions'] }} حصة
                                </span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">نسبة الحضور:</span>
                                    <span class="font-semibold text-emerald-600">{{ $subj['attendance_percentage'] }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">متوسط التقييمات:</span>
                                    <span class="font-semibold text-indigo-600">{{ $subj['assessment_average_percentage'] }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">المهام المقيمة:</span>
                                    <span class="font-semibold text-slate-700">{{ $subj['assessments_count'] }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Class Sessions Timeline -->
                @if(!empty($sessions))
                <div class="space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        سجل الحصص الأسبوعية والحضور
                    </h2>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="divide-y divide-slate-200">
                            @foreach($sessions as $session)
                            <div class="p-4 sm:p-5 hover:bg-slate-50 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900">{{ $session['subject_name'] ?? 'حصة دراسية' }}</span>
                                            <span class="text-xs text-slate-500">
                                                &bull; {{ \Carbon\Carbon::parse($session['date'])->translatedFormat('l, d M Y') }} ({{ substr($session['start_time'], 0, 5) }} - {{ substr($session['end_time'], 0, 5) }})
                                            </span>
                                        </div>
                                        @if(!empty($session['general_notes']))
                                        <p class="text-xs text-slate-600 mt-1 italic">
                                            <span class="font-semibold not-italic text-slate-700">موضوع الدرس:</span> {{ $session['general_notes'] }}
                                        </p>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Session In-Class Star Rating -->
                                        @if(!empty($session['rating']))
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-sm" title="تقييم المعلم لمستوى الطالب في الحصة">
                                                <span>{{ str_repeat('⭐', $session['rating']) }}</span>
                                                <span class="text-slate-700">({{ $session['rating'] }}/5)</span>
                                            </span>
                                        @endif

                                        <!-- Attendance Status -->
                                        @php
                                            $att = $session['attendance']['status'] ?? null;
                                        @endphp
                                        @if($att === 'present')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> حاضر
                                            </span>
                                        @elseif($att === 'late')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> متأخر
                                            </span>
                                        @elseif($att === 'absent')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> غائب
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                لم يُسجل
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Attendance Notes if available -->
                                @if(!empty($session['attendance']['notes']))
                                <div class="mt-2 text-xs bg-slate-100/70 p-2 rounded text-slate-700">
                                    <span class="font-medium">ملاحظات الحضور:</span> {{ $session['attendance']['notes'] }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Assessments & Scores -->
                @php
                    $allAssessments = collect($sessions)->flatMap(function($s) {
                        return collect($s['assessments'] ?? [])->map(function($a) use ($s) {
                            $a['subject_name'] = $s['subject_name'] ?? 'المادة';
                            $a['date'] = $s['date'] ?? '';
                            return $a;
                        });
                    });

                    $typeLabels = [
                        'homework' => 'واجب منزلي',
                        'quiz' => 'اختبار قصير',
                        'exam' => 'امتحان',
                        'assignment' => 'تكليف',
                        'activity' => 'نشاط',
                    ];
                @endphp

                @if($allAssessments->isNotEmpty())
                <div class="space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        نتائج الواجبات والاختبارات والتقييمات
                    </h2>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-right text-sm">
                            <thead class="bg-slate-50 text-slate-700 font-semibold text-xs tracking-wider">
                                <tr>
                                    <th class="py-3 px-4">عنوان التكليف / الاختبار</th>
                                    <th class="py-3 px-4">النوع</th>
                                    <th class="py-3 px-4">الدرجة</th>
                                    <th class="py-3 px-4">النسبة المئوية</th>
                                    <th class="py-3 px-4">ملاحظات وتوجيهات المعلم</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($allAssessments as $asmt)
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">{{ $asmt['title'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $asmt['subject_name'] }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700">
                                            {{ $typeLabels[$asmt['type'] ?? ''] ?? ($asmt['type'] ?? 'واجب') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-800">
                                        {{ $asmt['score'] }} / {{ $asmt['max_score'] }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $pct = $asmt['percentage'] ?? ($asmt['max_score'] > 0 ? round(($asmt['score'] / $asmt['max_score']) * 100, 1) : 0);
                                            $badgeColor = $pct >= 85 ? 'bg-emerald-100 text-emerald-800' : ($pct >= 65 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800');
                                        @endphp
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badgeColor }}">
                                            {{ $pct }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-600">
                                        {{ $asmt['notes'] ?? '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>

            <!-- Footer -->
            <div class="bg-slate-50 border-t border-slate-100 px-6 sm:px-8 py-5 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-500">
                <div>
                    تم إنشاء هذا التقرير بأمان في: {{ !empty($period['generated_at']) ? \Carbon\Carbon::parse($period['generated_at'])->translatedFormat('d M Y - h:i A') : now()->translatedFormat('d M Y') }}
                </div>
                <div>
                    نظام متابعة الطلاب والتقارير &bull; نسخة ولي الأمر السرية
                </div>
            </div>

        </div>

    </div>
</body>
</html>
