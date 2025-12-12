<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProcess extends Model
{
    protected $fillable = [
        'session_id',
        'tenant_id',
        'profile',
        'plan',
        'payment',
        'status',
        'current_step',
        'progress_percentage',
        'progress_message',
        'job_id',
        'error_message',
        'completed_at'
    ];

    protected $casts = [
        'profile' => 'array',
        'plan' => 'array',
        'payment' => 'array',
    ];

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

    public function belongsToSession(string $sessionId): bool {
        return $this->session_id === $sessionId;
    }

    public function updateStatus(Tenant $tenant, string $status): void {
        $this->update([
            'tenant_id' => $tenant->id,
            'status' => $status
        ]);
    }

     public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeStale($query, int $minutes = 60)
    {
        return $query->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes($minutes));
    }

    public function getProfile(): ?array {
        return $this->profile ?? [];
    }

    public function getPlan(): ?array {
        return $this->plan ?? [];
    }

    public function getPayment(): ?array {
        return $this->payment ?? [];
    }

    public function isComplete(): bool
    {
        return !empty($this->profile) && 
               !empty($this->plan) && 
               ($this->isFreePlan() || !empty($this->payment));
    }

    public function isFreePlan(): bool
    {
        $plan = $this->getPlan();
        return isset($plan['is_free']) && $plan['is_free'] === true;
    }

    public function updateProgress(int $percentage, string $message): void
    {
        $this->update([
            'progress_percentage' => $percentage,
            'progress_message' => $message,
            'updated_at' => now()
        ]);
    }

    public function markAsProcessing(string $jobId): void
    {
        $this->update([
            'status' => 'processing',
            'job_id' => $jobId,
            'progress_percentage' => 0,
            'progress_message' => 'Starting onboarding process...',
            'error_message' => null
        ]);
    }

    public function markAsCompleted(int $tenantId): void
    {
        $this->update([
            'status' => 'completed',
            'tenant_id' => $tenantId,
            'progress_percentage' => 100,
            'progress_message' => 'Onboarding completed successfully!',
            'completed_at' => now(),
            'error_message' => null
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'progress_message' => 'Onboarding failed. Please try again.'
        ]);
    }

}
