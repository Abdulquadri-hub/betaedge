<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Test drive the platform with basic features',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'currency' => 'NGN',
                'max_students' => 1,
                'max_instructors' => 1,
                'max_courses' => 2,
                'storage_gb' => 1,
                'features' => json_encode([
                    'Basic gradebook',
                    'Community support',
                    'Subdomain only'
                ]),
                'has_custom_domain' => false,
                'has_analytics' => false,
                'has_api_access' => false,
                'sort_order' => 1,
                'is_active' => true,
                'is_popular' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small schools just getting started',
                'price_monthly' => 15000.00,
                'price_yearly' => 150000.00, // Save ~17% (2 months free)
                'currency' => 'NGN',
                'max_students' => 50,
                'max_instructors' => 3,
                'max_courses' => 10,
                'storage_gb' => 10,
                'features' => json_encode([
                    'Email support',
                    'Attendance tracking',
                    'Parent accounts',
                    'Live class integration',
                    'Assignment management'
                ]),
                'has_custom_domain' => false,
                'has_analytics' => false,
                'has_api_access' => false,
                'sort_order' => 2,
                'is_active' => true,
                'is_popular' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing schools and academies',
                'price_monthly' => 35000.00,
                'price_yearly' => 350000.00, // Save ~17%
                'currency' => 'NGN',
                'max_students' => 250,
                'max_instructors' => 10,
                'max_courses' => 50,
                'storage_gb' => 50,
                'features' => json_encode([
                    'Priority support',
                    'Analytics dashboard',
                    'Bulk operations',
                    'Custom branding',
                    'Marketplace listing'
                ]),
                'has_custom_domain' => false,
                'has_analytics' => true,
                'has_api_access' => false,
                'sort_order' => 3,
                'is_active' => true,
                'is_popular' => true, // Most popular plan
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For established institutions needing full control',
                'price_monthly' => 75000.00,
                'price_yearly' => 750000.00, // Save ~17%
                'currency' => 'NGN',
                'max_students' => 0, // 0 = unlimited
                'max_instructors' => 0, // 0 = unlimited
                'max_courses' => 0, // 0 = unlimited
                'storage_gb' => 200,
                'features' => json_encode([
                    'Custom domain',
                    'API access',
                    'White-label branding',
                    'Dedicated support',
                    'Advanced analytics'
                ]),
                'has_custom_domain' => true,
                'has_analytics' => true,
                'has_api_access' => true,
                'sort_order' => 4,
                'is_active' => true,
                'is_popular' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('subscription_plans')->insert($plans);
    }
}