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
                'name' => "3 Months",
                'slug' => "3months",
                'billing_cycle' => 'quarterly',
                'description' => 'Perfect for small schools just getting started',
                'price_monthly' => 15000.00,
                'price_yearly' => 180000.00,
                'currency' => 'NGN',
                'max_students' => 0,
                'max_instructors' => 0,
                'max_courses' => 0,
                'storage_gb' => 0,
                'features' => json_encode([
                    'Email support',
                    'Unlimited students',
                    'Unlimited batches',
                    'All dashboard features',
                    'Marketplace listing'
                ]),
                'has_custom_domain' => false,
                'has_advanced_analytics' => false,
                'has_api_access' => false,
                'sort_order' => 1,
                'has_support_priority' => false,
                'is_active' => true,
                'is_popular' => false,
                'created_at' => $now,
                'activated_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => "6 Months",
                'slug' => "6months",
                'billing_cycle' => 'semi_annual',
                'description' => 'For growing schools and academies',
                'price_monthly' => 25000.00,
                'price_yearly' => 300000.00, 
                'currency' => 'NGN',
                'max_students' => 0,
                'max_instructors' => 0,
                'max_courses' => 0,
                'storage_gb' => 0,
                'features' => json_encode([
                    'Priority support',
                    'Unlimited students',
                    'Unlimited batches',
                    'All dashboard features',
                    'Marketplace listing'
                ]),
                'has_custom_domain' => false,
                'has_advanced_analytics' => true,
                'has_api_access' => false,
                'has_support_priority' => false,
                'sort_order' => 2,
                'is_active' => true,
                'is_popular' => true, // Most popular plan
                'created_at' => $now,
                'activated_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => " 1 Year",
                'slug' => "1year",
                'billing_cycle' => 'annual',
                'description' => 'For established institutions needing full control',
                'price_monthly' => 40000.00,
                'price_yearly' => 480000.00, 
                'currency' => 'NGN',
                'max_students' => 0, 
                'max_instructors' => 0, 
                'max_courses' => 0, 
                'storage_gb' => 0,
                'features' => json_encode([
                    'Custom domain',
                    'Dedicated support',
                    '3 & 6 Months features',
                    'All dashboard features', 
                ]),
                'has_custom_domain' => true,
                'has_advanced_analytics' => true,
                'has_api_access' => true,
                'has_support_priority' => true,
                'sort_order' => 3,
                'is_active' => true,
                'is_popular' => false,
                'created_at' => $now,
                'activated_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('subscription_plans')->insert($plans);
    }
}