<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Starter', 'Professional', 'Enterprise', 'Growth']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 29, 299),
            'currency' => 'USD',
            'billing_cycle' => fake()->randomElement(['monthly', 'quarterly', 'annual']),
            'max_courses' => fake()->numberBetween(5, 500),
            'max_students' => fake()->numberBetween(50, 5000),
            'max_instructors' => fake()->numberBetween(5, 100),
            'storage_gb' => fake()->numberBetween(10, 1000),
            'has_api_access' => fake()->boolean(),
            'has_custom_domain' => fake()->boolean(),
            'has_advanced_analytics' => fake()->boolean(),
            'has_support_priority' => fake()->boolean(),
            'features' => json_encode([
                'Basic Materials',
                'Student Management',
                'Grading System',
                'Attendance Tracking',
            ]),
            'discount_percentage' => 0,
            'is_active' => true,
            'is_featured' => fake()->boolean(),
            'display_order' => fake()->numberBetween(1, 5),
            'activated_at' => now(),
        ];
    }

    public function starter(): Factory
    {
        return $this->state([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29,
            'max_courses' => 5,
            'max_students' => 50,
            'max_instructors' => 2,
            'storage_gb' => 10,
            'has_api_access' => false,
            'has_support_priority' => false,
        ]);
    }

    public function professional(): Factory
    {
        return $this->state([
            'name' => 'Professional',
            'slug' => 'professional',
            'price' => 99,
            'max_courses' => 25,
            'max_students' => 500,
            'max_instructors' => 10,
            'storage_gb' => 100,
            'has_api_access' => true,
            'has_custom_domain' => true,
            'has_support_priority' => true,
        ]);
    }

    public function enterprise(): Factory
    {
        return $this->state([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 299,
            'max_courses' => 500,
            'max_students' => 5000,
            'max_instructors' => 100,
            'storage_gb' => 1000,
            'has_api_access' => true,
            'has_custom_domain' => true,
            'has_advanced_analytics' => true,
            'has_support_priority' => true,
        ]);
    }
}
