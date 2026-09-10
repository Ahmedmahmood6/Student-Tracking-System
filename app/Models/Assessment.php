<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class Assessment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'type',
        'title',
        'description',
        'score',
        'max_score',
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
            'type' => AssessmentType::class,
            'score' => 'float',
            'max_score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Assessment $assessment) {
            if ($assessment->max_score <= 0) {
                throw new InvalidArgumentException('The maximum score must be greater than zero.');
            }

            if ($assessment->score < 0) {
                throw new InvalidArgumentException('The score cannot be negative.');
            }

            if ($assessment->score > $assessment->max_score) {
                throw new InvalidArgumentException('The score cannot be greater than the maximum score.');
            }
        });
    }

    /**
     * Get percentage of assessment score.
     */
    protected function percentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->max_score > 0 ? round(($this->score / $this->max_score) * 100, 2) : 0.0,
        );
    }

    /**
     * Get the class session for this assessment.
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_id');
    }
}
