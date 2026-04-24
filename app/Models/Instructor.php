<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'user_id', 'instructor_id', 'qualification', 'specialization',
        'years_of_experience', 'bio', 'linkedin_url', 'hourly_rate',
        'employment_type', 'hire_date', 'status',
    ];

    protected $casts = [
        'hire_date'   => 'datetime',
        'hourly_rate' => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'instructor_course', 'instructor_id', 'course_id')
            ->withPivot(['assigned_date', 'is_primary_instructor'])
            ->withTimestamps();
    }

    public function primaryCourses(): BelongsToMany
    {
        return $this->courses()->wherePivot('is_primary_instructor', true);
    }

    /**
     * Payment agreements for this instructor (one per tenant).
     */
    public function paymentAgreements(): HasMany
    {
        return $this->hasMany(InstructorPaymentAgreement::class, 'instructor_id');
    }

    /**
     * Batch payment records (mark-paid entries).
     */
    public function batchPayments(): HasMany
    {
        return $this->hasMany(InstructorBatchPayment::class, 'instructor_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(InstructorAttendance::class);
    }

    public function todayAttendance(): HasOne
    {
        return $this->hasOne(InstructorAttendance::class)->where('date', today());
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function completedSessions(): HasMany
    {
        return $this->classSessions()->where('status', 'completed');
    }

    public function getFullNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full-time');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function calculateMonthlyHours(?int $month = null, ?int $year = null): float
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;

        $totalMinutes = $this->completedSessions()
            ->whereMonth('started_at', $month)
            ->whereYear('started_at', $year)
            ->sum('duration_minutes');

        return round($totalMinutes / 60, 2);
    }

    public function calculateMonthlyEarnings(?int $month = null, ?int $year = null): float
    {
        return round($this->calculateMonthlyHours($month, $year) * $this->hourly_rate, 2);
    }

    public function getStudentCount(): int
    {
        return Student::whereHas('enrollments.course.instructors', function ($query) {
            $query->where('instructor_course.instructor_id', $this->id);
        })->distinct()->count();
    }

    public function isTeaching(int $courseId): bool
    {
        return $this->courses()->where('course_id', $courseId)->exists();
    }
}