<?php

namespace App\Contracts\Services;

use App\Models\Tenant;
use App\Models\OnboardingProcess;
use App\Models\TenantSubscription;

interface TenantSubscriptionServiceInterface
{
    public function subscribe(Tenant $tenant, TenantSubscription $subscription, OnboardingProcess $onboarding): void;
    public function cancel(int $tenantId): bool;
    public function upgrade(int $tenantId, array $subscriptionData): array;
    public function verify(array $payload, string $signature);
}
