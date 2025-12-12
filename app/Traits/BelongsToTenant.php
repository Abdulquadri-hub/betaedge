<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Automatically add tenant_id on creation
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = self::getCurrentTenantId();
            }
        });

        // Apply global scope
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public static function getCurrentTenantId(): ?int
    {
        if (session()->has('active_tenant_id')) {
            return session('active_tenant_id');
        }

        if (Auth::check() && Auth::user()->currentTenant) {
            return Auth::user()->currentTenant->id;
        }

        return null;
    }

    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId);
    }

    public function scopeAcrossAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
