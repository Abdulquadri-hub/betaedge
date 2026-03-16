<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * User Notification Pivot Model
 *
 * Manages user-notification relationships with read status and archival
 */
class UserNotification extends Pivot
{
    protected $table = 'user_notifications';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'notification_id',
        'read_at',
        'is_archived',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Archive notification
     */
    public function archive()
    {
        return $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive notification
     */
    public function unarchive()
    {
        return $this->update(['is_archived' => false]);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return !$this->isRead();
    }

    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to get archived notifications
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to get unarchived notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
