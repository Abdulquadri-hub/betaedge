<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentRegistration extends Model
{
    protected $fillable = [
        'student_id',
        'enrollment_request_id',
        'registration_code',
        'parent_first_name',
        'parent_last_name',
        'parent_email',
        'parent_phone',
        'relationship',
        'temporary_password',
        'status',
        'created_parent_id',
        'created_user_id',
        'email_sent_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'email_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'temporary_password',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($registration) {
            // Generate registration code
            if (empty($registration->registration_code)) {
                $registration->registration_code = self::generateRegistrationCode();
            }

            // Set expiry (7 days from now)
            if (empty($registration->expires_at)) {
                $registration->expires_at = now()->addDays(7);
            }
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollmentRequest(): BelongsTo
    {
        return $this->belongsTo(EnrollmentRequest::class);
    }

    public function createdParent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'created_parent_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                  ->where('expires_at', '<', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    // Helper Methods
    public static function generateRegistrationCode(): string
    {
        do {
            $code = 'PREG-' . strtoupper(Str::random(10));
        } while (self::where('registration_code', $code)->exists());

        return $code;
    }

    public static function generateTemporaryPassword(): string
    {
        // Generate a secure random password (8 characters)
        return Str::random(8);
    }

    public function createParentAccount(): bool
    {
        try {
            // Check if email already exists
            $existingUser = User::where('email', $this->parent_email)->first();
            
            if ($existingUser) {
                // Check if it's a parent account
                if ($existingUser->user_type === 'parent' && $existingUser->parent) {
                    // Link existing parent to student
                    $this->linkExistingParent($existingUser->parent);
                    return true;
                }
                
                return false; // Email exists but not a parent
            }

            // Create new user account
            $user = User::create([
                'email' => $this->parent_email,
                'username' => $this->generateUsername(),
                'password' => Hash::make($this->temporary_password),
                'first_name' => $this->parent_first_name,
                'last_name' => $this->parent_last_name,
                'phone' => $this->parent_phone,
                'user_type' => 'parent',
                'status' => 'active',
                'email_verified_at' => null, // Will verify on first login
            ]);

            // Create parent record
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'parent_id' => 'PAR-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            ]);

            // Link parent to student
            $parent->children()->attach($this->student_id, [
                'relationship' => $this->relationship,
                'is_primary_contact' => true, // First parent is primary by default
                'can_view_grades' => true,
                'can_view_attendance' => true,
            ]);

            // Update registration record
            $this->update([
                'created_parent_id' => $parent->id,
                'created_user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Parent account creation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function linkExistingParent(ParentModel $parent): void
    {
        // Check if already linked
        if (!$parent->children()->where('student_id', $this->student_id)->exists()) {
            $parent->children()->attach($this->student_id, [
                'relationship' => $this->relationship,
                'is_primary_contact' => !$this->student->hasLinkedParent(), // Primary if first
                'can_view_grades' => true,
                'can_view_attendance' => true,
            ]);
        }

        // Update registration
        $this->update([
            'created_parent_id' => $parent->id,
            'created_user_id' => $parent->user_id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    protected function generateUsername(): string
    {
        $base = strtolower($this->parent_first_name . $this->parent_last_name);
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        
        $username = $base;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }
        
        return $username;
    }

    public function sendWelcomeEmail(): void
    {
        // Send email notification
        \Illuminate\Support\Facades\Notification::route('mail', $this->parent_email)
            ->notify(new \App\Notifications\ParentAccountCreatedNotification($this, $this->temporary_password));
        
        $this->update(['email_sent_at' => now()]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->status === 'pending' && $this->expires_at->isPast());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getParentFullNameAttribute(): string
    {
        return "{$this->parent_first_name} {$this->parent_last_name}";
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'expired' => 'danger',
            default => 'gray',
        };
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->isExpired()) return 0;
        
        return max(0, $this->expires_at->diffInDays(now()));
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'parent_id',
        'course_id',
        'payment_reference',
        'amount',
        'currency',
        'payment_method',
        'receipt_path',
        'receipt_filename',
        'parent_notes',
        'status',
        'admin_notes',
        'verified_by',
        'verified_at',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Boot method to generate reference
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForParent($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    // Helper Methods
    public static function generatePaymentReference(): string
    {
        do {
            $reference = 'PAY-' . strtoupper(Str::random(8));
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    public function verify(int $adminUserId, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'verified',
            'admin_notes' => $notes,
            'verified_by' => $adminUserId,
            'verified_at' => now(),
        ]);

        // Send notification to uploader
        if ($this->parent_id) {
            // Notify parent
            $this->parent->user->notify(new PaymentVerifiedNotification($this));
        } else {
            // Notify student
            $this->student->user->notify(new PaymentVerifiedNotification($this));
        }

        return true;
    }

    public function reject(int $adminUserId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'verified_by' => $adminUserId,
            'verified_at' => now(),
        ]);

        // Send notification to uploader
        if ($this->parent_id) {
            // Notify parent
            $this->parent->user->notify(new PaymentRejectedNotification($this));
        } else {
            // Notify student
            $this->student->user->notify(new PaymentRejectedNotification($this));
        }
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeVerified(): bool
    {
        return $this->status === 'pending';
    }

    public function hasSubscription(): bool
    {
        return $this->subscription()->exists();
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? asset('storage/' . $this->receipt_path) : null;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Awaiting Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }


    // NEW: Check if payment is from student (not parent)
    public function isStudentPayment(): bool
    {
        return $this->parent_id === null;
    }

    // NEW: Check if payment is from parent
    public function isParentPayment(): bool
    {
        return $this->parent_id !== null;
    }

    // NEW: Get uploader name
    public function getUploaderNameAttribute(): string
    {
        if ($this->parent_id) {
            return $this->parent->user->full_name . ' (Parent)';
        }
        
        return $this->student->user->full_name . ' (Student)';
    }

    // NEW: Get uploader type
    public function getUploaderTypeAttribute(): string
    {
        return $this->parent_id ? 'Parent' : 'Student';
    }
}

<?php

namespace App\Models;

use App\Models\StudentPromotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Student extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'user_id', 'student_id', 'date_of_birth',
        'gender', 'address', 'city', ' state',
        'country', 'emergency_contact_name', 'emergency_contact_phone', 'enrollment_date',
        'enrollment_status', "notes", 'academic_level_id'
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'date_of_birth' => 'datetime',
        'academic_level_id' => 'integer',
    ];

    public function academicLevel(): BelongsTo {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }


    //students can have many parents
    public function parents(): BelongsToMany {
        return $this->belongsToMany(ParentModel::class,"student_parent", "student_id", "parent_id")->withPivot(['relationship', 'is_primary_contact', 'can_view_grades', 'can_view_attendance'])->withTimestamps();
    }

    public function primaryParent(): BelongsToMany {
        return $this->parents()->wherePivot('is_primary_contact', true);
    }

    public function enrollments(): HasMany {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments(): HasMany {
        return $this->enrollments()->where('status', 'active')->orWhere('status', 'completed');
    }

     // NEW: Linking Requests
    public function linkingRequests(): HasMany
    {
        return $this->hasMany(ChildLinkingRequest::class, 'student_id');
    }

    public function pendingLinkingRequests(): HasMany
    {
        return $this->linkingRequests()->where('status', 'pending');
    }

    // NEW: Check if student has pending linking requests
    public function hasPendingLinkingRequests(): bool
    {
        return $this->pendingLinkingRequests()->exists();
    }

    // NEW: Get all parents (both confirmed and pending)
    public function getAllParentRelationships(): array
    {
        $confirmed = $this->parents()->get()->map(function ($parent) {
            return [
                'parent' => $parent,
                'status' => 'confirmed',
                'relationship' => $parent->pivot->relationship,
            ];
        });

        $pending = $this->linkingRequests()->pending()->with('parent.user')->get()->map(function ($request) {
            return [
                'parent' => $request->parent,
                'status' => 'pending',
                'relationship' => $request->relationship,
            ];
        });

        return $confirmed->merge($pending)->toArray();
    }

    // this is same as courses enrolled to 
    public function courses(): BelongsToMany {
        return $this->belongsToMany(Course::class, "enrollments")->withPivot(['enrolled_at', 'status', 'progress_percentage', 'final_grade'])->withTimestamps();
    }

    public function submissions(): HasMany {
        return $this->hasMany(Submission::class);
    }

    public function attendances(): HasMany {
        return $this->hasMany(Attendance::class);
    }

    // public function counselingSessions(): HasMany {
    //     return $this->hasMany(CounsellingSession::class);
    // }

    public function grades(): HasManyThrough {
        return $this->hasManyThrough(Grade::class, Submission::class);
    }

    // accessors() and mutators


    public function getFullAddressAttribute(): string {
        return trim("{$this->address}, {$this->city}, {$this->state}, {$this->country}");
    }

    public function getGradeLevelAttribute(): ?string {
        return $this->academicLevel?->name;
    }


    //scopes

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeGraduated($query) {
        $query->where('enrollment_status', 'graduated');
    }

    public function scopeByGradeLevel($query, int $levelId) {
        return $query->where('academic_level_id', $levelId);
    }
    
    //helpers methods 
    
    public function calculateOverallProgress(): float {
        return $this->activeEnrollments()->avg('progress_percentage') ?? 0.00;
    }

    public function calculateAttendanceRate(): float {
        $total = $this->attendances()->count();
        if($total === 0) {
            return 0;
        }

        $present = $this->attendances()->where('status', 'present')->count();
        return round(($present / $total) * 100 , 2); 
    }

    public function hasParent($parentId) {
        return $this->parents()->where('parent_id', $parentId)->exists();
    }

    public function getCurrentGradeNumber(): ?int
    {
        return $this->academicLevel?->grade_number;
    }

    public function isInElementary(): bool
    {
        return $this->academicLevel?->isElementary() ?? false;
    }

    public function isInMiddle(): bool
    {
        return $this->academicLevel?->isMiddle() ?? false;
    }

    public function isInHigh(): bool
    {
        return $this->academicLevel?->isHigh() ?? false;
    }

    public function canEnrollInCourse(Course $course): bool
    {
        // Check if course is for student's grade level
        if ($course->academic_level_id && $this->academic_level_id) {
            return $course->academic_level_id === $this->academic_level_id;
        }
        
        return true; // Allow enrollment if no level restrictions
    }

    public function getRecommendedCourses()
    {
        return Course::where('academic_level_id', $this->academic_level_id)
                     ->where('status', 'active')
                     ->whereDoesntHave('enrollments', function ($query) {
                         $query->where('student_id', $this->id)
                               ->where('status', 'active');
                     })
                     ->get();
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function pendingEnrollmentRequests(): HasMany
    {
        return $this->enrollmentRequests()->whereIn('status', ['pending', 'parent_notified', 'payment_pending']);
    }

    public function getPendingEnrollmentRequestsCount(): int
    {
        return $this->pendingEnrollmentRequests()->count();
    }

    public function hasPendingRequestFor(int $courseId): bool
    {
        return $this->enrollmentRequests()
            ->where('course_id', $courseId)
            ->whereIn('status', ['pending', 'parent_notified', 'payment_pending'])
            ->exists();
    }

     // NEW: Student Promotions
    public function promotions(): HasMany
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function completedPromotions(): HasMany
    {
        return $this->promotions()->where('status', 'completed');
    }

    public function pendingPromotions(): HasMany
    {
        return $this->promotions()->where('status', 'pending');
    }

    public function latestPromotion(): HasOne
    {
        return $this->hasOne(StudentPromotion::class)->latestOfMany('promotion_date');
    }


    public function getPromotionHistory(): array
    {
        return $this->promotions()
            ->with(['fromLevel', 'toLevel', 'promoter'])
            ->orderBy('promotion_date', 'desc')
            ->get()
            ->map(function ($promotion) {
                return [
                    'code' => $promotion->promotion_code,
                    'from' => $promotion->fromLevel?->name,
                    'to' => $promotion->toLevel->name,
                    'type' => $promotion->promotion_type,
                    'date' => $promotion->promotion_date,
                    'status' => $promotion->status,
                    'promoted_by' => $promotion->promoter->full_name,
                ];
            })
            ->toArray();
    }

    // NEW: Check if student has pending promotion
    public function hasPendingPromotion(): bool
    {
        return $this->pendingPromotions()->exists();
    }

    // NEW: Promote student
    public function promote(
        int $toLevelId, 
        int $promotedBy,
        string $type = 'regular',
        ?string $academicYear = null,
        ?float $finalGpa = null,
        ?string $notes = null,
        bool $autoUpdateEnrollments = true
    ): StudentPromotion {
        return StudentPromotion::create([
            'student_id' => $this->id,
            'from_level_id' => $this->academic_level_id,
            'to_level_id' => $toLevelId,
            'promotion_type' => $type,
            'academic_year' => $academicYear,
            'final_gpa' => $finalGpa,
            'promotion_notes' => $notes,
            'promoted_by' => $promotedBy,
            'status' => 'pending',
            'auto_update_enrollments' => $autoUpdateEnrollments,
        ]);
    }

    // NEW: Get next grade level
    public function getNextGradeLevel(): ?AcademicLevel
    {
        return $this->academicLevel?->getNextLevel();
    }

    // NEW: Can be promoted
    public function canBePromoted(): array
    {
        if (!$this->academic_level_id) {
            return ['can_promote' => false, 'reason' => 'No current grade level assigned'];
        }

        if ($this->hasPendingPromotion()) {
            return ['can_promote' => false, 'reason' => 'Promotion already pending'];
        }

        $nextLevel = $this->getNextGradeLevel();
        if (!$nextLevel) {
            return ['can_promote' => false, 'reason' => 'Already at highest grade level'];
        }

        if ($this->enrollment_status !== 'active') {
            return ['can_promote' => false, 'reason' => 'Student is not active'];
        }

        return ['can_promote' => true, 'reason' => null, 'next_level' => $nextLevel];
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    // NEW: Check if student is a minor (under 18)
    public function isMinor(): bool
    {
        if (!$this->date_of_birth) {
            // If no date of birth, assume minor for safety
            return true;
        }
        
        return $this->age < 18;
    }

    // NEW: Check if student is an adult (18 or older)
    public function isAdult(): bool
    {
        if (!$this->date_of_birth) {
            // If no date of birth, assume minor for safety
            return false;
        }
        
        return $this->age >= 18;
    }

    // NEW: Check if student has any linked parents
    public function hasLinkedParent(): bool
    {
        return $this->parents()->exists();
    }

    // NEW: Check if student has verified parent (approved linking request)
    public function hasVerifiedParent(): bool
    {
        return $this->parents()->wherePivot('is_primary_contact', true)->exists() 
               || $this->parents()->exists();
    }

    // NEW: Get primary parent (or first parent if no primary)
    public function getPrimaryParent(): ?ParentModel
    {
        $primary = $this->parents()->wherePivot('is_primary_contact', true)->first();
        
        if ($primary) {
            return $primary;
        }
        
        return $this->parents()->first();
    }

    // NEW: Determine enrollment request routing
    public function getEnrollmentRequestRoute(): string
    {
        // Adult student - they handle payment themselves
        if ($this->isAdult()) {
            return 'student_payment';
        }
        
        // Minor with parent - notify parent
        if ($this->isMinor() && $this->hasLinkedParent()) {
            return 'parent_payment';
        }
        
        // Minor without parent - needs parent info
        if ($this->isMinor() && !$this->hasLinkedParent()) {
            return 'parent_registration';
        }
        
        // Fallback
        return 'admin_review';
    }

    // NEW: Can request enrollment check
    public function canRequestEnrollment(int $courseId): array
    {
        // Check if already enrolled
        if ($this->courses()->where('course_id', $courseId)->exists()) {
            return ['can_request' => false, 'reason' => 'Already enrolled in this course'];
        }
        
        // Check if pending request exists
        if ($this->hasPendingRequestFor($courseId)) {
            return ['can_request' => false, 'reason' => 'Enrollment request already pending'];
        }
        
        // Check if student is active
        if ($this->enrollment_status !== 'active') {
            return ['can_request' => false, 'reason' => 'Student account is not active'];
        }
        
        // Get routing info
        $route = $this->getEnrollmentRequestRoute();
        
        return [
            'can_request' => true,
            'reason' => null,
            'route' => $route,
            'is_adult' => $this->isAdult(),
            'is_minor' => $this->isMinor(),
            'has_parent' => $this->hasLinkedParent(),
        ];
    }

}
