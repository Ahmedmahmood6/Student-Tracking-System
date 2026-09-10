<?php

namespace Database\Factories;

use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'date' => fake()->date(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => ClassSessionStatus::Scheduled,
            'general_notes' => fake()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassSessionStatus::Completed,
        ]);
    }
}
