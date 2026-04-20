<?php

namespace App\Contracts\Repositories\School;

use App\Models\TenantNotificationPreference;
 
interface TenantNotificationPreferenceRepositoryInterface
{
    public function getByTenant(int $tenantId): TenantNotificationPreference;
    public function upsert(int $tenantId, array $data): TenantNotificationPreference;
}

