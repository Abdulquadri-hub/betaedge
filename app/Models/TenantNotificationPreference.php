<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantNotificationPreference extends Model
{
    protected $fillable = [
        'tenant_id',
        'email_new_enrollment',
        'email_payment_received',
        'email_batch_complete',
        'email_complaint',
        'email_weekly_summary',
        'sms_new_enrollment',
        'sms_payment_received',
        'sms_complaint',
    ];

    protected $casts = [
        'email_new_enrollment'   => 'boolean',
        'email_payment_received' => 'boolean',
        'email_batch_complete'   => 'boolean',
        'email_complaint'        => 'boolean',
        'email_weekly_summary'   => 'boolean',
        'sms_new_enrollment'     => 'boolean',
        'sms_payment_received'   => 'boolean',
        'sms_complaint'          => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}