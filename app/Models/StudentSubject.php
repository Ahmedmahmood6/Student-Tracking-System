<?php

namespace App\Models;

use App\Enums\WeeklyDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class StudentSubject extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_subject';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'subject_id',
        'weekly_day',
        'weekly_start_time',
        'weekly_end_time',
        'teacher_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekly_day' => WeeklyDay::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentSubject $studentSubject) {
            if ($studentSubject->weekly_start_time !== null && $studentSubject->weekly_end_time !== null) {
                if ($studentSubject->weekly_end_time <= $studentSubject->weekly_start_time) {
                    throw new InvalidArgumentException('The weekly end time must be after the weekly start time.');
                }
            }
        });
    }

    /**
     * Get the student that owns the relationship.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the subject that owns the relationship.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
