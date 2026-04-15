<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthenticationServiceInterface
{
    /**
     * Authenticate user with email and password
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return array - ['success' => bool, 'user' => User|null, 'message' => string]
     */
    public function authenticate(string $email, string $password, bool $remember = false): array;

    /**
     * Logout the currently authenticated user
     *
     * @return bool
     */
    public function logout(): bool;

    /**
     * Get current authenticated user
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User;

    /**
     * Set active tenant for authenticated user
     *
     * @param User $user
     * @return array - ['success' => bool, 'tenant_id' => int|null, 'message' => string]
     */
    public function setActiveTenant(User $user): array;

    /**
     * Validate user has access to a tenant
     *
     * @param User $user
     * @param int $tenantId
     * @return bool
     */
    public function validateTenantAccess(User $user, int $tenantId): bool;
}
