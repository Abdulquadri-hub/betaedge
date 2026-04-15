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
     * Handle login request
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

        // Set active tenant for the user
        $tenantResult = $this->authService->setActiveTenant($result['user']);

        if (!$tenantResult['success']) {
            $this->authService->logout();
            throw ValidationException::withMessages([
                'email' => $tenantResult['message'],
            ]);
        }

        // Redirect to dashboard
        return redirect()->intended('/dashboard');
    }

    /**
     * Initialize login (POST handler)
     */
    public function initiate(Request $request)
    {
        return $this->login($request);
    }
}


