<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
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
            'active' => 'boolean',
        ];
    }

    /**
     * Get the students enrolled in this subject.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subject')
            ->withPivot(['id', 'weekly_day', 'weekly_start_time', 'weekly_end_time', 'teacher_notes'])
            ->withTimestamps();
    }

    /**
     * Get the pivot student-subject records for the subject.
     */
    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class, 'subject_id');
    }

    /**
     * Get the class sessions for the subject.
     */
    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'subject_id');
    }
}
