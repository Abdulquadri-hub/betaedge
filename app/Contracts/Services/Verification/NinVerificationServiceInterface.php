<?php

namespace App\Contracts\Services\Verification;

use App\Models\User;

interface NinVerificationServiceInterface
{
    public function submitVerification(User $user, int $tenantId, array $data): array;
    public function getStatus(User $user, int $tenantId): ?array;
    public function isVerified(User $user, int $tenantId): bool;
}
