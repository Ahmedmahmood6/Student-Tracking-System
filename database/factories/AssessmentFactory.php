<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'class_id' => ClassSession::factory(),
            'type' => fake()->randomElement(AssessmentType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'score' => 85.00,
            'max_score' => 100.00,
            'notes' => fake()->sentence(),
        ];
    }
}
