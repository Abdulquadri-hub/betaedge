<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use App\Contracts\Repositories\TenantPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TenantPaymentRepository implements TenantPaymentRepositoryInterface
{
   public function create(Tenant $tenant, TenantSubscription $subscription, array $paymentData, array $verification) {

        $plan = $subscription->plan;
        
        if ($plan->isFree() || empty($paymentData['paystack_reference'])) {
            TenantPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => 0,
                'currency' => 'NGN',
                'payment_method' => 'free',
                'payment_provider' => 'none',
                'status' => 'completed',
                'paid_at' => now(),
                'notes' => 'Free plan - no payment required'
            ]);
            return;
        }

        TenantPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $verification['amount'],
            'currency' => $verification['currency'],
            'payment_method' => 'paystack',
            'payment_provider' => 'paystack',
            'provider_payment_id' => $verification['transaction_id'],
            'status' => 'completed',
            'paid_at' => $verification['paid_at'] ?? now(),
            'notes' => 'Verified payment via Paystack'
        ]);
    }
}
