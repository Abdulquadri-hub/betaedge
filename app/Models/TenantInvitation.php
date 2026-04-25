<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'email',
        'full_name',
        'role',
        'token',
        'expires_at',
        'invited_by',
        'accepted_by',
        'accepted_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if(empty($invitation->token)) {
                $invitation->token = self::generateToken();
            }

            if(empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

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

    public function decline(): void {
        $this->update(['status' => 'declined']);
    }

    public function resend(): void {
        $this->update([
            'expires_at' => now()->addDays(7),
            'status' => 'pending',
        ]);
    }

    public function isExpired(): bool {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool {
        return $this->status === 'pending' && !$this->isExpired();
    }

    protected static function generateToken(): string {
        do {
            $code = Str::random(32);
        } while (self::where('token', $code)->exists());

        return $code;
    }
}
