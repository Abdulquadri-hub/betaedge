<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OnboardingProcess;

class CleanupStaleOnboardingJobs extends Command
{
    protected $signature = 'onboarding:cleanup {--minutes=60}';
    protected $description = 'Cleanup stale onboarding jobs that are stuck in processing';
    
    public function handle()
    {
        $minutes = $this->option('minutes');

        $count = OnboardingProcess::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => 'failed',
                'error_message' => "Job timed out after {$minutes} minutes",
                'progress_message' => 'Setup failed due to timeout'
            ]);

        $this->info("Cleaned up {$count} stale onboarding jobs.");

        return 0;
    }
}
