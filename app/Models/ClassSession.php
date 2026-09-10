<?php

namespace App\Models;

use App\Enums\ClassSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

class ClassSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'subject_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'general_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => ClassSessionStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ClassSession $session) {
            if ($session->start_time !== null && $session->end_time !== null) {
                if ($session->end_time <= $session->start_time) {
                    throw new InvalidArgumentException('The session end time must be after the start time.');
                }
            }
        });
    }

    /**
     * Get the student for the class session.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the subject for the class session.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the attendance record for the class session.
     */
    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'class_id');
    }

    /**
     * Get the assessments associated with the class session.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'class_id');
    }
}
