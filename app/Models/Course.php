<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'course_code',
        'title',
        'description',
        'academic_level_id',
        'duration_weeks',
        'thumbnail',
        'learning_objectives',
        'prerequisites',
        'status',           
        'is_published',
        'published_at',
    ];
 
    protected $casts = [
        'duration_weeks'      => 'integer',
        'is_published'        => 'boolean',
        'published_at'        => 'datetime',
        'learning_objectives' => 'array',
        'prerequisites'       => 'array',
    ];

    protected $appends = [
        'active_batches',
    ];

    public function academicLevel(): BelongsTo {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function instructors(): BelongsToMany {
        return $this->belongsToMany(Instructor::class, "instructor_course", "course_id", "instructor_id")->withPivot(['assigned_date', 'is_primary_instructor'])->withTimestamps();
    }

    public function primaryInstructor(): BelongsToMany {
        return $this->instructors()->wherePivot("is_primary_instructor", true);
    }

    public function enrollments(): HasMany {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments(): HasMany {
        return $this->enrollments()->where('status', 'active');
    }

    public function students(): BelongsToMany {
        return $this->belongsToMany(Student::class, "enrollments")->withPivot(['enrolled_at', 'status', 'progress_percentage', 'final_grade'])->withTimestamps();
    }

    public function classSessions(): HasMany {
        return $this->hasMany(ClassSession::class);
    }

    public function upcomingSessions(): HasMany {
        return $this->classSessions()
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled');
    }

    // public function batches(): HasMany {
    //     return $this->hasMany(Batch::class);
    // }

     public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_courses', 'course_id', 'batch_id')
            // ->using(BatchCourse::class)
            ->withPivot([
                'id',
                'session_day',
                'session_time',
                'session_duration_minutes',
                'session_platform',
                'session_frequency',
                'display_order',
            ])
            ->withTimestamps();
    }

    public function activeBatches() {
        return $this->batches()->where('status', 'active');
    }

    public function batchCourses(): HasMany
    {
        return $this->hasMany(BatchCourse::class);
    }
 
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('display_order');
    }
 
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
 
    public function scopeByAcademicLevel($query, int $levelId)
    {
        return $query->where('academic_level_id', $levelId);
    }

    public function scopeElementary($query)
    {
        return $query->whereHas('academicLevel', function ($q) {
            $q->where('level_type', 'elementary');
        });
    }

    public function scopeMiddle($query)
    {
        return $query->whereHas('academicLevel', function ($q) {
            $q->where('level_type', 'middle');
        });
    }

    public function scopeHigh($query)
    {
        return $query->whereHas('academicLevel', function ($q) {
            $q->where('level_type', 'high');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
 
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function getGradeLevelName(): ?string
    {
        return $this->academicLevel?->name;
    }

    public function getGradeNumber(): ?int
    {
        return $this->academicLevel?->grade_number;
    }

    public function isForGradeLevel(int $gradeNumber): bool
    {
        return $this->academicLevel?->grade_number === $gradeNumber;
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function pendingEnrollmentRequests(): HasMany
    {
        return $this->enrollmentRequests()->where('status', 'pending');
    }

    public function activeEnrollmentRequests(): HasMany
    {
        return $this->enrollmentRequests()->whereIn('status', ['pending', 'parent_notified', 'payment_pending']);
    }

    public function getPendingEnrollmentRequestsCount(): int
    {
        return $this->pendingEnrollmentRequests()->count();
    }

    public function isFull(): bool
    {
        if (!$this->max_students) return false;
        
        return $this->activeEnrollments()->count() >= $this->max_students;
    }

    public function getAvailableSpots(): ?int
    {
        if (!$this->max_students) return null; 
        
        $current = $this->activeEnrollments()->count();
        return max(0, $this->max_students - $current);
    }

    public function getActiveBatchesAttribute(): int
    {
        return $this->active_batches_count ?? $this->activeBatches()->count();
    }

    public function canStudentEnroll(Student $student): array
    {
        if ($this->students()->where('student_id', $student->id)->exists()) {
            return ['can_enroll' => false, 'reason' => 'Already enrolled'];
        }

        if ($this->enrollmentRequests()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'parent_notified', 'payment_pending'])
                ->exists()) {
            return ['can_enroll' => false, 'reason' => 'Request already pending'];
        }

        if ($this->isFull()) {
            return ['can_enroll' => false, 'reason' => 'Course is full'];
        }

        if ($this->academic_level_id && $student->academic_level_id) {
            if ($this->academic_level_id !== $student->academic_level_id) {
                return ['can_enroll' => false, 'reason' => 'Grade level mismatch'];
            }
        }

        return ['can_enroll' => true, 'reason' => null];
    }

    public function getActiveBatchCountAttribute(): int
    {
        return $this->batches()
            ->wherePivot('tenant_id', $this->tenant_id)
            ->where('batches.status', 'active')
            ->count();
    }
 
    public function getTotalEnrollmentAttribute(): int
    {
        return \App\Models\Enrollment::whereHas('batch.batchCourses', function ($q) {
            $q->where('course_id', $this->id);
        })->where('status', 'active')->count();
    }
}
