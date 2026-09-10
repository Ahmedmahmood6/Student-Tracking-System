<?php

namespace App\Http\Controllers;

use App\Services\WeeklyReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class ParentReportController extends Controller
{
    /**
     * Display the public weekly report for parents using the secure plain token.
     */
    public function show(string $token, WeeklyReportService $service): View|Response
    {
        $report = $service->findByPlainToken($token);

        if (! $report) {
            return response()->view('reports.parent-report-unavailable', [], 404);
        }

        return view('reports.parent-report', [
            'report' => $report,
            'snapshot' => $report->snapshot ?? [],
            'student' => $report->snapshot['student'] ?? [],
            'period' => $report->snapshot['period'] ?? [],
            'attendanceSummary' => $report->snapshot['attendance_summary'] ?? [],
            'assessmentSummary' => $report->snapshot['assessment_summary'] ?? [],
            'subjects' => $report->snapshot['subjects'] ?? [],
            'sessions' => $report->snapshot['sessions'] ?? [],
        ]);
    }
}
