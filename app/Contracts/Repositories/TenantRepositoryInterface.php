<?php

namespace App\Contracts\Repositories;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function getByDomain(string $domain): ?Tenant;
    public function getBySlug(string $slug): ?Tenant;
    public function getById(int $id): ?Tenant;
    public function getUsageStats(): array;
    public function getStats(): array;
    public function completeOnboarding(): void;
    public function updateOnboardingStep(): void;
}
