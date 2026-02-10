<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\OnboardingProcess;
use App\Contracts\Repositories\TenantUserRepositoryInterface;

class TenantUserRepository implements TenantUserRepositoryInterface
{
    public function create(Tenant $tenant, OnboardingProcess $onboarding)
    {
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->owner_id,
            'role' => 'owner',
            'permissions' => ['*'], 
            'status' => 'active',
            'joined_at' => now()
        ]);
    }

    public function isOwner(int $tenantId): ?bool {
        $tenant = TenantUser::where('tenant_id', $tenantId)->where('role', 'owner')->exists();

        if(!$tenant) {
            return false;
        }

        return true;
    }

    public function getRole(int $tenantId, string $role): ?String {
        if($this->isOwner($tenantId)) {
            $data =  TenantUser::select('id', 'role')->where('role', $role)->firstOrFail();
            return $data->role;
        }

        return '';
    }


}
