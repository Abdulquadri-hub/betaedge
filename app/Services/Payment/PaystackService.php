<?php

namespace App\Services\Payment;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class PaystackService
{
    private const PLATFORM_FEE_PERCENT = 10;
    private const BASE_URL = 'https://api.paystack.co';

    public function __construct(
        private readonly Tenant $tenant
    ) {}

    /**
     * Get this tenant's Paystack secret key.
     */
    private function secretKey(): string
    {
        $key = $this->tenant->paymentConfig?->secret_key;

        if (!$key) {
            throw new \RuntimeException(
                "Paystack not configured for tenant [{$this->tenant->name}]. " .
                "Please add your Paystack keys in Settings → Payments."
            );
        }

        return $key;
    }

    /**
     * Get this tenant's Paystack public key (for frontend).
     */
    public function publicKey(): string
    {
        $key = $this->tenant->paymentConfig?->public_key;

        if (!$key) {
            throw new \RuntimeException("Paystack public key not configured.");
        }

        return $key;
    }

    /**
     * Initialize a Paystack transaction.
     *
     * Returns ['authorization_url', 'access_code', 'reference']
     *
     * @param  string  $email         Payer's email
     * @param  int     $amountNaira   Amount in Naira (NOT kobo)
     * @param  string  $reference     Unique reference for this payment
     * @param  array   $metadata      Additional data stored on Paystack
     * @param  string  $callbackUrl   URL Paystack redirects to after payment
     */
    public function initializePayment(
        string $email,
        int    $amountNaira,
        string $reference,
        array  $metadata,
        string $callbackUrl
    ): array {
        $amountKobo = $amountNaira * 100;

        $response = Http::withToken($this->secretKey())
            ->post(self::BASE_URL . '/transaction/initialize', [
                'email'        => $email,
                'amount'       => $amountKobo,
                'reference'    => $reference,
                'callback_url' => $callbackUrl,
                'currency'     => 'NGN',
                'metadata'     => array_merge($metadata, [
                    'tenant_id'     => $this->tenant->id,
                    'tenant_name'   => $this->tenant->name,
                    'platform'      => 'teach',
                ]),
            ]);

        if (!$response->successful() || !$response->json('status')) {
            Log::error('Paystack initialize failed', [
                'tenant'   => $this->tenant->id,
                'response' => $response->json(),
            ]);

            throw new \RuntimeException(
                $response->json('message') ?? 'Payment initialization failed. Please try again.'
            );
        }

        return $response->json('data');
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * Returns the full transaction data if successful.
     * Throws RuntimeException if payment was not successful.
     */
    public function verifyPayment(string $reference): array
    {
        $response = Http::withToken($this->secretKey())
            ->get(self::BASE_URL . "/transaction/verify/{$reference}");

        if (!$response->successful()) {
            throw new \RuntimeException("Could not verify payment. Please contact support.");
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            throw new \RuntimeException(
                "Payment was not successful. Status: {$data['status']}"
            );
        }

        return $data;
    }

    /**
     * Validate a Paystack webhook signature.
     *
     * Uses HMAC-SHA512 with the tenant's secret key.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->secretKey());
        return hash_equals($computed, $signature);
    }

    /**
     * Calculate fee split for a given amount.
     *
     * Returns ['amount_kobo', 'platform_fee_kobo', 'school_amount_kobo']
     */
    public static function calculateSplit(int $amountNaira): array
    {
        $amountKobo      = $amountNaira * 100;
        $platformFeeKobo = (int) round($amountKobo * (self::PLATFORM_FEE_PERCENT / 100));
        $schoolAmountKobo = $amountKobo - $platformFeeKobo;

        return [
            'amount_kobo'       => $amountKobo,
            'platform_fee_kobo' => $platformFeeKobo,
            'school_amount_kobo'=> $schoolAmountKobo,
        ];
    }

    /**
     * Generate a unique payment reference.
     * Format: TEACH-{tenantSlug}-{timestamp}-{random}
     */
    public static function generateReference(string $tenantSlug): string
    {
        return strtoupper(
            'TEACH-' . $tenantSlug . '-' . now()->format('YmdHis') . '-' . Str::random(6)
        );
    }
}