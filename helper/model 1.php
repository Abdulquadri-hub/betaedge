<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends Model
{

    protected $fillable = [
        'name',
        'grade_number',
        'description',
        'level_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'grade_number' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeElementary($query)
    {
        return $query->where('level_type', 'elementary');
    }

    public function scopeMiddle($query)
    {
        return $query->where('level_type', 'middle');
    }

    public function scopeHigh($query)
    {
        return $query->where('level_type', 'high');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('grade_number');
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} (Grade {$this->grade_number})";
    }

    // Helper Methods
    public function isElementary(): bool
    {
        return $this->level_type === 'elementary';
    }

    public function isMiddle(): bool
    {
        return $this->level_type === 'middle';
    }

    public function isHigh(): bool
    {
        return $this->level_type === 'high';
    }

    public function getNextLevel(): ?self
    {
        return self::where('grade_number', $this->grade_number + 1)
                   ->where('is_active', true)
                   ->first();
    }

    public function getPreviousLevel(): ?self
    {
        return self::where('grade_number', $this->grade_number - 1)
                   ->where('is_active', true)
                   ->first();
    }

    public function getActiveStudentsCount(): int
    {
        return $this->students()
                    ->where('enrollment_status', 'active')
                    ->count();
    }

    public function getActiveCoursesCount(): int
    {
        return $this->courses()
                    ->where('status', 'active')
                    ->count();
    }
} 


<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    use SoftDeletes;

       protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'description',
        'instructions',
        'assigned_at',
        'due_at',
        'max_score',
        'type',
        'allows_late_submission',
        'late_penalty_percentage',
        'attachments',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'max_score' => 'integer',
        'allows_late_submission' => 'boolean',
        'late_penalty_percentage' => 'integer',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function course(): BelongsTo {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo {
        return $this->belongsTo(Instructor::class);
    }

    public function submissions(): HasMany {
        return $this->hasMany(Submission::class);
    }

    public function grades(): HasManyThrough {
        return $this->hasManyThrough(Grade::class, Submission::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())
                    ->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_at', '>', now())
                    ->where('status', 'published');
    }

    // Helper Methods
    public function isOverdue(): bool
    {
        return $this->due_at->isPast();
    }

    public function getDaysUntilDue(): int
    {
        return $this->due_at->diffInDays(now(), false);
    }

    public function getSubmissionCount(): int
    {
        return $this->submissions()->count();
    }

    public function getGradedCount(): int
    {
        return $this->submissions()->where('status', 'graded')->count();
    }

    public function getAverageScore(): float
    {
        return $this->grades()
            ->where('is_published', true)
            ->avg('percentage') ?? 0;
    }

    public function hasStudentSubmitted(int $studentId): bool
    {
        return $this->submissions()
            ->where('student_id', $studentId)
            ->exists();
    }

}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'class_session_id',
        'student_id',
        'instructor_id',
        'status',
        'joined_at',
        'left_at',
        'duration_minutes',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'duration_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function classSession(): BelongsTo {
        return $this->belongsTo(classSession::class);
    }

    public function student(): BelongsTo {
        return $this->belongsTo(student::class);
    }

    public function instructor(): BelongsTo {
        return $this->belongsTo(Instructor::class);
    }

    public function scopePesent($query) {
        $query->where('status', 'present');
    }

    public function scopeAbsent($query){
        return $query->where('status', 'absent');
    }

    //helpers

    public function markPresent(): void {
        $this->update([
            'status' => 'present',
            'joined_at' => now()
        ]);
    }

    public function markAbsent(): void {
        $this->update([
            'status' => 'absent',
        ]);
    }

    public function recordExit(): void {
        if(!$this->joined_at) return;

        $duration = $this->joined_at->diffInMinutes(now());

        $this->update([
            'left_at' => now(),
            'duration_minutes' => $duration
        ]);
    }

}

<?php

namespace App\Models;

use App\Events\ChildLinkingApproved;

use App\Events\ChildLinkingRejected;
use Illuminate\Database\Eloquent\Model;
use App\Events\ChildLinkingRequestCreated;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildLinkingRequest extends Model
{
    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship',
        'is_primary_contact',
        'can_view_grades',
        'can_view_attendance',
        'parent_message',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'can_view_grades' => 'boolean',
        'can_view_attendance' => 'boolean',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($request) {
            event(new ChildLinkingRequestCreated($request));
        });
    }

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForParent($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // Helper Methods
    public function approve(int $adminUserId, ?string $notes = null): bool
    {
        // Check if already linked
        if ($this->parent->children()->where('student_parent.student_id', $this->student_id)->exists()) {
            $this->update([
                'status' => 'rejected',
                'admin_notes' => 'Already linked',
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);
            return false;
        }

        // Create the link in student_parent pivot table
        $this->parent->children()->attach($this->student_id, [
            'relationship' => $this->relationship,
            'is_primary_contact' => $this->is_primary_contact,
            'can_view_grades' => $this->can_view_grades,
            'can_view_attendance' => $this->can_view_attendance,
        ]);

        // Update request status
        $this->update([
            'status' => 'approved',
            'admin_notes' => $notes,
            'reviewed_by' => $adminUserId,
            'reviewed_at' => now(),
        ]);


        event(new ChildLinkingApproved($this));

        return true;
    }

    public function reject(int $adminUserId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'reviewed_by' => $adminUserId,
            'reviewed_at' => now(),
        ]);

        // TODO: Send notification to parent
        event(new ChildLinkingRejected($this));
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Awaiting Admin Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSession extends Model
{
    protected $fillable = [
        'course_id','instructor_id','title','description','scheduled_at','started_at','ended_at','duration_minutes','google_meet_link','google_calendar_event_id','status','notes','max_participants',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
    ];

    public function course(): BelongsTo {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo {
        return $this->belongsTo(Instructor::class);
    }

    public function attendances(): HasMany {
        return $this->hasMany(Attendance::class);
    }

    public function scopeScheduled($query) {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query) {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query) {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopeToday($query) {
        return $query->whereDate('scheduled_at', today());
    }

    public function startSession(): void {
        $this->update([
            'started_at' => now(),
            'status' => 'in-progress'
        ]);
    }

    public function endSession(): void {
        $started = $this->started_at;
        $ended = now();
        $duration = $started->diffInMinutes($ended);

        $this->update([
            'ended_at' => $ended,
            'duration_minutes' => $duration,
            'status' => 'completed',
        ]);
    }

    public function cancelSession(): void {
        $this->update(['status' => 'cancelled']);
    }

    public function isInprogress(): bool {
        return $this->status === "in-progress";
    }

    public function canStart(): bool {
        return $this->status === "scheduled" && $this->scheduled_at->isPast();
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    // use SoftDeletes;
    
    protected $fillable = [
        "course_code", "title", "description", "category", 
        "level", "duration_weeks", "credit_hours", "price", "thumbnail", "learning_objectives", "prerequisites", "status", "max_students", "academic_level_id"
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_weeks' => 'integer',
        'learning_objectives' => 'array',
        'credit_hours' => 'integer',
        'max_students' => 'integer',
    ];

    // relationships

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

    public function assignments(): HasMany {
        return $this->hasMany(Assignment::class);
    }

    public function materials(): HasMany {
        return $this->hasMany(Material::class);
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

    // NEW: Enrollment Requests
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

    // NEW: Get enrollment requests count
    public function getPendingEnrollmentRequestsCount(): int
    {
        return $this->pendingEnrollmentRequests()->count();
    }

    // NEW: Check if course is full
    public function isFull(): bool
    {
        if (!$this->max_students) return false;
        
        return $this->activeEnrollments()->count() >= $this->max_students;
    }

    // NEW: Get available spots
    public function getAvailableSpots(): ?int
    {
        if (!$this->max_students) return null; // Unlimited
        
        $current = $this->activeEnrollments()->count();
        return max(0, $this->max_students - $current);
    }

    // NEW: Check if student can enroll
    public function canStudentEnroll(Student $student): array
    {
        // Check if already enrolled
        if ($this->students()->where('student_id', $student->id)->exists()) {
            return ['can_enroll' => false, 'reason' => 'Already enrolled'];
        }

        // Check pending requests
        if ($this->enrollmentRequests()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'parent_notified', 'payment_pending'])
                ->exists()) {
            return ['can_enroll' => false, 'reason' => 'Request already pending'];
        }

        // Check if course is full
        if ($this->isFull()) {
            return ['can_enroll' => false, 'reason' => 'Course is full'];
        }

        // Check grade level match
        if ($this->academic_level_id && $student->academic_level_id) {
            if ($this->academic_level_id !== $student->academic_level_id) {
                return ['can_enroll' => false, 'reason' => 'Grade level mismatch'];
            }
        }

        return ['can_enroll' => true, 'reason' => null];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    protected $fillable = [
        "student_id", "course_id", "enrolled_at", "completed", "status", "progress_percentage", "final_grade", "notes"
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


<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Events\EnrollmentApproved;
use App\Events\EnrollmentRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Events\EnrollmentRequestCreated;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Notifications\EnrollmentRequestAdminReview;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Notifications\EnrollmentRequestStudentPayment;

class EnrollmentRequest extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_id',
        'request_code',
        'frequency_preference',
        'quoted_price',
        'currency',
        'student_message',
        'status',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'enrollment_id',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Boot method to generate request code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_code)) {
                $request->request_code = self::generateRequestCode();
            }

            // Set quoted price based on frequency
            if (empty($request->quoted_price) && $request->course) {
                $request->quoted_price = match($request->frequency_preference) {
                    '3x_weekly' => $request->course->price_3x_weekly ?? 80,
                    '5x_weekly' => $request->course->price_5x_weekly ?? 100,
                    default => 0,
                };
            }
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function parentRegistration(): HasOne
    {
        return $this->hasOne(ParentRegistration::class);
    }


    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeParentNotified($query)
    {
        return $query->where('status', 'parent_notified');
    }

    public function scopePaymentPending($query)
    {
        return $query->where('status', 'payment_pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'parent_notified', 'payment_pending']);
    }

    // Helper Methods
    public static function generateRequestCode(): string
    {
        do {
            $code = 'ENR-' . strtoupper(Str::random(8));
        } while (self::where('request_code', $code)->exists());

        return $code;
    }

    public function notifyParent(): void
    {
        $student = $this->student;
        
        // Check routing
        $route = $student->getEnrollmentRequestRoute();
        
        switch ($route) {
            case 'parent_payment':
                // Student has parent - notify them
                $this->update(['status' => 'parent_notified']);
                
                event(new EnrollmentRequestCreated($this));
                break;
                
            case 'student_payment':
                $this->update(['status' => 'payment_pending']);
                
                $student->user->notify(new EnrollmentRequestStudentPayment($this));
                break;
                
            case 'parent_registration':
                $this->update(['status' => 'pending']);
                break;
                
            default:
                $this->update(['status' => 'pending']);
                
                User::where('user_type', 'admin')->each(function($admin) {
                    // $admin->notify(new EnrollmentRequestAdminReview($this));
                });
                break;
        }
  
    }

    public function markPaymentPending(): void
    {
        $this->update(['status' => 'payment_pending']);
    }

    public function approve(int $adminUserId): bool
    {
        // Check if already enrolled
        if ($this->student->courses()->where('course_id', $this->course_id)->exists()) {
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => 'Student is already enrolled in this course',
                'processed_by' => $adminUserId,
                'processed_at' => now(),
            ]);
            return false;
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'enrolled_at' => now(),
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        // Update request
        $this->update([
            'status' => 'approved',
            'enrollment_id' => $enrollment->id,
            'processed_by' => $adminUserId,
            'processed_at' => now(),
        ]);

       event(new EnrollmentApproved($this));

        return true;
    }

    public function reject(int $adminUserId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'processed_by' => $adminUserId,
            'processed_at' => now(),
        ]);

        event(new EnrollmentRejected($this));
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'processed_at' => now(),
        ]);

        $this->delete();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isParentNotified(): bool
    {
        return $this->status === 'parent_notified';
    }

    public function isPaymentPending(): bool
    {
        return $this->status === 'payment_pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'parent_notified', 'payment_pending']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'parent_notified' => 'info',
            'payment_pending' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Review',
            'parent_notified' => 'Parent Notified',
            'payment_pending' => 'Awaiting Payment',
            'approved' => 'Approved & Enrolled',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->quoted_price, 2);
    }

    public function getFrequencyTextAttribute(): string
    {
        return match($this->frequency_preference) {
            '3x_weekly' => '3 times per week',
            '5x_weekly' => '5 times per week',
            default => $this->frequency_preference,
        };
    }

    public function createParentAccountFromInfo(array $parentInfo): ParentRegistration
    {
        return DB::transaction(function () use ($parentInfo) {

        $tempPassword = ParentRegistration::generateTemporaryPassword();
        
        // Create registration record
        $registration = ParentRegistration::create([
            'student_id' => $this->student_id,
            'enrollment_request_id' => $this->id,
            'parent_first_name' => $parentInfo['first_name'],
            'parent_last_name' => $parentInfo['last_name'],
            'parent_email' => $parentInfo['email'],
            'parent_phone' => $parentInfo['phone'] ?? null,
            'relationship' => $parentInfo['relationship'],
            'temporary_password' => $tempPassword,
            'status' => 'pending',
        ]);
        
        // Create the actual parent account
        $registration->createParentAccount();
        
        // Send welcome email
        $registration->sendWelcomeEmail();

        //notify admin
        
        
        // Update enrollment request status
        $this->update(['status' => 'parent_notified']);
        
        return $registration;
        });
    }

    // NEW: Check if has parent registration
    public function hasParentRegistration(): bool
    {
        return $this->parentRegistration()->exists();
    }

    // NEW: Get parent registration status
    public function getParentRegistrationStatus(): ?string
    {
        $registration = $this->parentRegistration;
        
        if (!$registration) return null;
        
        return $registration->status;
    }

}

