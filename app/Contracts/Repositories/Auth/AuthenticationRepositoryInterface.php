<?php

namespace App\Contracts\Repositories\Auth;

use App\Models\User;

interface AuthenticationRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function verifyPassword(User $user, string $password): bool;
    public function getUserTenants(User $user);
    public function getUserFirstTenant(User $user);
    public function canAccessTenant(User $user, int $tenantId): bool;
}
