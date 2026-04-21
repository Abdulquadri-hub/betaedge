<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Batch extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'batch_name',
        'batch_code',
        'description',
        'start_date',
        'end_date',
        'max_students',
        'price',            
        'price_note',     
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
        'price'             => 'decimal:2',
        'max_students'      => 'integer',
        'is_published'      => 'boolean',
        'published_at'      => 'datetime',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'batch_courses', 'batch_id', 'course_id')
            // ->using(BatchCourse::class)
            ->withPivot([
                'id',
                'instructor_id',
                'session_day',
                'session_time',
                'session_duration_minutes',
                'session_platform',
                'session_frequency',
                'display_order',
            ])
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function batchCourses(): HasMany
    {
        return $this->hasMany(BatchCourse::class)->orderBy('display_order');
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

    public function getAcademicLevelAttribute(): ?string
    {
        return $this->courses()->with('academicLevel')->first()?->academicLevel?->name;
    }

    public function canBePublished(): bool
    {
        return $this->batchCourses()->exists();
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

    public function publish(): bool
    {
        if (!$this->canBePublished()) {
            return false;
        }

        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return true;
    }
}