<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'week_start',
        'week_end',
        'token_hash',
        'snapshot',
        'generated_at',
        'revoked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'snapshot' => 'array',
            'generated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Check if report access token is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if report access token is active/valid.
     */
    public function isValid(): bool
    {
        return ! $this->isRevoked();
    }

    /**
     * Get the student for the report.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
