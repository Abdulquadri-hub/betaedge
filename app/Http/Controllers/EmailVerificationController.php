<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TenantRepositoryInterface;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\Email\TenantVerificationEmailService;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected TenantVerificationEmailService $mailService,
        protected TenantRepositoryInterface $tenantRepository,
    ){}

    public function notice() 
    {
        return Inertia::render('Auth/VerifyEmailNotice', [
            'message' => 'Please check your email for verification link'
        ]);
    }

    public function verify(string $token) 
    {
        $key = 'verify-email:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many verification attempts');
        }

        RateLimiter::hit($key, 3600);

        $tenant = $this->tenantRepository->getByVerificationToken($token);

        if (! $tenant) {
            return Inertia::render('Auth/VerificationFailed', [
                'message' => 'Invalid or expired verification link'
            ]);
        }

        if ($tenant->email_verified_at) {
            return redirect()->route('login')->with('info', 'Email already verified. Please login.');
        }

        if ($tenant->updated_at->addHours(24) < now()) {
            return Inertia::render('Auth/VerificationExpired', [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->owner_email,
                ],
                'canResend' => true
            ]);
        }

        return Inertia::render('Auth/SetPassword', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->owner_email,
                'subdomain' => $tenant->subdomain
            ],
            'token' => $token
        ]);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tenant = $this->tenantRepository->getByVerificationToken($request->token);

        if (! $tenant) {
            return back()->withErrors(['token' => 'Invalid verification token']);
        }

        if ($tenant->updated_at->addHours(24) < now()) {
            return back()->withErrors(['token' => 'Verification link has expired']);
        }

        $this->tenantRepository->markEmailAsVerified($tenant);

        $tenant->owner->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => now()
        ]);

        Auth::login($tenant->owner);
        session(['active_tenant_id' => $tenant->id]);
        $adminUrl = 'https://' . $tenant->subdomain . '/dashboard';
        
        return Inertia::location($adminUrl);
    }

    public function resend(Request $request) 
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $key = 'resend-verification:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many resend attempts. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        $tenant = $this->tenantRepository->getUnverifiedByEmail($request->email);

        if (! $tenant) {
            return response()->json([
                'success' => true,
                'message' => 'If this email exists, a verification link has been sent.'
            ]);
        }

        try {
            $this->tenantRepository->generateVerificationToken($tenant);
            $this->mailService->send($tenant);

            return response()->json([
                'success' => true,
                'message' => 'Verification email has been resent successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }
}