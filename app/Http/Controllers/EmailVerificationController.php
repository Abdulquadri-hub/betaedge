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

    /**
     * Show email verification notice page
     */
    public function notice() 
    {
        return Inertia::render('Auth/VerifyEmailNotice', [
            'message' => 'Please check your email for verification link'
        ]);
    }

    /**
     * Verify email with token and show password setup page
     */
    public function verify(string $token) 
    {
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

        // Already verified - redirect to login
        if ($tenant->email_verified_at) {
            return redirect()->route('login')->with('info', 'Email already verified. Please login.');
        }

        // Check if token expired (24 hours)
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

        // Token is valid - show password setup page
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

    /**
     * Set password and complete verification
     */
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

        // Check if token expired
        if ($tenant->updated_at->addHours(24) < now()) {
            return back()->withErrors(['token' => 'Verification link has expired']);
        }

        // Mark email as verified
        $tenant->markEmailAsVerified();

        // Set password for owner user
        $tenant->owner->update([
            'password' => Hash::make($request->password),
            'email_verified_at' => now()
        ]);

        // Log the user in
        Auth::login($tenant->owner);

        // Set active tenant session
        session(['active_tenant_id' => $tenant->id]);

        // Log activity
        activity()
            ->causedBy($tenant->owner)
            ->performedOn($tenant)
            ->log('Email verified and password set');

        // Redirect to tenant admin dashboard
        $adminUrl = 'https://' . $tenant->subdomain . '/admin';
        
        return Inertia::location($adminUrl);
    }

    /**
     * Resend verification email
     */
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

        $tenant = Tenant::where('owner_email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        // Always return success for security (don't reveal if email exists)
        if (!$tenant) {
            return response()->json([
                'success' => true,
                'message' => 'If this email exists, a verification link has been sent.'
            ]);
        }

        try {
            // Generate new token
            $tenant->generateVerificationToken();
            
            // Send verification email
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