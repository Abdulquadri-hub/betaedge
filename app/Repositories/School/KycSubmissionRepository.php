<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\KycSubmissionRepositoryInterface;
use App\Models\KycSubmission;

class KycSubmissionRepository implements KycSubmissionRepositoryInterface
{
    public function latestForTenant(int $tenantId): ?KycSubmission
    {
        return KycSubmission::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first();
    }

    public function pendingForTenant(int $tenantId): ?KycSubmission
    {
        return KycSubmission::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();
    }

    public function deleteForTenant(int $tenantId): void
    {
        KycSubmission::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    public function createForTenant(int $tenantId, array $data): KycSubmission
    {
        return KycSubmission::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'status' => 'pending',
            'submitted_at' => now(),
        ]));
    }
}
