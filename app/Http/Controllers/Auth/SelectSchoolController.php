<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use Illuminate\Support\Facades\Auth;

class SelectSchoolController extends Controller
{
    public function __construct(
        protected AuthenticationServiceInterface $authService,
        protected AuthenticationRepositoryInterface $authRepo,
    ) {}

    public function showSelectSchool()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login.index');
        }

        $tenants = $this->authRepo->getUserTenants($user);

        if (count($tenants) === 1) {
            $tenant = $tenants[0];
            session(['active_tenant_id' => $tenant->id]);
            
            $subdomain = $tenant->custom_domain ?? $tenant->subdomain;
            return redirect()->to('https://' . $subdomain . '/dashboard');
        }

        if (count($tenants) === 0) {
            Auth::logout();
            return redirect()
                ->route('login.index')
                ->withErrors(['email' => 'You do not have access to any school.']);
        }

        return Inertia::render('Auth/SchoolSelector', [
            'tenants' => $tenants,
        ]);
    }

    public function selectSchool(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login.index');
        }

        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        if (!$this->authService->validateTenantAccess($user, $validated['tenant_id'])) {
            throw ValidationException::withMessages([
                'tenant_id' => 'You do not have access to this school.',
            ]);
        }

        // Set active tenant and save session explicitly
        session(['active_tenant_id' => $validated['tenant_id']]);
        session()->save(); // Explicitly save before cross-subdomain redirect

        $tenant = $user->tenants()
            ->where('id', $validated['tenant_id'])
            ->first();

        if (!$tenant) {
            throw ValidationException::withMessages([
                'tenant_id' => 'School not found.',
            ]);
        }

        $subdomain = $tenant->custom_domain ?? $tenant->subdomain;
    
        return redirect()->to('https://' . $subdomain . '/dashboard');
    }
}
