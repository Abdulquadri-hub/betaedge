<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
