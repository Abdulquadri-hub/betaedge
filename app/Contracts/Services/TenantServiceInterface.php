<?php

namespace App\Contracts\Services;

use App\Models\Tenant;

interface TenantServiceInterface
{
    public function setup(array $onboardingData): ?array;
    public function retrySetup(int $id, array $onboardingData): ?array;
    public function delete(Tenant $tenant): bool;
    public function update(Tenant $tenant, array $data): bool;
}
