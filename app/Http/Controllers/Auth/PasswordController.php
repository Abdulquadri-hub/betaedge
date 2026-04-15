<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Services\PasswordReset\PasswordResetServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function __construct(
        private PasswordResetServiceInterface $passwordResetService
    ) {}

    public function showForgot()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'We could not find a user with that email address.',
        ]);

        try {
            $this->passwordResetService->sendResetEmail($request->email);

            return back()->with('status', 'We have emailed your password reset link!');
        } catch (\Exception $e) {
            Log::error('Password reset email failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send reset email. Please try again.']);
        }
    }

    public function showReset(Request $request)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email'),
            'token' => $request->query('token'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $success = $this->passwordResetService->resetPassword(
            $validated['email'],
            $validated['token'],
            $validated['password']
        );

        if (!$success) {
            return back()->withErrors([
                'token' => 'This password reset token is invalid or has expired.',
            ]);
        }

        return redirect()->route('login.index')
            ->with('status', 'Your password has been reset successfully. Please log in with your new password.');
    }
}

