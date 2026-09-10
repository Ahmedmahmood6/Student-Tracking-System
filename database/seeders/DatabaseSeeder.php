<?php

namespace Database\Seeders;

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
use App\Models\User;
use App\Services\WeeklyReportService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create or update Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Also ensure test@example.com exists
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Teacher / Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Create sample Subjects
        $math = Subject::updateOrCreate(['name' => 'Mathematics'], ['description' => 'Algebra, Geometry & Calculus', 'active' => true]);
        $physics = Subject::updateOrCreate(['name' => 'Physics'], ['description' => 'Mechanics, Electricity & Modern Physics', 'active' => true]);
        $chemistry = Subject::updateOrCreate(['name' => 'Chemistry'], ['description' => 'Organic and Inorganic Chemistry', 'active' => true]);
        $english = Subject::updateOrCreate(['name' => 'English Language'], ['description' => 'Grammar, Reading & Writing', 'active' => true]);

        // 3. Create sample Students
        $studentsData = [
            [
                'name' => 'Ahmed Mahmoud',
                'phone' => '01012345678',
                'parent_name' => 'Mahmoud Ali',
                'parent_phone' => '01098765432',
                'date_of_birth' => '2008-04-15',
                'address' => 'Nasr City, Cairo',
                'notes' => 'Excellent problem solver in Mathematics.',
                'active' => true,
            ],
            [
                'name' => 'Sara Hassan',
                'phone' => '01123456789',
                'parent_name' => 'Hassan Ibrahim',
                'parent_phone' => '01198765432',
                'date_of_birth' => '2009-08-20',
                'address' => 'Maadi, Cairo',
                'notes' => 'Very attentive, needs slight support in physics problems.',
                'active' => true,
            ],
            [
                'name' => 'Youssef Karim',
                'phone' => '01223456789',
                'parent_name' => 'Karim Nader',
                'parent_phone' => '01298765432',
                'date_of_birth' => '2008-11-05',
                'address' => 'Mohandessin, Giza',
                'notes' => 'Active student, consistently submits homework on time.',
                'active' => true,
            ],
        ];

        foreach ($studentsData as $data) {
            $student = Student::updateOrCreate(['name' => $data['name']], $data);

            // Enroll in subjects
            $student->subjects()->syncWithoutDetaching([
                $math->id => [
                    'weekly_day' => WeeklyDay::Sunday->value,
                    'weekly_start_time' => '16:00:00',
                    'weekly_end_time' => '17:30:00',
                    'teacher_notes' => 'Weekly Sunday session',
                ],
                $physics->id => [
                    'weekly_day' => WeeklyDay::Tuesday->value,
                    'weekly_start_time' => '18:00:00',
                    'weekly_end_time' => '19:30:00',
                    'teacher_notes' => 'Weekly Tuesday session',
                ],
            ]);

            // Create past completed sessions with attendance & assessments
            $session1 = ClassSession::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $math->id,
                    'date' => now()->subDays(3)->toDateString(),
                ],
                [
                    'start_time' => '16:00:00',
                    'end_time' => '17:30:00',
                    'status' => ClassSessionStatus::Completed,
                    'general_notes' => 'Reviewed linear algebra equations and practice set 4.',
                ]
            );

            Attendance::updateOrCreate(
                ['class_id' => $session1->id],
                ['status' => AttendanceStatus::Present, 'notes' => 'Attended on time and participated actively.']
            );

            Assessment::updateOrCreate(
                [
                    'class_id' => $session1->id,
                    'title' => 'Algebra HW #4',
                ],
                [
                    'type' => AssessmentType::Homework,
                    'score' => 95.0,
                    'max_score' => 100.0,
                    'description' => 'Solve problems 1 through 15 on page 42.',
                    'notes' => 'Great work on all equations.',
                ]
            );

            // Today's session
            $sessionToday = ClassSession::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $physics->id,
                    'date' => now()->toDateString(),
                ],
                [
                    'start_time' => '18:00:00',
                    'end_time' => '19:30:00',
                    'status' => ClassSessionStatus::Scheduled,
                    'general_notes' => 'Newton second law and momentum experiments.',
                ]
            );

            Attendance::updateOrCreate(
                ['class_id' => $sessionToday->id],
                ['status' => AttendanceStatus::Present, 'notes' => 'Present today.']
            );

            // Monthly Fees
            MonthlyFee::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'month' => now()->month,
                    'year' => now()->year,
                ],
                [
                    'amount' => 800.00,
                    'status' => MonthlyFeeStatus::Paid,
                    'paid_at' => now()->subDays(2),
                    'notes' => 'Paid via bank transfer',
                ]
            );

            // Generate weekly report
            $reportService = app(WeeklyReportService::class);
            $weekStart = now()->startOfWeek()->toDateString();
            $weekEnd = now()->endOfWeek()->toDateString();
            $reportService->generate($student, $weekStart, $weekEnd);
        }
    }
}
