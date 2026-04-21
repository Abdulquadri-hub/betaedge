<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchCourse extends Model
{
    use BelongsToTenant;

    protected $table = 'batch_courses';

    protected $fillable = [
        'tenant_id',
        'batch_id',
        'course_id',
        'instructor_id',
        'session_day',
        'session_time',
        'session_duration_minutes',
        'session_platform',
        'session_frequency',
        'display_order',
    ];

    protected $casts = [
        'session_duration_minutes' => 'integer',
        'display_order'            => 'integer',
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

    public function getScheduleSummaryAttribute(): string
    {
        $parts = array_filter([
            $this->session_day,
            $this->session_time ? $this->fmtTime($this->session_time) : null,
            $this->session_duration_minutes ? "{$this->session_duration_minutes}min" : null,
        ]);

        return implode(' · ', $parts) ?: 'No schedule set';
    }

    public function getPlatformLabelAttribute(): string
    {
        return match ($this->session_platform) {
            'google_meet' => 'Google Meet',
            'zoom'        => 'Zoom',
            'jitsi'       => 'Jitsi Meet',
            'teams'       => 'Microsoft Teams',
            'custom'      => 'Custom link',
            default       => $this->session_platform ?? '—',
        };
    }

    private function fmtTime(string $t): string
    {
        [$h, $m] = explode(':', $t);
        $hour    = (int) $h;
        return ($hour % 12 ?: 12) . ':' . $m . ' ' . ($hour >= 12 ? 'PM' : 'AM');
    }
}