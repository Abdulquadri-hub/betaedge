<?php

namespace App\Contracts\Repositories;

use App\Models\Tenant;
use App\Models\TenantPage;
use App\Models\OnboardingProcess;

interface TenantPageRepositoryInterface
{
    public function generatePages(Tenant $tenant, OnboardingProcess $onboarding): void;
    public function getPageContent(Tenant $tenant, string $type): TenantPage;
}
