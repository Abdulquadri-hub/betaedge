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
        'enrollment_status',
        'whatsapp_link',   
        'notes',          
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'published_at'      => 'datetime',
        'max_students'      => 'integer',
        'is_published'      => 'boolean',
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

    public function upcomingSessions(): HasMany
    {
        return $this->classSessions()
            ->where('status', 'scheduled')
            ->where('scheduled_start', '>=', now())
            ->orderBy('scheduled_start');
    }

    public function nextSession(): ?ClassSession
    {
        return $this->upcomingSessions()->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnrollmentOpen($query)
    {
        return $query->where('enrollment_status', 'open');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getStudentCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    public function getPriceAttribute(): ?float
    {
        return $this->course?->price;
    }

    public function getScheduleSummaryAttribute(): string
    {
        $day      = $this->course?->session_day;
        $time     = $this->course?->session_time;
        $duration = $this->course?->session_duration_minutes;

        if (!$day && !$time) return 'Schedule not set';

        $parts = array_filter([$day, $time ? $this->formatTime($time) : null]);
        $summary = implode(' at ', $parts);

        if ($duration) {
            $summary .= " ({$duration} min)";
        }

        return $summary;
    }

    public function isEnrollmentOpen(): bool
    {
        return $this->enrollment_status === 'open';
    }

    public function isFull(): bool
    {
        return $this->activeStudents()->count() >= $this->max_students;
    }

    public function openEnrollment(): void
    {
        $this->update(['enrollment_status' => 'open']);
    }

    public function closeEnrollment(): void
    {
        $this->update(['enrollment_status' => 'closed']);
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    private function formatTime(string $t): string
    {
        [$h, $m] = explode(':', $t);
        $hour = (int) $h;
        return ($hour % 12 ?: 12) . ':' . $m . ' ' . ($hour >= 12 ? 'PM' : 'AM');
    }
}