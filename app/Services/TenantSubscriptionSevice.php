<?php

namespace App\Services;

use Exception;
use App\Models\Tenant;
use App\GateWays\Paystack;
use Illuminate\Support\Str;
use App\Models\OnboardingProcess;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\TenantSubscriptionServiceInterface;
use App\Contracts\Repositories\TenantPaymentRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class TenantSubscriptionSevice implements TenantSubscriptionServiceInterface
{
    public function __construct(
        protected TenantSubscriptionRepositoryInterface $repo,
        protected TenantRepositoryInterface $tenantRepo,
        protected SubscriptionPlanRepositoryInterface $planRepo,
        protected TenantPaymentRepositoryInterface $tenantpayRepo,
        protected Paystack $gateway,
    ){}

    public function subscribe(Tenant $tenant, TenantSubscription $subscription, OnboardingProcess $onboarding): void
    {
        try {

            if ($this->tenantpayRepo->existsForSubscription($subscription->id)) {
                Log::info('Duplicate payment attempt blocked', [
                    'tenant_id'       => $tenant->id,
                    'subscription_id' => $subscription->id,
                ]);
                return;
            }

            $paymentData = $onboarding->getPayment();
            $reference = $paymentData['paystack_reference'] ?? 'free';

            if($reference === 'free') {
                DB::transaction(function () use  ($tenant, $subscription, $paymentData) {
                    return $this->tenantpayRepo->create($tenant, $subscription,$paymentData, null);
                });

                return;
            }

            if ($this->tenantpayRepo->existsForReference($reference)) {
                Log::warning('Paystack reference already used', [
                    'tenant_id'  => $tenant->id,
                    'reference'  => $reference,
                ]);
                throw new Exception('This payment reference has already been used.');
            }
    
            $verification =  $this->gateway->verify($reference);

            if (!$this->isSuccessfullyProcessed($verification)) {
                throw new Exception(
                    'Payment verification failed: ' . ($verification['error'] ?? 'Unknown error')
                );
            }

            if ($verification['data']['status'] !== 'success') {
                throw new Exception('Payment was not successful');
            }

            $paidAmt = $verification['data']['amount'] / 100;
            if ($paidAmt != $subscription->amount) {
                throw new Exception('Payment amount mismatch '. $subscription->amount);
            }

            DB::transaction(function () use  ($tenant, $subscription, $verification, $paymentData) {
                return $this->tenantpayRepo->create($tenant, $subscription,$paymentData, $verification);
            });

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function upgrade(int $tenantId, array $subscriptionData): array
    {
        return [];
    }

    public function cancel(int $tenantId): bool
    {
        return true;
    }

    public function verify(array $payload, string $signature)
    {
        
    }

    private function generateReference(): string {
        return  "TNT_" . strtoupper(Str::random(12)). "_" . time();
    }

    private function prepareSubcriptionData(array $subscriptionData, $plan, $tenant): array {
        return [];
    }

    private function processPayment(array $subscriptionData): array {
        return [];
    }

    private function isSuccessfullyProcessed(array $response): bool {
        return isset($response['status']) && $response['status'] === true;
    }
}
