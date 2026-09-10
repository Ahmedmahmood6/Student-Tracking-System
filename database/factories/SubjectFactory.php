<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English', 'History', 'Arabic', 'Science', 'Computer Science']),
            'description' => fake()->sentence(),
            'active' => true,
        ];
    }
}
