<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Batch extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'course_id',
        'batch_name',
        'batch_code',
        'description',
        'start_date',
        'end_date',
        'max_students',
        'status',
        'schedule_type',
        'schedule_days',
        'start_time',
        'end_time',
        'location',
        'notes',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
        'schedule_days' => 'array',
        'max_students' => 'integer',
        'is_published' => 'boolean',
    ];


    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'batch_student', 'batch_id', 'student_id')
            ->withPivot(['enrolled_at', 'completed_at', 'status', 'progress_percentage', 'final_grade'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->where('status', '!=', 'cancelled');
    }

    public function scopeOngoing($query)
    {
        return $query->whereDate('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now());
            })
            ->where('status', 'active');
    }

    
    public function getStudentCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function isFull(): bool
    {
        return $this->activeStudents()->count() >= $this->max_students;
    }

    public function getProgressPercentage(): float
    {
        if ($this->end_date === null) {
            return 0;
        }

        $start = $this->start_date;
        $end = $this->end_date;
        $now = now()->date;

        if ($now < $start) {
            return 0;
        }

        if ($now > $end) {
            return 100;
        }

        $total = $start->diffInDays($end);
        $elapsed = $start->diffInDays($now);

        return round(($elapsed / $total) * 100, 2);
    }
}
