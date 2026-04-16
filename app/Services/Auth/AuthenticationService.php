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
        $user = $this->authRepo->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials.',
            ];
        }

        if (!$this->authRepo->verifyPassword($user, $password)) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials.',
            ];
        }

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

        session()->regenerate();

        return [
            'success' => true,
            'user' => Auth::user(),
            'message' => 'Login successful.',
        ];
    }

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

    public function getUserTenants(User $user): array
    {
        $tenants = $this->authRepo->getUserTenants($user);
        return $tenants ? $tenants->all() : [];
    }

    public function setActiveTenant(User $user): array
    {
        $tenant = $this->authRepo->getUserFirstTenant($user);

        if (!$tenant) {
            return [
                'success' => false,
                'tenant' => null,
                'message' => 'You do not have access to any school.',
            ];
        }

        session(['active_tenant_id' => $tenant->id]);

        return [
            'success' => true,
            'tenant' => $tenant,
            'message' => 'Tenant activated.',
        ];
    }

    public function validateTenantAccess(User $user, int $tenantId): bool
    {
        return $this->authRepo->canAccessTenant($user, $tenantId);
    }

    public function validateUserRole(User $user, string $selectedRole): bool
    {
        return $user->user_type === $selectedRole;
    }
}
