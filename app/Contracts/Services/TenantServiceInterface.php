<?php

namespace App\Contracts\Services;

use App\Models\Tenant;

interface TenantServiceInterface
{
    public function setup(int $onboardingId);
    public function retrySetup(int $tenantId, string $sessionId, array $onboardingData): ?array;
    public function delete(Tenant $tenant): bool;
    public function update(Tenant $tenant, array $data): bool;
}
