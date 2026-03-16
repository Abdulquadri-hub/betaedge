<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * TenantFactory
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'owner_id' => User::factory(),
            'owner_email' => fake()->companyEmail(),
            'school_type' => fake()->randomElement(['primary', 'secondary', 'university', 'bootcamp', 'tutoring']),
            'custom_domain' => null,
            'subdomain' => Str::slug($name),
            'website' => fake()->url(),
            'year_established' => fake()->year(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'description' => fake()->paragraph(),
            'logo' => fake()->imageUrl(200, 200, 'business'),
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'timezone' => fake()->timezone(),
            'currency' => 'USD',
            'status' => 'active',
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_token' => null,
            'trial_ends_at' => now()->addDays(30),
            'setup_completed' => true,
            'onboarding_step' => 5,
            'max_users' => 100,
            'max_courses' => 50,
            'max_storage_gb' => 10,
            'current_storage_gb' => fake()->numberBetween(0, 5),
        ];
    }

    public function trial(): Factory
    {
        return $this->state([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'setup_completed' => false,
            'onboarding_step' => fake()->numberBetween(1, 4),
        ]);
    }

    public function suspended(): Factory
    {
        return $this->state(['status' => 'suspended']);
    }

    public function unverified(): Factory
    {
        return $this->state([
            'is_verified' => false,
            'email_verified_at' => null,
            'verification_token' => Str::random(64),
        ]);
    }
}
