<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPaymentConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'public_key',
        'secret_key',
        'primary_currency',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name',
    ];

    protected $hidden = [
        'secret_key',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}