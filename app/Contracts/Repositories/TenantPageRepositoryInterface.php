<?php

namespace App\Contracts\Repositories;

use App\Models\Tenant;
use App\Models\OnboardingProcess;

interface TenantPageRepositoryInterface
{
    public function generatePages(Tenant $tenant, OnboardingProcess $onboarding): void;
}
