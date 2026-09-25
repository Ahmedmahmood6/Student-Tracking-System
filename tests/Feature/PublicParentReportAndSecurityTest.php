<?php

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Services\WeeklyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public parent report is accessible with valid token', function () {
    $student = Student::factory()->create([
        'name' => 'Layla Nour',
        'parent_name' => 'Nour Eldin',
        'parent_phone' => '01000000000',
    ]);

    $subject = Subject::factory()->create(['name' => 'Mathematics']);

    $session = ClassSession::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => now()->startOfWeek()->toDateString(),
        'status' => ClassSessionStatus::Completed,
        'general_notes' => 'Quadratic equations review',
    ]);

    Attendance::factory()->create([
        'class_id' => $session->id,
        'status' => AttendanceStatus::Present,
        'notes' => 'Great engagement',
    ]);

    Assessment::factory()->create([
        'class_id' => $session->id,
        'type' => AssessmentType::Quiz,
        'title' => 'Quadratic Quiz',
        'score' => 90.00,
        'max_score' => 100.00,
        'notes' => 'Flawless solving steps',
    ]);

    $service = app(WeeklyReportService::class);
    $result = $service->generate(
        $student,
        now()->startOfWeek()->toDateString(),
        now()->endOfWeek()->toDateString()
    );

    $plainToken = $result['plain_token'];
    $report = $result['report'];

    // SHA-256 hash stored in DB
    expect($report->token_hash)->toBe(hash('sha256', $plainToken));

    // Access public parent route as guest
    $response = $this->get(route('parent.report.show', ['token' => $plainToken]));

    $response->assertSuccessful();
    $response->assertSee('Layla Nour');
    $response->assertSee('Nour Eldin');
    $response->assertSee('Quadratic Quiz');
    $response->assertSee('90%');
    $response->assertSee('طباعة / حفظ PDF');
});

test('public parent report returns 404 generic error for invalid or revoked tokens', function () {
    // 1. Invalid random token
    $invalidResponse = $this->get('/parent-report/non_existent_token_123456');
    $invalidResponse->assertStatus(404);
    $invalidResponse->assertSee('التقرير غير متاح حالياً');

    // 2. Revoked token
    $student = Student::factory()->create(['name' => 'Kareem Tarek']);
    $service = app(WeeklyReportService::class);
    $result = $service->generate($student, now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString());

    $plainToken = $result['plain_token'];
    $report = $result['report'];

    // Revoke token
    $service->revokeToken($report);
    expect($report->fresh()->isRevoked())->toBeTrue();

    $revokedResponse = $this->get(route('parent.report.show', ['token' => $plainToken]));
    $revokedResponse->assertStatus(404);
    $revokedResponse->assertSee('التقرير غير متاح حالياً');
    // Ensure no sensitive student ID is leaked in error response
    $revokedResponse->assertDontSee('Kareem Tarek');
});

test('can regenerate token for a revoked or active report', function () {
    $student = Student::factory()->create(['name' => 'Mona Samir']);
    $service = app(WeeklyReportService::class);
    $result = $service->generate($student, now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString());

    $oldToken = $result['plain_token'];
    $report = $result['report'];

    $service->revokeToken($report);
    expect($service->findByPlainToken($oldToken))->toBeNull();

    // Regenerate
    $newToken = $service->regenerateToken($report);
    expect($newToken)->not->toBe($oldToken)
        ->and(strlen($newToken))->toBe(64);

    // Old token still invalid
    $this->get(route('parent.report.show', ['token' => $oldToken]))->assertStatus(404);

    // New token works
    $this->get(route('parent.report.show', ['token' => $newToken]))
        ->assertSuccessful()
        ->assertSee('Mona Samir');
});

test('parent report calculates total sessions by summing session_count and omits time range', function () {
    $student = Student::factory()->create(['name' => 'Sara Ali']);
    $subject = Subject::factory()->create(['name' => 'Arabic Language']);

    // Session 1: 2 units (e.g. 80 minutes)
    $session1 = ClassSession::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => now()->startOfWeek()->toDateString(),
        'start_time' => '17:00:00',
        'end_time' => '18:20:00',
        'session_count' => 2.0,
        'status' => ClassSessionStatus::Completed,
    ]);
    Attendance::factory()->create([
        'class_id' => $session1->id,
        'status' => AttendanceStatus::Present,
    ]);

    // Session 2: 1.5 units (e.g. 60 minutes)
    $session2 = ClassSession::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'start_time' => '19:00:00',
        'end_time' => '20:00:00',
        'session_count' => 1.5,
        'status' => ClassSessionStatus::Completed,
    ]);
    Attendance::factory()->create([
        'class_id' => $session2->id,
        'status' => AttendanceStatus::Present,
    ]);

    $service = app(WeeklyReportService::class);
    $result = $service->generate(
        $student,
        now()->startOfWeek()->toDateString(),
        now()->endOfWeek()->toDateString()
    );

    $snapshot = $result['report']->snapshot;
    expect($snapshot['attendance_summary']['total_sessions'])->toBe(3.5)
        ->and($snapshot['attendance_summary']['present'])->toBe(3)
        ->and($snapshot['subjects'][0]['total_sessions'])->toBe(3.5);

    $response = $this->get(route('parent.report.show', ['token' => $result['plain_token']]));
    $response->assertSuccessful();
    $response->assertSee('3.5');
    $response->assertSee('3 حاضر');
    $response->assertDontSee('(17:00 - 18:20)');
    $response->assertDontSee('(19:00 - 20:00)');
});
