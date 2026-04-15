<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;
// use Illuminate\Http\Request;

interface AuthenticationServiceInterface
{
    public function authenticate(string $email, string $password, bool $remember = false): array;
    public function logout(): bool;
    public function getAuthenticatedUser(): ?User;
    public function setActiveTenant(User $user): array;
    public function validateTenantAccess(User $user, int $tenantId): bool;
}
