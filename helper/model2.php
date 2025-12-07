<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'submission_id',
        'instructor_id',
        'score',
        'max_score',
        'percentage',
        'letter_grade',
        'feedback',
        'graded_at',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'graded_at' => 'datetime',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

 
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePassing($query, float $passingPercentage = 60)
    {
        return $query->where('percentage', '>=', $passingPercentage);
    }

    public function scopeFailing($query, float $passingPercentage = 60)
    {
        return $query->where('percentage', '<', $passingPercentage);
    }

    // Helper Methods
    public function calculatePercentage(): void
    {
        if ($this->max_score > 0) {
            $percentage = ($this->score / $this->max_score) * 100;
            $this->update(['percentage' => round($percentage, 2)]);
        }
    }

    public function calculateLetterGrade(): void
    {
        $percentage = $this->percentage;
        
        $letterGrade = match(true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 70 => 'C',
            $percentage >= 60 => 'D',
            default => 'F',
        };

        $this->update(['letter_grade' => $letterGrade]);
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Fire event to notify student
        // event(new \App\Events\GradePublished($this));
    }

    public function isPassing(float $threshold = 60): bool
    {
        return $this->percentage >= $threshold;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    // use SoftDeletes;

    // fillbales
    
    protected $fillable = [
        "user_id", "instructor_id", "qualification", "specialization", "years_of_experience", "bio", 
        "linkedin_url", "hourly_rate", "employment_type", "hire_date", "status"
    ];

    // casts

    protected $casts = [
        "hire_date" => 'datetime',
        "hourly_rate" => 'decimal:2'
    ];

    // relationships

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function courses(): BelongsToMany {
        return $this->belongsToMany(Course::class, "instructor_course", "instructor_id", "course_id")->withPivot(['assigned_date', 'is_primary_instructor'])->withTimestamps();
    }

    public function primaryCourses(): BelongsToMany {
        return $this->courses()->wherePivot('is_primary_instructor', true);
    }

    public function assignments():HasMany {
        return $this->hasMany(Assignment::class);
    }

    public function attendances():HasMany {
        return $this->hasMany(InstructorAttendance::class);
    }

    public function todayAttendance():HasOne {
        return $this->hasOne(InstructorAttendance::class)
           ->where('date', today());
    }

    public function classSessions(): HasMany {
        return $this->hasMany(classSession::class);
    }

    public function completedSession() : HasMany {
        return $this->classSessions()->where('status', 'completed');
    }

    // access / mutators

    //scopes 
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeInActive($query) {
        return $query->where('status', 'inactive');
    }

    public function scopeFullTime($query) {
        return $query->where('employment_type', 'full-time');
    }

    // helpers
    public function calculateMonthlyHours(?int $month = null , ?int $year = null) {
        $month = $month ?? now()->month();
        $year = $year ?? now()->year();

        $totalMinutes = $this->completedSession()
            ->whereMonth('started_at', $month)
            ->whereYear('started_at', $year)
            ->sum('duration_minutes');

        return round($totalMinutes / 60, 2);
    }

    public function calculateMonthlyEarnings(?int $month = null , ?int $year = null) {
        $hours = $this->calculateMonthlyHours($month, $year);
        return round($hours * $this->hourly_rate, 2);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttendance extends Model
{
    protected $fillable = [ 
        'instructor_id', 'date', 'clock_in', 'clock_out', 'status'
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function instructor(): BelongsTo {
        return $this->belongsTo(Instructor::class);
    }

    public function scopeAbsent() {
        return $this->where('status', 'absent');
    }

    public function scopePresent() {
        return $this->where('status', 'present');
    }

    public function clockIn(): void {
        $this->update([
            'clock_in' => now(),
            'status' => 'present'
        ]);
    }

    public function clockOut(): void {
        $this->update([
            'clock_out' => now(),
            'status' => 'absent'
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorCourse extends Model
{
    protected $table = 'instructor_course';

    protected $fillable = [
        'instructor_id',
        'course_id',
        'assigned_date',
        'is_primary_instructor',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'is_primary_instructor' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'external_url',
        'download_count',
        'is_downloadable',
        'uploaded_at',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_downloadable' => 'boolean',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) return 'N/A';

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    public function hasExternalUrl(): bool
    {
        return !empty($this->external_url);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification_
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'notifiable',
        'message',
        'data',
        'priority',
        'is_read',
        'read_at',
        'channel',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Helper Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }
}

<?php

namespace App\Models;

use App\Models\Student;
use App\Models\ParentModel;
use Illuminate\Database\Eloquent\Model;
use App\Events\ParentAssignmentSubmitted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAssignment extends Model
{

    protected $fillable = [
        'parent_id',
        'student_id',
        'assignment_id',
        'submission_id',
        'parent_notes',
        'attachments',
        'status',
        'uploaded_at',
        'submitted_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'uploaded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
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
    public function submitToInstructor(): bool
    {
        $submission = Submission::create([
            'assignment_id' => $this->assignment_id ?? null,
            'student_id' => $this->student_id,
            'content' => $this->parent_notes ?? 'Submitted by parent',
            'attachments' => $this->attachments,
            'submitted_at' => now(),
            'status' => 'submitted',
            'attempt_number' => 1,
            'is_late' => $this->assignment->due_at->isPast(),
        ]);

        $this->update([
            'submission_id' => $submission->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        event(new ParentAssignmentSubmitted($this));

        return true;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function canEdit(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'submitted' => 'info',
            'graded' => 'success',
            default => 'gray',
        };
    }

    public function getSubmissionStatusText(): string
    {
        return match($this->status) {
            'pending' => 'Not yet submitted',
            'submitted' => 'Submitted to instructor',
            'graded' => 'Graded',
            default => 'Unknown',
        };
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentModel extends Model
{
    use SoftDeletes;

    protected $table = 'parents';
    
    protected $fillable = [
        'user_id', 'parent_id', 'occupation',
        'address', 'city', 'state', 'country',
        'secondary_phone', 'preferred_contact_method',
        'recieves_weekly_report'
    ];

    protected $casts = [
        "recieves_weekly_report" => 'boolean'
    ];
    
    // Relationships
    
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function children(): BelongsToMany {
        return $this->belongsToMany(Student::class, "student_parent", "parent_id", "student_id")
            ->withPivot(["relationship", "is_primary_contact", "can_view_grades", "can_view_attendance"])
            ->withTimestamps();
    }

    public function primaryChildren(): BelongsToMany {
        return $this->children()->wherePivot('is_primary_contact', true);
    }

    public function reports(): HasMany {
        return $this->hasMany(Report::class, "parent_id");
    }

    // NEW: Parent Assignments relationship
    public function parentAssignments(): HasMany {
        return $this->hasMany(ParentAssignment::class, 'parent_id');
    }

    public function pendingAssignments(): HasMany {
        return $this->parentAssignments()->where('status', 'pending');
    }

    // NEW: Child Linking Requests
    public function linkingRequests(): HasMany
    {
        return $this->hasMany(ChildLinkingRequest::class, 'parent_id');
    }

    public function pendingLinkingRequests(): HasMany
    {
        return $this->linkingRequests()->where('status', 'pending');
    }

    public function approvedLinkingRequests(): HasMany
    {
        return $this->linkingRequests()->where('status', 'approved');
    }

    // NEW: Helper to check if linking request exists
    public function hasLinkingRequestFor(int $studentId): bool
    {
        return $this->linkingRequests()
            ->where('student_id', $studentId)
            ->where('status', 'pending')
            ->exists();
    }

    // NEW: Get pending requests count
    public function getPendingLinkingRequestsCount(): int
    {
        return $this->pendingLinkingRequests()->count();
    }


    // Accessors / Mutators

    public function getFullAddressAttribute(): string {
        return trim("{$this->address}, {$this->city}, {$this->state}, {$this->country}");
    }
    
    // Helpers

    public function canViewChildGrades(int $childId): bool {
        return $this->children()
            ->where('student_parent.student_id', $childId)
            ->wherePivot('can_view_grades', true)
            ->exists();
    }

    public function canViewChildAttendance(int $childId): bool {
        return $this->children()
            ->where('student_parent.student_id', $childId)
            ->wherePivot('can_view_attendance', true)
            ->exists();
    }

    public function getChildrenProgress(): array {
        return $this->children->map(function ($child) {
            return [
                "student" => $child->student_id,
                "name" => $child->user->full_name,
                "progress" => $child->calculateOverallProgress(),
                "attendance_rate" => $child->calculateAttendanceRate()
            ];
        })->toArray();
    }

    // NEW: Get pending assignments count
    public function getPendingAssignmentsCount(): int {
        return $this->pendingAssignments()->count();
    }

    // NEW: Get assignments for specific child
    public function getChildAssignments(int $studentId) {
        return $this->parentAssignments()
            ->where('student_id', $studentId)
            ->with(['assignment.course', 'submission.grade'])
            ->get();
    }
}

