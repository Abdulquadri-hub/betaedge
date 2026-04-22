<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycSubmission extends Model
{
    protected $fillable = [
        'tenant_id',
        'submitted_by',
        'id_type',
        'id_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'business_name',
        'rc_number',
        'passport_number',
        'expiry_date',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'expiry_date'   => 'date',
        'reviewed_at'   => 'datetime',
        'submitted_at'  => 'datetime',
    ];

    const STATUS_PENDING     = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED    = 'approved';
    const STATUS_REJECTED    = 'rejected';

    const ID_TYPE_NIN             = 'nin';
    const ID_TYPE_BVN             = 'bvn';
    const ID_TYPE_PASSPORT        = 'passport';
    const ID_TYPE_DRIVERS_LICENSE = 'drivers_license';
    const ID_TYPE_CAC             = 'cac';
    const ID_TYPE_VOTERS_CARD     = 'voters_card';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isBusinessType(): bool
    {
        return $this->id_type === self::ID_TYPE_CAC;
    }

    public function isPersonalType(): bool
    {
        return in_array($this->id_type, [
            self::ID_TYPE_NIN,
            self::ID_TYPE_BVN,
            self::ID_TYPE_PASSPORT,
            self::ID_TYPE_DRIVERS_LICENSE,
            self::ID_TYPE_VOTERS_CARD,
        ]);
    }
}