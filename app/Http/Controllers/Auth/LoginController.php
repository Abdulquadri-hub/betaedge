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

    public function login(Request $request)
    {
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


