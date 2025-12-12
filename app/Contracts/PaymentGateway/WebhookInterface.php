<?php

namespace App\Contracts\PaymentGateWay;

interface WebhookInterface
{
    public function validateSignature(array $payload, string $signature): array;
}
