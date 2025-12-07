<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MichielKempen\LaravelEncryptable\Encryptable;

class TenantPaymentConfig extends Model
{
    use Encryptable;

    protected $fillable = [
        'tenant_id',
        'primary_currency',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name'
    ];

    protected $encryptable = [
        'bank_name',
        'account_number',
        'account_name',
    ];

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

}
