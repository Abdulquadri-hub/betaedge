<?php

namespace App\Models\Scopes;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * TenantScope - Automatically filters queries by tenant_id
 * Applied globally to all tenant-specific models
 * @template TModel of Model
 * @implements Scope<TModel>
 */

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $tenantId = $this->getCurrentTenantId();
        
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    protected function getCurrentTenantId(): ?int
    {
        // Get from session (set by middleware)
        if (session()->has('active_tenant_id')) {
            return session('active_tenant_id');
        }

        // Get from authenticated user's current tenant
        if (Auth::check() && Auth::user()->currentTenant) {
            return Auth::user()->currentTenant->id;
        }

        return null;
    }
}
