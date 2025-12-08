<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TenantSubscription Model - Tracks tenant's subscription status
 */
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
            if (empty($subscription->subscription_code)) {
                $subscription->subscription_code = self::generateSubscriptionCode();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('current_period_end', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('current_period_end', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereBetween('current_period_end', [now(), now()->addDays($days)]);
    }

    // Helper Methods
    public static function generateSubscriptionCode(): string
    {
        do {
            $code = 'TSUB-' . strtoupper(Str::random(10));
        } while (self::where('subscription_code', $code)->exists());

        return $code;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->current_period_end->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->current_period_end->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->status === 'active' && 
               $this->current_period_end->isBetween(now(), now()->addDays($days));
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->current_period_end->isPast()) return 0;
        
        return now()->diffInDays($this->current_period_end);
    }

    public function renew(): void
    {
        $periodLength = match($this->billing_cycle) {
            'monthly' => 30,
            'yearly' => 365,
            default => 30,
        };

        $this->update([
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($periodLength),
            'status' => 'active',
        ]);
    }

    public function upgrade(int $newPlanId): void
    {
        $this->update([
            'plan_id' => $newPlanId,
            'status' => 'active',
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function reactivate(): void
    {
        if ($this->current_period_end->isFuture()) {
            $this->update([
                'status' => 'active',
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);
        }
    }
}


/**
 * TenantPayment Model - Tracks subscription payments
 */
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
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    // Helper Methods
    public static function generatePaymentReference(): string
    {
        do {
            $reference = 'TPAY-' . strtoupper(Str::random(10));
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }
}


/**
 * DomainVerification Model - Custom domain verification
 */
class DomainVerification extends Model
{
    protected $fillable = [
        'tenant_id',
        'domain',
        'verification_code',
        'dns_records',
        'status',
        'verified_at',
        'expires_at',
        'last_checked_at',
        'error_message',
    ];

    protected $casts = [
        'dns_records' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($verification) {
            if (empty($verification->verification_code)) {
                $verification->verification_code = self::generateVerificationCode();
            }

            if (empty($verification->expires_at)) {
                $verification->expires_at = now()->addDays(30);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    // Helper Methods
    public static function generateVerificationCode(): string
    {
        return config('app.name') . '-verify-' . Str::random(32);
    }

    public function verify(): bool
    {
        // Check DNS records
        $records = dns_get_record($this->domain, DNS_A + DNS_CNAME);
        
        $verified = false;
        foreach ($records as $record) {
            if (isset($record['ip']) && $record['ip'] === config('app.server_ip')) {
                $verified = true;
                break;
            }
        }

        if ($verified) {
            $this->update([
                'status' => 'verified',
                'verified_at' => now(),
                'last_checked_at' => now(),
                'error_message' => null,
            ]);

            // Update tenant
            $this->tenant->update(['custom_domain' => $this->domain]);

            return true;
        }

        $this->update([
            'status' => 'pending',
            'last_checked_at' => now(),
            'error_message' => 'DNS records not found or incorrect',
        ]);

        return false;
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getRequiredDnsRecords(): array
    {
        return [
            [
                'type' => 'A',
                'name' => '@',
                'value' => config('app.server_ip'),
                'ttl' => 3600,
            ],
            [
                'type' => 'CNAME',
                'name' => 'www',
                'value' => $this->domain,
                'ttl' => 3600,
            ],
            [
                'type' => 'TXT',
                'name' => '@',
                'value' => $this->verification_code,
                'ttl' => 3600,
            ],
        ];
    }
}


/**
 * TenantInvitation Model - Invite instructors to join tenant
 */
class TenantInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'email',
        'full_name',
        'role',
        'invited_by',
        'invitation_code',
        'status',
        'expires_at',
        'accepted_at',
        'message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->invitation_code)) {
                $invitation->invitation_code = self::generateInvitationCode();
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->where('status', 'pending');
    }

    // Helper Methods
    public static function generateInvitationCode(): string
    {
        do {
            $code = Str::random(32);
        } while (self::where('invitation_code', $code)->exists());

        return $code;
    }

    public function accept(User $user): bool
    {
        if ($this->isExpired() || $this->status !== 'pending') {
            return false;
        }

        // Create TenantUser relationship
        TenantUser::create([
            'tenant_id' => $this->tenant_id,
            'user_id' => $user->id,
            'role' => $this->role,
            'joined_at' => now(),
            'status' => 'active',
        ]);

        // Update invitation
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return true;
    }

    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    public function resend(): void
    {
        $this->update([
            'expires_at' => now()->addDays(7),
            'status' => 'pending',
        ]);

        // Send notification
        // event(new TenantInvitationSent($this));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
