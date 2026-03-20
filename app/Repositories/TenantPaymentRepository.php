<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use App\Contracts\Repositories\TenantPaymentRepositoryInterface;

class TenantPaymentRepository implements TenantPaymentRepositoryInterface
{
    public function create(
        Tenant $tenant,
        TenantSubscription $subscription,
        ?array $paymentData,
        ?array $verification
    ): void {
        $plan = $subscription->plan;

        // Free plan — no payment required
        if ($plan->isFree() || empty($paymentData['paystack_reference'])) {
            TenantPayment::create([
                'tenant_id'       => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount'          => 0,
                'currency'        => 'NGN',
                'payment_method'  => 'manual_payment',
                'status'          => 'completed',
                'paid_at'         => now(),
                'metadata'        => ['note' => 'Free plan - no payment required'],
            ]);
            return;
        }

        $data = $verification['data'];

        TenantPayment::create([
            'tenant_id'          => $tenant->id,
            'subscription_id'    => $subscription->id,
            'amount'             => $data['amount'] / 100,
            'currency'           => $data['currency'],
            'payment_method'     => 'paystack',
            'status'             => 'completed',
            'transaction_reference' => $data['reference'],
            'payment_gateway_id' => (string) $data['id'],
            'paid_at'            => $data['paid_at'] ?? now(),
            'metadata'           => [
                'channel'  => $data['channel'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'gateway_response' => $data['gateway_response'] ?? null,
            ],
        ]);
    }

    public function existsForSubscription(int $subscriptionId): bool {
        return TenantPayment::where('subscription_id', $subscriptionId)
            ->where('status', 'completed')
            ->exists();
    }

    public function existsForReference(string $reference): bool {
        return TenantPayment::where('transaction_reference', $reference)
            ->where('status', 'completed')
            ->exists();
    }
}
