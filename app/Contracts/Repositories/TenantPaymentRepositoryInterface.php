<?php

namespace App\Contracts\Repositories;

use App\Models\Tenant;
use App\Models\TenantSubscription;

interface TenantPaymentRepositoryInterface
{
    public function create(Tenant $tenant, TenantSubscription $subscription, array $paymentData, array $verification);
}
