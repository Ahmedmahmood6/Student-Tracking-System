<?php

namespace App\Models;

use App\Enums\MonthlyFeeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyFee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'month',
        'year',
        'amount',
        'status',
        'paid_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'amount' => 'decimal:2',
            'status' => MonthlyFeeStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the student for the monthly fee.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
