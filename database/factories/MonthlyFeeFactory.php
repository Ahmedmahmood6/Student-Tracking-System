<?php

namespace Database\Factories;

use App\Enums\MonthlyFeeStatus;
use App\Models\MonthlyFee;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyFee>
 */
class MonthlyFeeFactory extends Factory
{
    protected $model = MonthlyFee::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => 2026,
            'amount' => 500.00,
            'status' => MonthlyFeeStatus::Unpaid,
            'paid_at' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
