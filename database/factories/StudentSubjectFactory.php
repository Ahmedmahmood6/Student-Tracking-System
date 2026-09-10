<?php

namespace Database\Factories;

use App\Enums\WeeklyDay;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentSubject>
 */
class StudentSubjectFactory extends Factory
{
    protected $model = StudentSubject::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'weekly_day' => fake()->randomElement(WeeklyDay::cases()),
            'weekly_start_time' => '10:00:00',
            'weekly_end_time' => '11:30:00',
            'teacher_notes' => fake()->sentence(),
        ];
    }
}
