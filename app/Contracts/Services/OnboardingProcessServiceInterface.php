<?php

namespace App\Contracts\Services;

interface OnboardingProcessServiceInterface
{
    public function save(string $sessionId, array $stepData, string $step): ?array;
    public function fetch(string $sessionId): ?array;
    public function submit(string $sessionId): ?array;
    public function getProgress(string $jobId): ?array;
    public function getOrCreateSessionId($request): string;
}
