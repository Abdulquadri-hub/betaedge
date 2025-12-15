<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class TenantSubscription extends Model
{
     protected $fillable = [
        'tenant_id',
        'plan_id',
        'subscription_code',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancellation_reason',
        'payment_method',
        'payment_provider',
        'provider_subscription_id',
        'auto_renew',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if(empty($subscription->subscription_code)) {
                $subscription->subscription_code = self::generateSubscriptionCode();
            }
        });
    }

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public static function generateSubscriptionCode(): string {
        do {
           $code = 'TSUB-' . strtoupper(Str::random(10));
        } while (self::where('subscription_code', $code)->exists());

        return $code;
    }

    public function payments(): HasMany {
        return $this->hasMany(TenantPayment::class, 'subscription_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 'active')
                    ->where('current_period_end', '>', now());
    }

    public function scopeExpired($query) {
        return $query->where('status', 'expired')
                    ->orWhere('current_period_end', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 7) {
        return $query->where('status', 'active')
                    ->whereBetween('current_period_end', [now(), now()->addDays($days)]);
    }

    public function isActive(): bool {
        return $this->status === 'active' && $this->current_period_end->isFuture();
    }

    public function isExpired(): bool {
        return $this->status === 'expired' || $this->current_period_end->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool {
        return $this->status === 'active' &&
               $this->whereBetween($this->current_period_end->isBetween(now(), now()->addDays($days)));
    }

    public function getDaysRemainingAttribute(): int {
        if($this->current_period_end->isPast()) return 0;

        return now()->diffInDays($this->current_period_end);
    }

    public function renew(): void {
        $periodLength = match ($this->bicycle_cylce) {
            'monthly' => 30,
            'yearly' => 365,
            default => 30
        };

        $this->update([
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($periodLength),
            'status' => 'active',
        ]);
    }

    public function upgrade(int $newPlanId): void {
        $this->update([
            'plan_id' => $newPlanId,
            'status' => 'active',
        ]);
    }

    public function cancel(string $reason): void {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function suspend(): void {
        $this->update(['status' => 'suspended']);
    }

    public function reactivate(): void {
        if ($this->current_period_end->isFuture()) {
            $this->update([
                'status' => 'active',
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);
        }
    }
}
