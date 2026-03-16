<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = SubscriptionPlan::inRandomOrder()->first();
        $billingCycle = $plan->billing_cycle ?? 'monthly';

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_plan_id' => $plan?->id ?? SubscriptionPlan::factory(),
            'subscription_code' => 'SUB-' . strtoupper(Str::random(12)),
            'status' => fake()->randomElement(['active', 'cancelled']),
            'amount' => $plan?->price ?? 99,
            'currency' => 'USD',
            'billing_cycle' => $billingCycle,
            'started_at' => now()->subMonths(fake()->numberBetween(1, 12)),
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'next_billing_date' => now()->addMonth()->startOfMonth(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'auto_renew' => true,
            'payment_provider' => fake()->randomElement(['stripe', 'paystack']),
            'provider_subscription_id' => 'sub_' . Str::random(20),
        ];
    }

    public function active(): Factory
    {
        return $this->state([
            'status' => 'active',
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): Factory
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function withoutAutoRenew(): Factory
    {
        return $this->state(['auto_renew' => false]);
    }
}
