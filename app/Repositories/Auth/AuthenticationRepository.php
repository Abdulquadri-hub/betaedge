<?php

namespace App\Repositories\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;

class AuthenticationRepository implements AuthenticationRepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Verify password for a user
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Get user's accessible tenants
     */
    public function getUserTenants(User $user)
    {
        return $user->tenants()
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get user's first accessible tenant
     */
    public function getUserFirstTenant(User $user)
    {
        return $user->tenants()
            ->wherePivot('status', 'active')
            ->first();
    }

    /**
     * Check if user can access a specific tenant
     */
    public function canAccessTenant(User $user, int $tenantId): bool
    {
        return $user->canAccessTenant($tenantId);
    }
}
