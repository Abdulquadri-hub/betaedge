<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activity Log Model
 *
 * Records all user activities and system events for audit trails
 */
class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $table = 'activity_log';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event',
        'log_name',
        'subject_type',
        'subject_id',
        'ip_address',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get activities by specific event
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to get activities for a specific subject
     */
    public function scopeForSubject($query, string $subjectType, int $subjectId)
    {
        return $query->where('subject_type', $subjectType)
                     ->where('subject_id', $subjectId);
    }

    /**
     * Scope to get activities by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->whereBetween('created_at', [
            now()->subDays($days),
            now(),
        ]);
    }

    /**
     * Get the model name being audited
     */
    public function getSubjectModel()
    {
        if (!$this->subject_type) {
            return null;
        }

        try {
            return app($this->subject_type)::find($this->subject_id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get a readable description of the change
     */
    public function getChangeDescription(): string
    {
        if ($this->old_values && $this->new_values) {
            $changes = array_keys(array_diff_assoc($this->new_values, $this->old_values));
            return 'Updated: ' . implode(', ', $changes);
        }

        return $this->description ?? ucfirst($this->event);
    }
}
