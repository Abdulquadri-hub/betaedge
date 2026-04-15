<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        protected AuthenticationRepositoryInterface $authRepo
    ) {}

    /**
     * Authenticate user with email and password
     */
    public function authenticate(string $email, string $password, bool $remember = false): array
    {
        // Find user by email
        $user = $this->authRepo->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials.',
            ];
        }

        // Verify password
        if (!$this->authRepo->verifyPassword($user, $password)) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials.',
            ];
        }

        // Check if user is active
        if (!$user->is_active) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Your account is inactive. Please contact support.',
            ];
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Authentication failed. Please try again.',
            ];
        }

        // Regenerate session
        session()->regenerate();

        return [
            'success' => true,
            'user' => Auth::user(),
            'message' => 'Login successful.',
        ];
    }

    /**
     * Logout the currently authenticated user
     */
    public function logout(): bool
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return true;
    }

    public function getAuthenticatedUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Get all accessible tenants for a user
     */
    public function getUserTenants(User $user): array
    {
        $tenants = $this->authRepo->getUserTenants($user);
        return $tenants ? $tenants->all() : [];
    }

    /**
     * Set active tenant and return full tenant data
     */
    public function setActiveTenant(User $user): array
    {
        // Get user's first accessible tenant
        $tenant = $this->authRepo->getUserFirstTenant($user);

        if (!$tenant) {
            return [
                'success' => false,
                'tenant' => null,
                'message' => 'You do not have access to any school.',
            ];
        }

        // Store tenant_id in session
        session(['active_tenant_id' => $tenant->id]);

        return [
            'success' => true,
            'tenant' => $tenant,
            'message' => 'Tenant activated.',
        ];
    }

    /**
     * Validate user has access to a tenant
     */
    public function validateTenantAccess(User $user, int $tenantId): bool
    {
        return $this->authRepo->canAccessTenant($user, $tenantId);
    }

    /**
     * Validate user's selected role matches their actual user_type
     */
    public function validateUserRole(User $user, string $selectedRole): bool
    {
        // Map role values: student, parent, instructor, school_owner
        return $user->user_type === $selectedRole;
    }
}
