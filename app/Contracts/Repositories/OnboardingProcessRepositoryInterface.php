<?php

namespace App\Contracts\Repositories;

use App\Models\OnboardingProcess;

interface OnboardingProcessRepositoryInterface
{
    public function getBySessionId(int $sessionId): ?OnboardingProcess;
    public function getByJobId(int $jobId): ?OnboardingProcess;
    public function create(int $sessionId, array $data): ?OnboardingProcess;
    public function update(int $sessionId, array $data): ?OnboardingProcess;
    public function delete(int $sessionId): bool;
    public function getById(int $id): ?OnboardingProcess;
    public function cleanupStaleJobs(int $minutes = 60): int;
}
