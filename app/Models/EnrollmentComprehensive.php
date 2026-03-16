<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enrollment Model
 *
 * Represents a student's enrollment in a course
 */
class Enrollment extends Model
{
    use BelongsToTenant, SoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'course_id',
        'enrollment_date',
        'status',
        'progress_percentage',
        'final_grade',
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the student for this enrollment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course for this enrollment
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get all submissions for this enrollment
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the grade for this enrollment
     */
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    /**
     * Get all class sessions attended
     */
    public function classSessionsAttended(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope to get active enrollments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending enrollments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get withdrawn enrollments
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    /**
     * Scope to get failed enrollments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Calculate attendance rate for this enrollment
     */
    public function getAttendanceRate(): float
    {
        $totalSessions = $this->course->classSessions()->count();
        
        if ($totalSessions === 0) {
            return 0;
        }

        $attended = $this->classSessionsAttended()
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($attended / $totalSessions) * 100, 2);
    }

    /**
     * Get average submission score
     */
    public function getAverageSubmissionScore(): float
    {
        $grades = $this->submissions()
            ->whereHas('grade', function ($query) {
                $query->where('is_published', true);
            })
            ->get()
            ->flatMap->grade;

        if ($grades->isEmpty()) {
            return 0;
        }

        return round($grades->avg('percentage'), 2);
    }

    /**
     * Update progress based on submissions
     */
    public function updateProgress(): bool
    {
        $submissions = $this->submissions()->count();
        $courseAssignments = $this->course->assignments()->count();

        if ($courseAssignments === 0) {
            $progress = 0;
        } else {
            $progress = ($submissions / $courseAssignments) * 100;
        }

        return $this->update(['progress_percentage' => round($progress, 2)]);
    }

    /**
     * Mark enrollment as completed
     */
    public function markCompleted(?float $finalGrade = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'final_grade' => $finalGrade ?? $this->getAverageSubmissionScore(),
        ]);
    }

    /**
     * Mark enrollment as withdrawn
     */
    public function markWithdrawn(): bool
    {
        return $this->update(['status' => 'withdrawn']);
    }

    /**
     * Check if enrollment is passing (assuming 60% is passing)
     */
    public function isPassing(): bool
    {
        return $this->final_grade >= 60;
    }

    /**
     * Get performance data
     */
    public function getPerformanceData(): array
    {
        return [
            'enrollment_status' => $this->status,
            'progress' => $this->progress_percentage,
            'attendance_rate' => $this->getAttendanceRate(),
            'average_score' => $this->getAverageSubmissionScore(),
            'final_grade' => $this->final_grade,
            'is_passing' => $this->isPassing(),
        ];
    }
}
