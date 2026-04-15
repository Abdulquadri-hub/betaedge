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

        $tenants = $this->authRepo->getUserTenants($user);
        $tenantCount = count($tenants);

        if ($tenantCount === 0) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => 'You do not have access to any school.',
            ]);
        }

        // Get first tenant and set it as active in session
        $tenant = $tenants[0];
        session(['active_tenant_id' => $tenant->id]);
        session()->save(); // Explicitly save session before cross-subdomain redirect

        $subdomain = $tenant->custom_domain ?? $tenant->subdomain;

        // If user has only 1 tenant, auto-select and redirect to dashboard
        if ($tenantCount === 1) {
            return redirect()->to('https://' . $subdomain . '/dashboard');
        }

        return redirect()->to('https://' . $subdomain . '/auth/select-school');
    }

    public function initiate(Request $request)
    {
        return $this->login($request);
    }
}


