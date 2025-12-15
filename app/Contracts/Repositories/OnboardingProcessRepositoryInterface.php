<?php

namespace App\Contracts\Repositories;

use App\Models\OnboardingProcess;

interface OnboardingProcessRepositoryInterface
{
    public function getBySessionId(string $sessionId): ?OnboardingProcess;
    public function getByJobId(string $jobId): ?OnboardingProcess;
    public function create(string $sessionId, array $data): ?OnboardingProcess;
    public function update(string $sessionId, array $data): ?OnboardingProcess;
    public function delete(string $sessionId): bool;
    public function getById(string $id): ?OnboardingProcess;
    public function cleanupStaleJobs(int $minutes = 60): int;
}
