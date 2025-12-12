<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAnalytic extends Model
{
    protected $fillable = [
        'tenant_id',
        'date',
        'active_students',
        'active_instructors',
        'total_enrollments',
        'new_enrollments',
        'completed_courses',
        'revenue',
        'class_sessions_held',
        'average_attendance_rate',
        'storage_used_bytes',
    ];

    protected $casts = [
        'date' => 'date',
        'active_students' => 'integer',
        'active_instructors' => 'integer',
        'total_enrollments' => 'integer',
        'new_enrollments' => 'integer',
        'completed_courses' => 'integer',
        'revenue' => 'decimal:2',
        'class_sessions_held' => 'integer',
        'average_attendance_rate' => 'decimal:2',
        'storage_used_bytes' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function captureSnapshot(Tenant $tenant): void
    {
        $date = today();

        self::updateOrCreate(
            ['tenant_id' => $tenant->id, 'date' => $date],
            [
                'active_students' => $tenant->students()->where('enrollment_status', 'active')->count(),
                'active_instructors' => $tenant->instructors()->where('status', 'active')->count(),
                'total_enrollments' => Enrollment::where('tenant_id', $tenant->id)->count(),
                'new_enrollments' => Enrollment::where('tenant_id', $tenant->id)
                    ->whereDate('enrolled_at', $date)
                    ->count(),
                'completed_courses' => Enrollment::where('tenant_id', $tenant->id)
                    ->where('status', 'completed')
                    ->count(),
                'class_sessions_held' => ClassSession::where('tenant_id', $tenant->id)
                    ->whereDate('started_at', $date)
                    ->where('status', 'completed')
                    ->count(),
                'average_attendance_rate' => self::calculateAttendanceRate($tenant, $date),
                'storage_used_bytes' => $tenant->calculateStorageUsage(),
            ]
        );
    }

    protected static function calculateAttendanceRate(Tenant $tenant, $date): float
    {
        $sessions = ClassSession::where('tenant_id', $tenant->id)
            ->whereDate('started_at', $date)
            ->where('status', 'completed')
            ->get();

        if ($sessions->isEmpty()) return 0;

        $totalRate = 0;
        foreach ($sessions as $session) {
            $enrolled = $session->course->activeEnrollments()->count();
            if ($enrolled > 0) {
                $attended = $session->attendances()->where('status', 'present')->count();
                $totalRate += ($attended / $enrolled) * 100;
            }
        }

        return round($totalRate / $sessions->count(), 2);
    }
}