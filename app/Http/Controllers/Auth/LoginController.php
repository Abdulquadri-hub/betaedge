<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function __construct(
        protected AuthenticationServiceInterface $authService,
        protected AuthenticationRepositoryInterface $authRepo,
    ) {}

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request with multi-tenant and role support
     */
    public function login(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // Attempt authentication
        $result = $this->authService->authenticate(
            $validated['email'],
            $validated['password'],
            $request->boolean('remember')
        );

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'email' => $result['message'],
            ]);
        }

        $user = $result['user'];

        // Get all accessible tenants for the user
        $tenants = $this->authRepo->getUserTenants($user);
        $tenantCount = count($tenants);

        // Check if user has access to any tenant
        if ($tenantCount === 0) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => 'You do not have access to any school.',
            ]);
        }

        // Set active tenant (first one or selected one)
        $tenantResult = $this->authService->setActiveTenant($user);

        if (!$tenantResult['success']) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => $tenantResult['message'],
            ]);
        }

        $tenant = $tenantResult['tenant'];
        $subdomain = $tenant->custom_domain ?? $tenant->subdomain;
        $userRole = $user->user_type;

        // If user has only 1 tenant, auto-select and redirect to dashboard
        if ($tenantCount === 1) {
            // TODO: Implement role-based dashboards at /{role}/dashboard
            // For now, redirect to /dashboard which renders role-based content
            return redirect()->to('https://' . $subdomain . '/dashboard');
        }

        // If user has multiple tenants, redirect to school selector
        return redirect()->to('https://' . $subdomain . '/auth/select-school');
    }

    public function initiate(Request $request)
    {
        return $this->login($request);
    }
}


