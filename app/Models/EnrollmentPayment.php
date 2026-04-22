<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EnrollmentPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'batch_id',
        'student_id',
        'enrollment_id',
        'paystack_reference',
        'paystack_access_code',
        'paystack_authorization_url',
        'amount_kobo',
        'platform_fee_kobo',
        'school_amount_kobo',
        'currency',
        'status',
        'channel',
        'ip_address',
        'paid_at',
        'paystack_response',
    ];

    protected $casts = [
        'amount_kobo'          => 'integer',
        'platform_fee_kobo'    => 'integer',
        'school_amount_kobo'   => 'integer',
        'paid_at'              => 'datetime',
        'paystack_response'    => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function getAmountNairaAttribute(): float
    {
        return $this->amount_kobo / 100;
    }

    public function getPlatformFeeNairaAttribute(): float
    {
        return $this->platform_fee_kobo / 100;
    }

    public function getSchoolAmountNairaAttribute(): float
    {
        return $this->school_amount_kobo / 100;
    }


    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markCompleted(array $paystackData): void
    {
        $this->update([
            'status'             => 'completed',
            'channel'            => $paystackData['channel'] ?? null,
            'paid_at'            => now(),
            'paystack_response'  => $paystackData,
        ]);
    }

    public function markFailed(array $paystackData = []): void
    {
        $this->update([
            'status'           => 'failed',
            'paystack_response'=> $paystackData ?: $this->paystack_response,
        ]);
    }
}