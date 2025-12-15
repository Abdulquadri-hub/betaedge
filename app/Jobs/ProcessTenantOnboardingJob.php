<?php

namespace App\Jobs;

use App\Contracts\Services\TenantServiceInterface;
use App\Models\OnboardingProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessTenantOnboardingJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 10;

    public function __construct(
        private int $onboardingId,
        private string $jobId,
    ){}

    public function handle(): void
    {
        try {

            Log::info("Starting onboarding process", [
                'onboarding_id' => $this->onboardingId,
                'job_id' => $this->jobId
            ]);

           app(TenantServiceInterface::class)->setup($this->onboardingId);

        } catch (\Exception $e) {
            Log::error("Onboarding failed", [
                'onboarding_id' => $this->onboardingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $onboarding = OnboardingProcess::find($this->onboardingId);
            if ($onboarding) {
                $onboarding->markAsFailed($e->getMessage());
            }
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Onboarding job failed permanently', [
            'onboarding_id' => $this->onboardingId,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $onboarding = OnboardingProcess::find($this->onboardingId);
        if ($onboarding) {
            $onboarding->markAsFailed(
                'Setup failed after multiple attempts. Please contact support. Error: ' . $exception->getMessage()
            );
        }
    }
}
