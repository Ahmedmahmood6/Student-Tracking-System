<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'week_start' => now()->startOfWeek()->toDateString(),
            'week_end' => now()->endOfWeek()->toDateString(),
            'token_hash' => hash('sha256', Str::random(64)),
            'snapshot' => ['summary' => 'Initial report snapshot'],
            'generated_at' => now(),
            'revoked_at' => null,
        ];
    }
}
