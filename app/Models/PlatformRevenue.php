<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformRevenue extends Model
{
    protected $fillable = [
        'tenant_id',
        'payment_id',
        'enrollment_id',
        'gross_amount',
        'platform_fee',
        'platform_percentage',
        'net_amount',
        'currency',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year);
    }

    // Helper Methods
    public static function calculatePlatformFee(float $amount): float
    {
        $percentage = config('platform.fee_percentage', 5); // Default 5%
        return round(($amount * $percentage) / 100, 2);
    }

    public function getFormattedFeeAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->platform_fee, 2);
    }
}
