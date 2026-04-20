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
        'batch_id',          
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
        'status',           
        'max_participants',
        'actual_participants', 
        'total_enrolled',   
        'recording_url',
        'duration_minutes',
        'notes',            
    ];

    protected $casts = [
        'scheduled_start'    => 'datetime',
        'scheduled_end'      => 'datetime',
        'actual_start'       => 'datetime',
        'actual_end'         => 'datetime',
        'duration_minutes'   => 'integer',
        'max_participants'   => 'integer',
        'actual_participants'=> 'integer',
        'total_enrolled'     => 'integer',
        'session_notes'      => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_session_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_start', '>=', now());
    }

    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function getAttendanceRateAttribute(): ?float
    {
        if (!$this->total_enrolled) return null;
        return round(($this->actual_participants / $this->total_enrolled) * 100, 1);
    }

    public function getUiStatusAttribute(): string
    {
        return $this->status === 'in_progress' ? 'live' : $this->status;
    }


    public function isLive(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function startSession(): void
    {
        $this->update([
            'actual_start' => now(),
            'status'       => 'in_progress',
        ]);
    }

    public function endSession(int $attendees = 0): void
    {
        $started  = $this->actual_start ?? now();
        $ended    = now();
        $duration = $started->diffInMinutes($ended);

        $this->update([
            'actual_end'          => $ended,
            'duration_minutes'    => $duration,
            'status'              => 'completed',
            'actual_participants' => $attendees,
        ]);
    }

    public function cancelSession(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}