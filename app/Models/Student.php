<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'parent_name',
        'parent_phone',
        'date_of_birth',
        'address',
        'notes',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the subjects associated with the student.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject')
            ->withPivot(['id', 'weekly_day', 'weekly_start_time', 'weekly_end_time', 'teacher_notes'])
            ->withTimestamps();
    }

    /**
     * Get the pivot student-subject records for the student.
     */
    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class, 'student_id');
    }

    /**
     * Get the class sessions for the student.
     */
    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'student_id');
    }

    /**
     * Get the weekly reports for the student.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'student_id');
    }

    /**
     * Get the monthly fees for the student.
     */
    public function monthlyFees(): HasMany
    {
        return $this->hasMany(MonthlyFee::class, 'student_id');
    }
}
