<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Contracts\Services\Verification\NinVerificationServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected NinVerificationServiceInterface $verificationService
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $tenantId = session('active_tenant_id');
        $verification = $this->verificationService->getStatus($user, $tenantId);

        return Inertia::render('School/Dashboard/Profile', [
            'user' => $user,
            'tenant' => app('tenant'),
            'verification' => $verification,
        ]);
    }

    public function update(Request $request): Response
    {
        $user = $request->user();
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|regex:/^\+?[0-9]{10,}$/',
        ]);

        $user->update($validated);

        return Inertia::render('School/Dashboard/Profile', [
            'user' => $user->fresh(),
            'tenant' => $tenant,
            'verification' => $this->verificationService->getStatus($user, session('active_tenant_id')),
            'success' => 'Profile updated successfully.',
        ]);
    }
}
