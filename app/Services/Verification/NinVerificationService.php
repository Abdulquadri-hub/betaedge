<?php

namespace App\Services\Verification;

use App\Contracts\Services\Verification\NinVerificationServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NinVerificationService implements NinVerificationServiceInterface
{
    public function submitVerification(User $user, int $tenantId, array $data): array
    {
        $verification = DB::table('user_verifications')
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($verification && $verification->status === 'verified') {
            return [
                'success' => false,
                'message' => 'User is already verified.',
                'status' => 'verified',
            ];
        }

        DB::table('user_verifications')->updateOrInsert(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ],
            [
                'id_type' => $data['id_type'],
                'id_number' => encrypt($data['id_number']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'status' => 'pending',
                'submitted_at' => now(),
                'verification_response' => json_encode(['submitted_at' => now()->toIso8601String()]),
            ]
        );

        return [
            'success' => true,
            'message' => 'Verification submitted. Please wait for approval.',
            'status' => 'pending',
        ];
    }

    public function getStatus(User $user, int $tenantId): ?array
    {
        $verification = DB::table('user_verifications')
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $verification ? (array) $verification : null;
    }

    public function isVerified(User $user, int $tenantId): bool
    {
        $verification = $this->getStatus($user, $tenantId);
        
        return $verification && $verification['status'] === 'verified';
    }
}
