<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSession extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'course_id',
        'instructor_id',
        'title',
        'description',
        'session_type',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'status',
        'max_participants',
        'actual_participants',
        'recording_url',
        'duration_minutes',
        'session_notes',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'actual_participants' => 'integer',
        'session_notes' => 'array',
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
        return $query->where('scheduled_start', '>', now())
                     ->where('status', 'scheduled');
    }

    public function scopeToday($query) {
        return $query->whereDate('scheduled_start', today());
    }

    public function startSession(): void {
        $this->update([
            'actual_start' => now(),
            'status' => 'in_progress'
        ]);
    }

    public function endSession(): void {
        $started = $this->actual_start;
        $ended = now();

        $duration = $started ? $started->diffInMinutes($ended) : 0;

        $this->update([
            'actual_end' => $ended,
            'duration_minutes' => $duration,
            'status' => 'completed',
        ]);
    }

    public function cancelSession(): void {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    public function isInProgress(): bool {
        return $this->status === "in_progress";
    }

    public function canStart(): bool {
        return $this->status === "scheduled"
            && $this->scheduled_start
            && $this->scheduled_start->isPast();
    }
}
