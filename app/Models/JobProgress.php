<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobProgress extends Model
{
    use BelongsToTenant;
    
    protected $table = 'job_progress';

    protected $fillable = [
        'tenant_id',
        'job_id',
        'user_id',
        'status',
        'progress',
        'message',
        'error',
        'metadata',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'progress' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Quick update method
    public static function updateProgress(
        string $jobId,
        int $progress,
        string $message,
        ?int $userId = null
    ): void {
        self::updateOrCreate(
            ['job_id' => $jobId],
            [
                'user_id' => $userId,
                'status' => $progress >= 100 ? 'completed' : 'processing',
                'progress' => $progress,
                'message' => $message,
                'started_at' => $progress === 0 ? now() : null,
                'completed_at' => $progress >= 100 ? now() : null
            ]
        );
    }

    public static function markFailed(string $jobId, string $error): void
    {
        self::updateOrCreate(
            ['job_id' => $jobId],
            [
                'status' => 'failed',
                'error' => $error,
                'completed_at' => now()
            ]
        );
    }

    public static function getProgress(string $jobId): ?array
    {
        $progress = self::where('job_id', $jobId)->first();
        
        if (!$progress) {
            return null;
        }

        return [
            'status' => $progress->status,
            'progress' => $progress->progress,
            'message' => $progress->message,
            'error' => $progress->error,
            'metadata' => $progress->metadata
        ];
    }

    // Cleanup old jobs
    public static function cleanup(int $days = 7): int
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }
}
