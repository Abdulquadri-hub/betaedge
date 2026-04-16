<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use Inertia\Inertia;

class LogoutController extends Controller
{
    public function __construct(
        protected AuthenticationServiceInterface $authService
    ) {}


    public function logout(Request $request)
    {
        $this->authService->logout();

        return Inertia::location(route('login.index'));
    }
}
