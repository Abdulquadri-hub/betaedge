<?php

namespace App\Contracts\Services\School;

interface PaystackServiceInterface
{
    public function publicKey(): string;
    public function initializePayment(string $email,int $amountNaira,string $reference,array  $metadata,string $callbackUrl): array; 
    public function verifyPayment(string $reference): array;
    public function validateWebhookSignature(string $payload, string $signature): bool;
    public static function calculateSplit(int $amountNaira): array;
    public static function generateReference(string $tenantSlug): string;
}
