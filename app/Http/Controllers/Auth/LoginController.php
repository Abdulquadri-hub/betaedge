<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function index() {
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
        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // If the user has tenants (schools), store the tenant_id in session
        // For now, get the first tenant they have access to
        $tenant = $user->tenants()->first();

        if ($tenant) {
            session(['active_tenant_id' => $tenant->id]);
            return redirect()->intended(route('dashboard') ?? '/dashboard');
        } else {
            // User has no tenant access - this shouldn't happen in normal flow
            Auth::logout();
            return redirect()->route('login.index')->withErrors(['email' => 'You do not have access to any school.']);
        }
    }

    /**
     * Initialize login (for two-factor auth flow if needed)
     */
    public function initiate(Request $request)
    {
        // This can be used for role selection or two-factor auth
        return $this->login($request);
    }
}

