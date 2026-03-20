<?php

namespace App\Jobs;

use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Models\OnboardingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contracts\Services\TenantServiceInterface;

class ProcessTenantOnboardingJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    // public int $tries = 3;
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

            $onboarding = app(OnboardingProcessRepositoryInterface::class)->getById($this->onboardingId);
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

        $onboarding = app(OnboardingProcessRepositoryInterface::class)->getById($this->onboardingId);
        if ($onboarding) {

            DB::transaction(function () use ($onboarding, $exception){
                $this->cleanupFailedProcess($onboarding);

                $onboarding->update([
                    'status' => 'draft',
                    'tenant_id' => null,
                    'job_id' => null,
                    'progress_percentage' => 0,
                    'error_message' => 'Setup failed: ' . $exception->getMessage(),
                    'failed_at' => now()
                ]);
            });
        }
    }

    private function cleanupFailedProcess(OnboardingProcess $onboarding) {
        try {
            app(OnboardingProcessRepositoryInterface::class)->cleanupFailedOnboarding($onboarding);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup onboarding data', [
                'onboarding_id' => $onboarding->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
