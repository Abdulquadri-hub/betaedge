<?php

namespace App\Contracts\Repositories;

use App\Models\Tenant;
use App\Models\OnboardingProcess;

interface TenantUserRepositoryInterface
{
    public function create(Tenant $tenant, OnboardingProcess $onboarding);
    // public function getUserTenants(int $userId);
    // public function attachUserToTenant(int $userId, int $tenantId, string $role);
}
