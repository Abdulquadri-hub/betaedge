<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Str;
use App\Models\OnboardingProcess;
use Illuminate\Support\Facades\Hash;
use App\Contracts\Repositories\TenantRepositoryInterface;

class TenantRepository implements TenantRepositoryInterface
{

    public function create(OnboardingProcess $onboarding): ?Tenant
    {
        $profile = $onboarding->getProfile();

        $user = User::create([
            'name' => $profile['owner_name'] ?? $profile['school_name'] . ' Admin',
            'email' => $profile['owner_email'],
            'password' => Hash::make(Str::random(32)), 
            'email_verified_at' => null
        ]);

        return Tenant::create([
            'name' => $profile['school_name'],
            'owner_id' => $user->id,
            'owner_email' => $profile['owner_email'],
            'school_type' => $profile['school_type'] ?? 'primary',
            'address' => $profile['address'] ?? null,
            'city' => $profile['city'] ?? null,
            'country' => $profile['country'] ?? 'NG',
            'description' => $profile['description'] ?? null,
            'logo' => $profile['logo'] ?? null,
            'primary_color' => $profile['primary_color'] ?? '#3B82F6',
            'secondary_color' => $profile['secondary_color'] ?? '#10B981',
            'timezone' => $profile['timezone'] ?? 'Africa/Lagos',
            'currency' => $profile['currency'] ?? 'NGN',
            'status' => 'active',
            'is_verified' => false,
            'trial_ends_at' => now()->addDays(14)
        ]);
    }

    public function getByDomain(string $domain): ?Tenant
    {
        return Tenant::where('subdomain', $domain)
            ->orWhere('custom_domain', $domain)
            ->where('status', 'active')
            ->first();
    }

    public function getBySlug(string $slug): ?Tenant
    {
       return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    public function getById(int $id): ?Tenant
    {
        return Tenant::where('id', $id)
            ->where('status', 'active')
            ->first();
    }

    public function getUsageStats(Tenant $tenant): array
    {
        return $tenant->getUsageStats();
    }

    public function getStats(): array
    {
        return [];
    }

    public function updateOnboardingStep(Tenant $tenant, string $step): void
    {
        $tenant->updateOnboardingStep($step);
    }

    public function completeOnboarding(Tenant $tenant): void
    {
        $tenant->completeOnboarding();
    }

}
