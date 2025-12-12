<?php

namespace App\Contracts\Repositories;

use App\Models\OnboardingProcess;
use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function create(OnboardingProcess $onboarding): ?Tenant;
    public function getByDomain(string $domain): ?Tenant;
    public function getBySlug(string $slug): ?Tenant;
    public function getById(int $id): ?Tenant;
    public function getUsageStats(Tenant $tenant): array;
    public function getStats(): array;
    public function completeOnboarding(Tenant $tenant): void;
    public function updateOnboardingStep(Tenant $tenant, string $tep): void;
}
