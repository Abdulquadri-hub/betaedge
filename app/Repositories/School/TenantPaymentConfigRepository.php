<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\TenantPaymentConfigRepositoryInterface;
use App\Models\TenantPaymentConfig;
 
class TenantPaymentConfigRepository implements TenantPaymentConfigRepositoryInterface
{
    public function getByTenant(int $tenantId): ?TenantPaymentConfig
    {
        return TenantPaymentConfig::where('tenant_id', $tenantId)->first();
    }
 
    public function upsert(int $tenantId, array $data): TenantPaymentConfig
    {
        $config = TenantPaymentConfig::firstOrNew(['tenant_id' => $tenantId]);
        $config->fill($data);
        $config->save();
        return $config;
    }
}