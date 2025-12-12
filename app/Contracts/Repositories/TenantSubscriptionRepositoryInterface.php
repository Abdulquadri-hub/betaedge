<?php

namespace App\Contracts\Repositories;

use App\Models\OnboardingProcess;
use App\Models\Tenant;
use App\Models\TenantSubscription;

interface TenantSubscriptionRepositoryInterface
{
    public function create(Tenant $tenant, OnboardingProcess $onboarding): TenantSubscription;
}
