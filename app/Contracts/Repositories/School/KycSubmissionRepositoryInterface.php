<?php

namespace App\Contracts\Repositories\School;

use App\Models\KycSubmission;

interface KycSubmissionRepositoryInterface
{
    public function latestForTenant(int $tenantId): ?KycSubmission;
    public function pendingForTenant(int $tenantId): ?KycSubmission;
    public function deleteForTenant(int $tenantId): void;
    public function createForTenant(int $tenantId, array $data): KycSubmission;
}
