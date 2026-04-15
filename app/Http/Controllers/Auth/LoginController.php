<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;

class LoginController extends Controller
{
    public function __construct(
        protected AuthenticationServiceInterface $authService
    ) {}

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request with multi-tenant support
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

        // Get all accessible tenants for the user
        $user = $result['user'];
        $tenants = $this->authService->getUserTenants($user);
        $tenantCount = count($tenants);

        // Check if user has access to any tenant
        if ($tenantCount === 0) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => 'You do not have access to any school.',
            ]);
        }

        // If user has only 1 tenant, auto-select and redirect to tenant subdomain
        if ($tenantCount === 1) {
            $tenantResult = $this->authService->setActiveTenant($user);

            if (!$tenantResult['success']) {
                $this->authService->logout();
                throw ValidationException::withMessages([
                    'email' => $tenantResult['message'],
                ]);
            }

            $tenant = $tenantResult['tenant'];
            $subdomain = $tenant->custom_domain ?? $tenant->subdomain;
            
            return redirect()->to('https://' . $subdomain . '/dashboard');
        }

        // If user has multiple tenants, set first as active and redirect to school selector
        $tenantResult = $this->authService->setActiveTenant($user);

        if (!$tenantResult['success']) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => $tenantResult['message'],
            ]);
        }

        return redirect()->to('https://' . ($tenantResult['tenant']->custom_domain ?? $tenantResult['tenant']->subdomain) . '/auth/select-school');
    }

    public function initiate(Request $request)
    {
        return $this->login($request);
    }
}


