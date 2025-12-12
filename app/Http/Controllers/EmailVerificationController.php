<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Tenant;
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
    ){}

    public function notice() {
        return Inertia::render('Auth/VerifyEmailNotice', [
            'message' => 'Please check your email for verification link'
        ]);
    }

    public function verify(string $token) {
        $key = 'verify-email:' . request()->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429, 'Too many verification attempts');
        }

        RateLimiter::hit($key, 3600);

        $tenant = Tenant::where('verification_token', $token)->first();

        if (!$tenant) {
            return Inertia::render('Auth/VerificationFailed', [
                'message' => 'Invalid or expired verification link'
            ]);
        }

        if ($tenant->email_verified_at) {
            return redirect()->route('login')->with('info', 'Email already verified. Please login.');
        }

        if ($tenant->updated_at->addHours(24) < now()) {
            return Inertia::render('Auth/VerificationExpired', [
                'tenant' => $tenant,
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

        $tenant = Tenant::where('verification_token', $request->token)->first();

        if (!$tenant) {
            return back()->withErrors(['token' => 'Invalid verification token']);
        }

        $tenant->markEmailAsVerified();

        $tenant->owner->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => now()
        ]);

        Auth::login($tenant->owner);

        session(['active_tenant_id' => $tenant->id]);

        // Log activity
        activity()
            ->causedBy($tenant->owner)
            ->performedOn($tenant)
            ->log('Email verified and password set');


        $adminUrl = 'https://' . $tenant->subdomain . '/admin';
        
        return Inertia::location($adminUrl);
    }

    public function resend(Request $request) {

        $request->validate([
            'email' => 'required|email'
        ]);

        $key = 'resend-verification:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many resend attempts. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        $tenant = Tenant::where('owner_email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'If this email exists, a verification link has been sent.'
            ]);
        }

        try {
            $this->mailService->send($tenant);

            return response()->json([
                'message' => 'Verification email has been resent successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to send email. Please try again later.'
            ], 500);
        }
    }
}