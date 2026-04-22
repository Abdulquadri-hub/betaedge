<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorPaymentAgreement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instructor_id',
        'payment_type',   // per_batch | per_student | monthly | custom
        'amount',
        'payment_terms',
        'status',         // active | inactive
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ─── Constants ───────────────────────────────────────────────────────────────

    const TYPE_PER_BATCH   = 'per_batch';
    const TYPE_PER_STUDENT = 'per_student';
    const TYPE_MONTHLY     = 'monthly';
    const TYPE_CUSTOM      = 'custom';

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function typeLabel(): string
    {
        return match ($this->payment_type) {
            self::TYPE_PER_BATCH   => 'Fixed per Batch',
            self::TYPE_PER_STUDENT => 'Fixed per Student',
            self::TYPE_MONTHLY     => 'Monthly Salary',
            self::TYPE_CUSTOM      => 'Custom Arrangement',
            default                => $this->payment_type,
        };
    }

    public function suffix(): string
    {
        return match ($this->payment_type) {
            self::TYPE_PER_BATCH   => '/ batch',
            self::TYPE_PER_STUDENT => '/ student',
            self::TYPE_MONTHLY     => '/ month',
            default                => '',
        };
    }
}