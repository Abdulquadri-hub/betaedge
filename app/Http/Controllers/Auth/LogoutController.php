<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;

class LogoutController extends Controller
{
    public function __construct(
        protected AuthenticationServiceInterface $authService
    ) {}

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $this->authService->logout();

        return redirect()->route('login.index')->with('message', 'You have been logged out successfully.');
    }
}
