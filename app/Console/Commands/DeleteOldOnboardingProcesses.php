<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OnboardingProcess;

class DeleteOldOnboardingProcesses extends Command
{
    protected $signature = 'onboarding:prune {--days=30}';
    protected $description = 'Delete old completed onboarding processes';

    public function handle()
    {
        $days = $this->option('days');

        $count = OnboardingProcess::where('status', 'completed')
            ->where('completed_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$count} old onboarding records.");

        return 0;
    }
}
