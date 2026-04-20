<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\TenantNotificationPreferenceRepositoryInterface;
use App\Models\TenantNotificationPreference;
 
class TenantNotificationPreferenceRepository implements TenantNotificationPreferenceRepositoryInterface
{
    public function getByTenant(int $tenantId): TenantNotificationPreference
    {
        return TenantNotificationPreference::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'email_new_enrollment'   => true,
                'email_payment_received' => true,
                'email_batch_complete'   => true,
                'email_complaint'        => true,
                'email_weekly_summary'   => true,
                'sms_new_enrollment'     => false,
                'sms_payment_received'   => true,
                'sms_complaint'          => true,
            ]
        );
    }
 
    public function upsert(int $tenantId, array $data): TenantNotificationPreference
    {
        $pref = TenantNotificationPreference::firstOrNew(['tenant_id' => $tenantId]);
        $pref->fill($data);
        $pref->save();
        return $pref;
    }
}
