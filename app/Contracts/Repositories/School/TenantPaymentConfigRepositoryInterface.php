<?php

namespace App\Contracts\Repositories\School;

use App\Models\TenantPaymentConfig;
 
interface TenantPaymentConfigRepositoryInterface
{
    public function getByTenant(int $tenantId): ?TenantPaymentConfig;
    public function upsert(int $tenantId, array $data): TenantPaymentConfig;
}
