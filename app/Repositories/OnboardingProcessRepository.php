<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Tenant;
use App\Models\OnboardingProcess;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use Illuminate\Support\Facades\Log;

class OnboardingProcessRepository implements OnboardingProcessRepositoryInterface
{
    public function create(string $sessionId, array $data): ?OnboardingProcess
    {
        return OnboardingProcess::create([
            'session_id' => $sessionId,
            'profile' => $data['profile'] ?? [],
            'plan' => $data['plan'] ?? [],
            'payment' => $data['payment'] ?? [],
            'status' => 'draft',
            'current_step' => $data['current_step'] ?? 'profile',
            'progress_percentage' => 0
        ]);
    }

    public function update(string $sessionId, array $data): ?OnboardingProcess
    {
        $draft = $this->getBySessionId($sessionId);

        if (!$draft) {
            return $this->create($sessionId, $data);
        }

        $updated = $draft->update([
            'profile' =>  array_merge($draft->profile ?? [], $data['profile'] ?? []),
            'plan' => array_merge($draft->plan ?? [], $data['plan'] ?? []),
            'payment' => array_merge($draft->payment ?? [], $data['payment'] ?? []),
            'current_step' => $data['current_step'] ?? $draft->current_step,
            'updated_at' => now()
        ]);

        if (!$updated) {
            throw new \RuntimeException('Failed to update onboarding draft');
        }

        return $draft->fresh();
    }

    public function delete(string $sessionId): bool
    {
        return OnboardingProcess::where('session_id', $sessionId)
            ->whereIn('status', ['draft', 'failed'])
            ->delete();
    }

    public function getBySessionId(string $sessionId): ?OnboardingProcess
    {
        return OnboardingProcess::where('session_id', $sessionId)
            ->whereIn('status', ['draft', 'processing', 'failed'])
            ->latest()
            ->first();
    }

    public function getById(string $id): ?OnboardingProcess
    {
        return OnboardingProcess::find($id);
    }

    public function getByJobId(string $jobId): ?OnboardingProcess
    {
        return OnboardingProcess::where('job_id', $jobId)->first();
    }

    public function cleanupStaleJobs(int $minutes = 60): int
    {
        return OnboardingProcess::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => 'failed',
                'error_message' => 'Job timed out after ' . $minutes . ' minutes'
            ]);
    }

    public function cleanupFailedOnboarding(OnboardingProcess $onboarding): void
    {
        $profile = $onboarding->getProfile();
        $ownerEmail = $profile['owner_email'] ?? null;

        $tenant = null;

        if ($onboarding->tenant_id) {
            $tenant = Tenant::find($onboarding->tenant_id);
        }

        if (!$tenant && $ownerEmail) {
            $tenant = Tenant::where('owner_email', $ownerEmail)->first();
        }

        if ($tenant) {
            $ownerId = $tenant->owner_id; 
            $tenant->forceDelete();

            if ($ownerId) {
                User::where('id', $ownerId)->forceDelete();
                return;
            }
        }

        if ($ownerEmail) {
            $user = User::where('email', $ownerEmail)->first();
            if ($user) {
                $user->forceDelete();
            }
        }
    }

}
