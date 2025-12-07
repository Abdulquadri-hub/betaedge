<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParent extends Model
{
    protected $table = 'student_parent';

    protected $fillable = [
        'student_id',
        'parent_id',
        'relationship',
        'is_primary_contact',
        'can_view_grades',
        'can_view_attendance',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'can_view_grades' => 'boolean',
        'can_view_attendance' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Events\StudentPromoted;
use App\Events\PromotionRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'student_id',
        'from_level_id',
        'to_level_id',
        'promotion_code',
        'promotion_type',
        'academic_year',
        'final_gpa',
        'promotion_notes',
        'status',
        'rejection_reason',
        'promoted_by',
        'approved_by',
        'promotion_date',
        'approved_at',
        'effective_date',
        'auto_update_enrollments',
    ];

    protected $casts = [
        'final_gpa' => 'decimal:2',
        'promotion_date' => 'datetime',
        'approved_at' => 'datetime',
        'effective_date' => 'datetime',
        'auto_update_enrollments' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Boot method to generate promotion code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($promotion) {
            if (empty($promotion->promotion_code)) {
                $promotion->promotion_code = self::generatePromotionCode();
            }

            // Set effective date if not provided
            if (empty($promotion->effective_date)) {
                $promotion->effective_date = now();
            }
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'from_level_id');
    }

    public function toLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'to_level_id');
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeRegular($query)
    {
        return $query->where('promotion_type', 'regular');
    }

    // Helper Methods
    public static function generatePromotionCode(): string
    {
        do {
            $code = 'PROM-' . strtoupper(Str::random(8));
        } while (self::where('promotion_code', $code)->exists());

        return $code;
    }

    public function approve(int $adminUserId): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $adminUserId,
            'approved_at' => now(),
        ]);

        // Execute the promotion
        return $this->execute();
    }

    public function reject(int $adminUserId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $adminUserId,
            'approved_at' => now(),
        ]);

        event(new PromotionRejected($this));
    }

    public function execute(): bool
    {
        // Update student's academic level
        $this->student->update([
            'academic_level_id' => $this->to_level_id,
        ]);

        // Update status to completed
        $this->update(['status' => 'completed']);

        // Update enrollments if requested
        if ($this->auto_update_enrollments) {
            $this->updateEnrollments();
        }

        event(new StudentPromoted($this));

        return true;
    }

    protected function updateEnrollments(): void
    {
        // Get active enrollments
        $activeEnrollments = $this->student->enrollments()
            ->where('status', 'active')
            ->get();

        foreach ($activeEnrollments as $enrollment) {
            $course = $enrollment->course;
            
            // Check if course requires specific grade level
            if ($course->academic_level_id) {
                // If course level doesn't match new level, complete the enrollment
                if ($course->academic_level_id !== $this->to_level_id) {
                    $enrollment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'notes' => 'Completed due to grade level promotion',
                    ]);
                }
            }
        }
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function isPromotion(): bool
    {
        if (!$this->from_level_id) return true;
        
        return $this->toLevel->grade_number > $this->fromLevel->grade_number;
    }

    public function isDemotion(): bool
    {
        if (!$this->from_level_id) return false;
        
        return $this->toLevel->grade_number < $this->fromLevel->grade_number;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Awaiting Approval',
            'approved' => 'Approved',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getPromotionTypeTextAttribute(): string
    {
        return match($this->promotion_type) {
            'regular' => 'Regular Promotion',
            'skip' => 'Skip Grade',
            'repeat' => 'Repeat Grade',
            'transfer' => 'Transfer',
            'manual' => 'Manual Promotion',
            default => ucfirst($this->promotion_type),
        };
    }

    public function getLevelChangeAttribute(): string
    {
        $from = $this->fromLevel?->name ?? 'None';
        $to = $this->toLevel->name;
        
        return "{$from} → {$to}";
    }
}

<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'attachments',
        'submitted_at',
        'is_late',
        'status',
        'attempt_number',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'attempt_number' => 'integer',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function assignment(): BelongsTo {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function grade(): HasOne {
        return $this->hasOne(Grade::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

      // Helper Methods
    public function updateProgress(): void
    {
        $totalAssignments = $this->course->assignments()->where('status', 'published')->count();
        
        if ($totalAssignments === 0) {
            $this->update(['progress_percentage' => 0]);
            return;
        }

        $completedAssignments = Submission::where('student_id', $this->student_id)
            ->whereHas('assignment', function ($query) {
                $query->where('course_id', $this->course_id)
                      ->where('status', 'published');
            })
            ->where('status', 'graded')
            ->count();

        $progress = round(($completedAssignments / $totalAssignments) * 100, 2);
        $this->update(['progress_percentage' => $progress]);
    }

    public function markCompleted(float $finalGrade): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'final_grade' => $finalGrade,
            'progress_percentage' => 100,
        ]);
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_id',
        'payment_id',
        'subscription_code',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'total_sessions',
        'sessions_attended',
        'sessions_remaining',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_sessions' => 'integer',
        'sessions_attended' => 'integer',
        'sessions_remaining' => 'integer',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Boot method to generate subscription code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->subscription_code)) {
                $subscription->subscription_code = self::generateSubscriptionCode();
            }

            // Calculate total sessions based on frequency and duration
            if (empty($subscription->total_sessions)) {
                $subscription->calculateTotalSessions();
            }

            // Initialize sessions remaining
            if (empty($subscription->sessions_remaining)) {
                $subscription->sessions_remaining = $subscription->total_sessions;
            }
        });

        static::created(function ($subscription) {
            event(new SubscriptionCreated($subscription));
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'active')
                  ->where('end_date', '<', now());
            });
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Helper Methods
    public static function generateSubscriptionCode(): string
    {
        do {
            $code = 'SUB-' . strtoupper(Str::random(10));
        } while (self::where('subscription_code', $code)->exists());

        return $code;
    }

    public function calculateTotalSessions(): void
    {
        $weeks = $this->start_date->diffInWeeks($this->end_date);
        
        $sessionsPerWeek = match($this->frequency) {
            '3x_weekly' => 3,
            '5x_weekly' => 5,
            default => 3,
        };

        $this->total_sessions = $weeks * $sessionsPerWeek;
    }

    public function recordAttendance(): void
    {
        $this->increment('sessions_attended');
        $this->decrement('sessions_remaining');
        
        // Check if all sessions are used
        if ($this->sessions_remaining <= 0) {
            $this->markAsExpired('All sessions completed');
        }
    }

    public function checkExpiry(): void
    {
        if ($this->end_date->isPast() && $this->status === 'active') {
            $this->markAsExpired('Subscription period ended');
            event(new SubscriptionExpired($this));
        }
    }

    public function checkExpiryWarning(int $daysWarning = 7): void
    {
        if ($this->isExpiringSoon($daysWarning) && $this->status === 'active') {
            $daysRemaining = $this->end_date->diffInDays(now());
            event(new SubscriptionExpiring($this, $daysRemaining));
        }
    }

    public function markAsExpired(?string $reason = null): void
    {
        $this->update([
            'status' => 'expired',
            'notes' => $reason ?? $this->notes,
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function reactivate(): void
    {
        if ($this->end_date->isFuture()) {
            $this->update(['status' => 'active']);
        }
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_date->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->status === 'active' && 
               $this->end_date->isBetween(now(), now()->addDays($days));
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, $this->end_date->diffInDays(now()));
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_sessions === 0) return 0;
        
        return round(($this->sessions_attended / $this->total_sessions) * 100, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'expired' => 'danger',
            'cancelled' => 'warning',
            'suspended' => 'gray',
            default => 'gray',
        };
    }

    public function getFrequencyTextAttribute(): string
    {
        return match($this->frequency) {
            '3x_weekly' => '3 times per week',
            '5x_weekly' => '5 times per week',
            default => $this->frequency,
        };
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'username',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'user_type',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        // if($this->user_type === "admin") {
        //     return true;
        // }

        return $this->user_type === $panelId;
    }

    public function student(): HasOne {
        return $this->hasOne(Student::class);
    }

    public function instructor(): HasOne {
        return $this->hasOne(Instructor::class);
    }

    public function parent(): HasOne {
        return $this->hasOne(ParentModel::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function getFullNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getNameAttribute(): string {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type) {
        return $query->where('user_type', $type);
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isInstructor(): bool
    {
        return $this->user_type === 'instructor';
    }

    public function isStudent(): bool
    {
        return $this->user_type === 'student';
    }

    public function isParent(): bool
    {
        return $this->user_type === 'parent';
    }
}
