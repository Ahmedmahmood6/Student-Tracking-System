<?php

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\MonthlyFeeStatus;
use App\Enums\WeeklyDay;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Filament\Resources\MonthlyFees\MonthlyFeeResource;
use App\Filament\Resources\Students\StudentResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin login page is accessible', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
});

test('authenticated user can access admin dashboard and see stats', function () {
    $user = User::factory()->create();

    $student = Student::factory()->create(['active' => true]);
    $subject = Subject::factory()->create(['active' => true]);
    $student->subjects()->attach($subject->id, [
        'weekly_day' => WeeklyDay::Sunday->value,
        'weekly_start_time' => '10:00:00',
        'weekly_end_time' => '11:00:00',
    ]);

    $session = ClassSession::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => now()->toDateString(),
        'status' => ClassSessionStatus::Completed,
    ]);

    Attendance::factory()->create([
        'class_id' => $session->id,
        'status' => AttendanceStatus::Present,
    ]);

    MonthlyFee::factory()->create([
        'student_id' => $student->id,
        'amount' => 500,
        'status' => MonthlyFeeStatus::Unpaid,
    ]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertSuccessful();
    $response->assertSee('Student Tracking & Reporting');
    $response->assertSee('Academic Management');
    $response->assertSee('Financial Management');
});

test('can render student resource list and view pages', function () {
    $user = User::factory()->create();
    $student = Student::factory()->create([
        'name' => 'Tarek Mansour',
        'phone' => '0123456789',
        'parent_name' => 'Mansour Ali',
        'parent_phone' => '0987654321',
        'active' => true,
    ]);

    $response = $this->actingAs($user)->get(StudentResource::getUrl('index'));
    $response->assertSuccessful();
    $response->assertSee('Tarek Mansour');

    $viewResponse = $this->actingAs($user)->get(StudentResource::getUrl('view', ['record' => $student]));
    $viewResponse->assertSuccessful();
    $viewResponse->assertSee('Tarek Mansour');
    $viewResponse->assertSee('Enrolled Subjects & Schedules');
});

test('can render subject resource and class session resource pages', function () {
    $user = User::factory()->create();
    $subject = Subject::factory()->create(['name' => 'Advanced Biology']);
    $student = Student::factory()->create(['name' => 'Salma Ezz']);

    $session = ClassSession::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'date' => now()->toDateString(),
    ]);

    $subjectResponse = $this->actingAs($user)->get(SubjectResource::getUrl('index'));
    $subjectResponse->assertSuccessful();
    $subjectResponse->assertSee('Advanced Biology');

    $classResponse = $this->actingAs($user)->get(ClassSessionResource::getUrl('index'));
    $classResponse->assertSuccessful();
    $classResponse->assertSee('Salma Ezz');
    $classResponse->assertSee('Advanced Biology');
});

test('can render monthly fee resource page and filter', function () {
    $user = User::factory()->create();
    $student = Student::factory()->create(['name' => 'Kareem Nader']);

    MonthlyFee::factory()->create([
        'student_id' => $student->id,
        'month' => 9,
        'year' => 2026,
        'amount' => 1200.00,
        'status' => MonthlyFeeStatus::Paid,
    ]);

    $response = $this->actingAs($user)->get(MonthlyFeeResource::getUrl('index'));
    $response->assertSuccessful();
    $response->assertSee('Kareem Nader');
    $response->assertSee('September 2026');
});
