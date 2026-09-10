<?php

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\MonthlyFeeStatus;
use App\Enums\WeeklyDay;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Models\Subject;
use App\Services\WeeklyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create students and subjects with many-to-many relationship', function () {
    $student = Student::factory()->create([
        'name' => 'Ahmed Ali',
        'active' => true,
    ]);

    $subject = Subject::factory()->create([
        'name' => 'Physics 101',
    ]);

    $student->subjects()->attach($subject->id, [
        'weekly_day' => WeeklyDay::Monday->value,
        'weekly_start_time' => '10:00:00',
        'weekly_end_time' => '12:00:00',
        'teacher_notes' => 'Weekly tutoring session',
    ]);

    expect($student->subjects)->toHaveCount(1)
        ->and($student->subjects->first()->name)->toBe('Physics 101')
        ->and($student->subjects->first()->pivot->weekly_day)->toBe(WeeklyDay::Monday->value)
        ->and($subject->students)->toHaveCount(1)
        ->and($subject->students->first()->name)->toBe('Ahmed Ali');
});

test('enforces end_time greater than start_time on class session', function () {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();

    expect(function () use ($student, $subject) {
        ClassSession::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'date' => '2026-09-10',
            'start_time' => '12:00:00',
            'end_time' => '11:00:00', // invalid: end before start
            'status' => ClassSessionStatus::Scheduled,
        ]);
    })->toThrow(InvalidArgumentException::class, 'The session end time must be after the start time.');
});

test('enforces score less than or equal to max_score on assessment', function () {
    $session = ClassSession::factory()->create();

    expect(function () use ($session) {
        Assessment::create([
            'class_id' => $session->id,
            'type' => AssessmentType::Quiz,
            'title' => 'Math Pop Quiz',
            'score' => 105.00, // invalid: score > max_score
            'max_score' => 100.00,
        ]);
    })->toThrow(InvalidArgumentException::class, 'The score cannot be greater than the maximum score.');
});

test('calculates assessment score percentage correctly', function () {
    $session = ClassSession::factory()->create();

    $assessment = Assessment::create([
        'class_id' => $session->id,
        'type' => AssessmentType::Exam,
        'title' => 'Midterm',
        'score' => 45.00,
        'max_score' => 50.00,
    ]);

    expect($assessment->percentage)->toBe(90.0);
});

test('creates class session with attendance and multiple assessments', function () {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create();

    $session = ClassSession::create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-10',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'status' => ClassSessionStatus::Completed,
        'general_notes' => 'Covered Chapter 3',
    ]);

    $attendance = Attendance::create([
        'class_id' => $session->id,
        'status' => AttendanceStatus::Present,
        'notes' => 'On time and engaged',
    ]);

    $assessment1 = Assessment::create([
        'class_id' => $session->id,
        'type' => AssessmentType::Homework,
        'title' => 'HW 3',
        'score' => 9.00,
        'max_score' => 10.00,
    ]);

    $assessment2 = Assessment::create([
        'class_id' => $session->id,
        'type' => AssessmentType::Quiz,
        'title' => 'Quiz 3',
        'score' => 18.00,
        'max_score' => 20.00,
    ]);

    expect($session->attendance->status)->toBe(AttendanceStatus::Present)
        ->and($session->assessments)->toHaveCount(2)
        ->and($attendance->classSession->id)->toBe($session->id);
});

test('creates monthly fee with correct enum status and casts', function () {
    $student = Student::factory()->create();

    $fee = MonthlyFee::create([
        'student_id' => $student->id,
        'month' => 9,
        'year' => 2026,
        'amount' => 750.00,
        'status' => MonthlyFeeStatus::Paid,
        'paid_at' => now(),
        'notes' => 'Paid via bank transfer',
    ]);

    expect($fee->status)->toBe(MonthlyFeeStatus::Paid)
        ->and($fee->amount)->toBe('750.00')
        ->and($fee->student->id)->toBe($student->id);
});

test('weekly report service generates snapshot and handles token hashing and verification', function () {
    $student = Student::factory()->create();
    $subject = Subject::factory()->create(['name' => 'Chemistry']);

    $session1 = ClassSession::create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-07',
        'start_time' => '09:00:00',
        'end_time' => '10:30:00',
        'status' => ClassSessionStatus::Completed,
    ]);

    Attendance::create([
        'class_id' => $session1->id,
        'status' => AttendanceStatus::Present,
    ]);

    Assessment::create([
        'class_id' => $session1->id,
        'type' => AssessmentType::Homework,
        'title' => 'Lab prep',
        'score' => 10.0,
        'max_score' => 10.0,
    ]);

    $session2 = ClassSession::create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => '2026-09-09',
        'start_time' => '09:00:00',
        'end_time' => '10:30:00',
        'status' => ClassSessionStatus::Completed,
    ]);

    Attendance::create([
        'class_id' => $session2->id,
        'status' => AttendanceStatus::Late,
    ]);

    Assessment::create([
        'class_id' => $session2->id,
        'type' => AssessmentType::Quiz,
        'title' => 'Lab Quiz',
        'score' => 8.0,
        'max_score' => 10.0,
    ]);

    $service = app(WeeklyReportService::class);
    $result = $service->generate($student, '2026-09-07', '2026-09-13');

    expect($result)->toHaveKeys(['report', 'plain_token'])
        ->and(strlen($result['plain_token']))->toBe(64);

    $report = $result['report'];
    expect($report->token_hash)->toBe(hash('sha256', $result['plain_token']))
        ->and($report->snapshot)->toBeArray()
        ->and($report->snapshot['attendance_summary']['total_sessions'])->toBe(2)
        ->and($report->snapshot['attendance_summary']['present'])->toBe(1)
        ->and($report->snapshot['attendance_summary']['late'])->toBe(1)
        ->and($report->snapshot['attendance_summary']['attendance_percentage'])->toEqual(75.0)
        ->and($report->snapshot['assessment_summary']['total_assessments'])->toBe(2)
        ->and($report->snapshot['assessment_summary']['overall_average_percentage'])->toEqual(90.0)
        ->and($report->snapshot['subjects'])->toHaveCount(1)
        ->and($report->snapshot['subjects'][0]['subject_name'])->toBe('Chemistry');

    // Test token lookup & revocation
    $found = $service->findByPlainToken($result['plain_token']);
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($report->id);

    $service->revokeToken($report);
    $report->refresh();
    expect($report->isRevoked())->toBeTrue()
        ->and($service->findByPlainToken($result['plain_token']))->toBeNull();
});
