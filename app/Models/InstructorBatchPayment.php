<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorBatchPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instructor_id',
        'batch_id',
        'amount_due',
        'payment_status',  // pending | paid
        'marked_paid_at',
        'note',
    ];

    protected $casts = [
        'amount_due'     => 'decimal:2',
        'marked_paid_at' => 'datetime',
    ];

    // ─── Constants ───────────────────────────────────────────────────────────────

    const STATUS_PENDING = 'pending';
    const STATUS_PAID    = 'paid';

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Required by the controller: InstructorBatchPayment::whereHas('batch', ...)
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    public function markAsPaid(?string $note = null): void
    {
        $this->update([
            'payment_status' => self::STATUS_PAID,
            'marked_paid_at' => now(),
            'note'           => $note ?? $this->note,
        ]);
    }
}