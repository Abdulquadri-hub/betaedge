<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', "student_id", "course_id", "enrolled_at", "completed", "status", "progress_percentage", "final_grade", "notes"
    ];

    protected $casts = [
        "enrolled_at" => "datetime",
        "progress_percentage" => "decimal:2",
        "final_grade" => "decimal:2",
        "completed_at" => "datetime",
    ];

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo {
        return $this->belongsTo(Course::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    // Scopes
    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    // Helper Methods
    public function checkIfLate(): void
    {
        $isLate = $this->submitted_at->isAfter($this->assignment->due_at);
        $this->update(['is_late' => $isLate]);
    }

    public function calculatePenalty(): float
    {
        if (!$this->is_late || !$this->assignment->allows_late_submission) {
            return 0;
        }

        return $this->assignment->late_penalty_percentage;
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded' && $this->grade !== null;
    }

    public function updateProgress(): void
    {
        $course = $this->course;

        if (!$course) {
            $this->update(['progress_percentage' => 0]);
            return;
        }


        $totalAssignments = $course->assignments()->where('status', 'published')->count();
        
        if ($totalAssignments === 0) {
            $this->update(['progress_percentage' => 0]);
            return;
        }

        $completedAssignments = Submission::where('student_id', $this->student_id)
            ->whereHas('assignment', function ($query) use ($course) {
                $query->where('course_id', $course->id)
                      ->where('status', 'published');
            })
            ->where('status', 'graded')
            ->count();

        $progress = round(($completedAssignments / $totalAssignments) * 100, 2);
        $this->update(['progress_percentage' => $progress]);
    }
}
