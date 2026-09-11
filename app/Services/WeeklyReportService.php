<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Models\ClassSession;
use App\Models\Report;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class WeeklyReportService
{
    /**
     * Generate and persist a weekly report for a student with token hashing.
     *
     * @return array{report: Report, plain_token: string}
     */
    public function generate(Student $student, CarbonInterface|string $weekStart, CarbonInterface|string $weekEnd): array
    {
        $startDate = $weekStart instanceof CarbonInterface ? $weekStart->toDateString() : Carbon::parse($weekStart)->toDateString();
        $endDate = $weekEnd instanceof CarbonInterface ? $weekEnd->toDateString() : Carbon::parse($weekEnd)->toDateString();

        $snapshot = $this->buildSnapshot($student, $startDate, $endDate);
        $plainToken = Str::random(64);
        $tokenHash = $this->hashToken($plainToken);

        $report = Report::create([
            'student_id' => $student->id,
            'week_start' => $startDate,
            'week_end' => $endDate,
            'token_hash' => $tokenHash,
            'snapshot' => $snapshot,
            'generated_at' => now(),
            'revoked_at' => null,
        ]);

        return [
            'report' => $report,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * Hash a plain report token using SHA-256.
     */
    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Locate a valid (non-revoked) report by its plain token.
     */
    public function findByPlainToken(string $plainToken): ?Report
    {
        $tokenHash = $this->hashToken($plainToken);

        return Report::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();
    }

    /**
     * Revoke access token for a report.
     */
    public function revokeToken(Report $report): bool
    {
        return $report->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Regenerate a new secure access token for a report and reactivate it.
     */
    public function regenerateToken(Report $report): string
    {
        $plainToken = Str::random(64);

        $report->update([
            'token_hash' => $this->hashToken($plainToken),
            'revoked_at' => null,
        ]);

        return $plainToken;
    }

    /**
     * Build the complete data snapshot for a student's weekly timeframe.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(Student $student, string $startDate, string $endDate): array
    {
        /** @var Collection<int, ClassSession> $sessions */
        $sessions = ClassSession::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['subject', 'attendance', 'assessments'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $attendanceStats = $this->calculateAttendanceStats($sessions);
        $assessmentStats = $this->calculateAssessmentStats($sessions);
        $subjectSummaries = $this->calculateSubjectSummaries($sessions);

        $ratedSessions = $sessions->filter(fn (ClassSession $s) => $s->rating !== null);
        $averageRating = $ratedSessions->isNotEmpty()
            ? round((float) $ratedSessions->avg('rating'), 1)
            : null;

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'phone' => $student->phone,
                'parent_name' => $student->parent_name,
                'parent_phone' => $student->parent_phone,
            ],
            'period' => [
                'week_start' => $startDate,
                'week_end' => $endDate,
                'generated_at' => now()->toIso8601String(),
            ],
            'attendance_summary' => $attendanceStats,
            'assessment_summary' => $assessmentStats,
            'performance_summary' => [
                'average_rating' => $averageRating,
                'rated_sessions_count' => $ratedSessions->count(),
            ],
            'subjects' => $subjectSummaries,
            'sessions' => $sessions->map(function (ClassSession $session) {
                return [
                    'id' => $session->id,
                    'subject_name' => $session->subject?->name,
                    'date' => $session->date?->toDateString(),
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'status' => $session->status?->value ?? (string) $session->status,
                    'rating' => $session->rating,
                    'general_notes' => $session->general_notes,
                    'attendance' => $session->attendance ? [
                        'status' => $session->attendance->status?->value ?? (string) $session->attendance->status,
                        'notes' => $session->attendance->notes,
                    ] : null,
                    'assessments' => $session->assessments->map(function ($assessment) {
                        return [
                            'id' => $assessment->id,
                            'type' => $assessment->type?->value ?? (string) $assessment->type,
                            'title' => $assessment->title,
                            'score' => (float) $assessment->score,
                            'max_score' => (float) $assessment->max_score,
                            'percentage' => $assessment->percentage,
                            'notes' => $assessment->notes,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Calculate attendance counts and percentage.
     *
     * @param  Collection<int, ClassSession>  $sessions
     * @return array{total_sessions: int, present: int, absent: int, late: int, recorded_sessions: int, attendance_percentage: float}
     */
    public function calculateAttendanceStats(Collection $sessions): array
    {
        $totalSessions = $sessions->count();
        $present = 0;
        $absent = 0;
        $late = 0;

        foreach ($sessions as $session) {
            if (! $session->attendance) {
                continue;
            }

            $status = $session->attendance->status instanceof AttendanceStatus
                ? $session->attendance->status
                : AttendanceStatus::tryFrom((string) $session->attendance->status);

            match ($status) {
                AttendanceStatus::Present => $present++,
                AttendanceStatus::Absent => $absent++,
                AttendanceStatus::Late => $late++,
                default => null,
            };
        }

        $recordedSessions = $present + $absent + $late;
        // Calculation: (Present + Late) / Total Recorded Sessions or default 0
        $attendancePercentage = $recordedSessions > 0
            ? round((($present + ($late * 0.5)) / $recordedSessions) * 100, 2)
            : 0.0;

        return [
            'total_sessions' => $totalSessions,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'recorded_sessions' => $recordedSessions,
            'attendance_percentage' => $attendancePercentage,
        ];
    }

    /**
     * Calculate assessment metrics and percentage averages.
     *
     * @param  Collection<int, ClassSession>  $sessions
     * @return array<string, mixed>
     */
    public function calculateAssessmentStats(Collection $sessions): array
    {
        $totalEarned = 0.0;
        $totalMax = 0.0;
        $assessmentCount = 0;

        /** @var array<string, array{count: int, earned: float, max: float, average_percentage: float}> $byType */
        $byType = [];

        foreach ($sessions as $session) {
            foreach ($session->assessments as $assessment) {
                $score = (float) $assessment->score;
                $maxScore = (float) $assessment->max_score;

                if ($maxScore <= 0) {
                    continue;
                }

                $totalEarned += $score;
                $totalMax += $maxScore;
                $assessmentCount++;

                $typeName = $assessment->type instanceof AssessmentType
                    ? $assessment->type->value
                    : (string) $assessment->type;

                if (! isset($byType[$typeName])) {
                    $byType[$typeName] = [
                        'count' => 0,
                        'earned' => 0.0,
                        'max' => 0.0,
                        'average_percentage' => 0.0,
                    ];
                }

                $byType[$typeName]['count']++;
                $byType[$typeName]['earned'] += $score;
                $byType[$typeName]['max'] += $maxScore;
            }
        }

        foreach ($byType as $key => $data) {
            $byType[$key]['average_percentage'] = $data['max'] > 0
                ? round(($data['earned'] / $data['max']) * 100, 2)
                : 0.0;
            $byType[$key]['earned'] = round($data['earned'], 2);
            $byType[$key]['max'] = round($data['max'], 2);
        }

        $overallAverage = $totalMax > 0
            ? round(($totalEarned / $totalMax) * 100, 2)
            : 0.0;

        return [
            'total_assessments' => $assessmentCount,
            'total_score_earned' => round($totalEarned, 2),
            'total_max_score' => round($totalMax, 2),
            'overall_average_percentage' => $overallAverage,
            'by_type' => $byType,
        ];
    }

    /**
     * Calculate subject-level breakdowns.
     *
     * @param  Collection<int, ClassSession>  $sessions
     * @return array<int, array<string, mixed>>
     */
    public function calculateSubjectSummaries(Collection $sessions): array
    {
        $grouped = $sessions->groupBy('subject_id');
        $summaries = [];

        foreach ($grouped as $subjectId => $subjectSessions) {
            /** @var Collection<int, ClassSession> $subjectSessions */
            $firstSession = $subjectSessions->first();
            $subjectName = $firstSession?->subject?->name ?? 'Unknown Subject';

            $attendance = $this->calculateAttendanceStats($subjectSessions);
            $assessments = $this->calculateAssessmentStats($subjectSessions);

            $summaries[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'total_sessions' => $subjectSessions->count(),
                'attendance_percentage' => $attendance['attendance_percentage'],
                'assessment_average_percentage' => $assessments['overall_average_percentage'],
                'assessments_count' => $assessments['total_assessments'],
            ];
        }

        return $summaries;
    }
}
