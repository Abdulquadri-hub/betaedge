<?php

namespace App\Contracts\Repositories\Auth;

use App\Models\User;

interface AuthenticationRepositoryInterface
{
    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;

    /**
     * Verify password for a user
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(User $user, string $password): bool;

    /**
     * Get user's accessible tenants
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTenants(User $user);

    /**
     * Get user's first accessible tenant
     *
     * @param User $user
     * @return \App\Models\Tenant|null
     */
    public function getUserFirstTenant(User $user);

    /**
     * Check if user can access a specific tenant
     *
     * @param User $user
     * @param int $tenantId
     * @return bool
     */
    public function canAccessTenant(User $user, int $tenantId): bool;
}
