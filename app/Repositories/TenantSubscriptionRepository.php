<?php

namespace App\Repositories;

use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Models\Tenant;
use App\Models\OnboardingProcess;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;

class TenantSubscriptionRepository implements TenantSubscriptionRepositoryInterface
{
    public function __construct(
        protected SubscriptionPlanRepositoryInterface $subPlanRepo,
    ){}

   public function create(Tenant $tenant, OnboardingProcess $onboarding): TenantSubscription
   {
       $planData = $onboarding->getPlan();
       $plan = $this->subPlanRepo->getById($planData['plan_id']);

       $billingCycle = $planData['billing_cycle'] ?? 'monthly';
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $periodEnd = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => $plan->currency,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => $periodEnd,
            'auto_renew' => true,
            'payment_method' => 'paystack',
            'payment_provider' => 'paystack'
        ]);
   }
}
