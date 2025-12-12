<?php

namespace App\Contracts\Services;

interface OnboardingProcessServiceInterface
{
    public function save(int $sessionId, array $stepData, string $step): ?array;
    public function fetch(int $sessionId): ?array;
    public function submit(int $sessionId): ?array;
    public function getProgress(string $jobId): ?array;
    public function getOrCreateSessionId($request): string;
}
