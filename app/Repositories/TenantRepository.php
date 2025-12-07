<?php

namespace App\Repositories;

use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Models\Tenant;

class TenantRepository implements TenantRepositoryInterface
{

    public function getByDomain(string $domain): ?Tenant
    {
        throw new \Exception('Not implemented');
    }

    public function getBySlug(string $slug): ?Tenant
    {
        throw new \Exception('Not implemented');
    }

    public function getById(int $id): ?Tenant
    {
        throw new \Exception('Not implemented');
    }

    public function getUsageStats(): array
    {
        throw new \Exception('Not implemented');
    }

    public function getStats(): array
    {
        throw new \Exception('Not implemented');
    }

    public function updateOnboardingStep(): void
    {
        throw new \Exception('Not implemented');
    }

    public function completeOnboarding(): void
    {
        throw new \Exception('Not implemented');
    }
}
