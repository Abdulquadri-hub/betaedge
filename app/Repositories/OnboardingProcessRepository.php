<?php

namespace App\Repositories;

use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Models\OnboardingProcess;

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

        if(!$draft) {
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
            ->whereIn('status', ['draft', 'processing'])
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
}
