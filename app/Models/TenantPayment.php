<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'payment_reference',
        'amount',
        'currency',
        'payment_method',
        'payment_provider',
        'provider_payment_id',
        'status',
        'paid_at',
        'receipt_url',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if(empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }
        });
    }

    public function generatePaymentReference() {
        do {
            $reference = 'TPAY-' . strtoupper(Str::random(10));
        } while (self::where('payment_reference', $reference)->exists());
    }

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    public function markAsPaid(): void {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }
}
