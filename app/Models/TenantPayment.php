<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'currency',
        'payment_method',
        'status',
        'transaction_reference',
        'payment_gateway_id',
        'paid_at',
        'verified_by',
        'verified_at',
        'refund_amount',
        'refunded_at',
        'refund_reason',
        'metadata',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at'       => 'datetime',
        'verified_at'   => 'datetime',
        'refunded_at'   => 'datetime',
        'metadata'      => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payment) {
            if (empty($payment->invoice_number)) {
                $payment->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $invoice = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('invoice_number', $invoice)->exists());

        return $invoice;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status'   => 'failed',
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }

    public function markAsRefunded(float $amount, string $reason): void
    {
        $this->update([
            'status'        => 'refunded',
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_at'   => now(),
        ]);
    }

    public function verify(int $userId): void
    {
        $this->update([
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }


    public function isPaid(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}