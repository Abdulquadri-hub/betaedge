<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\TenantPage;
use App\Models\OnboardingProcess;
use App\Contracts\Repositories\TenantPageRepositoryInterface;

class TenantPageRepository implements TenantPageRepositoryInterface
{
    public function generatePages(Tenant $tenant, OnboardingProcess $onboarding): void {

        $profile = $onboarding->getProfile();

        //landing page
        TenantPage::create([
            'tenant_id' => $tenant->id,
            'slug' => '/',
            'title' => 'Welcome to ' . $tenant->name,
            'page_type' => 'landing',
            'content' => $this->getLandingPageTemplate($tenant, $profile),
            'is_active' => true,
            'meta_title' => $tenant->name . ' - Online Learning Platform',
            'meta_description' => $profile['description'] ?? 'Quality education for every student'
        ]);

        // About Page
        TenantPage::create([
            'tenant_id' => $tenant->id,
            'slug' => 'about',
            'title' => 'About ' . $tenant->name,
            'page_type' => 'about',
            'content' => $this->getAboutPageTemplate($tenant, $profile),
            'is_active' => true,
            'meta_title' => 'About ' . $tenant->name,
            'meta_description' => 'Learn more about ' . $tenant->name
        ]);

        // Student Registration Page
        TenantPage::create([
            'tenant_id' => $tenant->id,
            'slug' => 'register/student',
            'title' => 'Student Registration',
            'page_type' => 'register_student',
            'content' => null, // Handled by Vue component
            'is_active' => true,
            'meta_title' => 'Student Registration - ' . $tenant->name,
            'meta_description' => 'Register as a student at ' . $tenant->name
        ]);

        //  Parent Registration Page
        TenantPage::create([
            'tenant_id' => $tenant->id,
            'slug' => 'register/parent',
            'title' => 'Parent Registration',
            'page_type' => 'register_parent',
            'content' => null, // Handled by Vue component
            'is_active' => true,
            'meta_title' => 'Parent Registration - ' . $tenant->name,
            'meta_description' => 'Register as a parent at ' . $tenant->name
        ]);
    }

    private function getLandingPageTemplate(Tenant $tenant, array $profile): array
    {
        return [
            'hero' => [
                'heading' => $profile['hero_heading'] ?? "Welcome to {$tenant->name}",
                'subheading' => $profile['hero_subheading'] ?? 'Quality education for every student',
                'image' => $profile['hero_image'] ?? null,
                'cta' => [
                    'text' => 'Enroll Now',
                    'link' => '/register/student'
                ]
            ],
            'features' => [
                [
                    'icon' => 'book',
                    'title' => 'Quality Courses',
                    'description' => 'Learn from comprehensive and well-structured courses'
                ],
                [
                    'icon' => 'users',
                    'title' => 'Expert Instructors',
                    'description' => 'Learn from experienced and dedicated teachers'
                ],
                [
                    'icon' => 'award',
                    'title' => 'Certifications',
                    'description' => 'Earn recognized certificates upon completion'
                ],
                [
                    'icon' => 'clock',
                    'title' => 'Flexible Learning',
                    'description' => 'Study at your own pace with 24/7 access'
                ]
            ],
            'stats' => [
                ['label' => 'Active Students', 'value' => '0+'],
                ['label' => 'Courses', 'value' => '0+'],
                ['label' => 'Instructors', 'value' => '0+'],
                ['label' => 'Success Rate', 'value' => '0%']
            ],
            'about_preview' => $profile['description'] ?? 'A leading educational institution committed to excellence.',
            'cta_section' => [
                'heading' => 'Ready to Start Learning?',
                'subheading' => 'Join thousands of students already learning with us',
                'button_text' => 'Get Started Today',
                'button_link' => '/register/student'
            ]
        ];
    }

    private function getAboutPageTemplate(Tenant $tenant, array $profile): array
    {
        return [
            'introduction' => $profile['description'] ?? "Welcome to {$tenant->name}",
            'mission' => $profile['mission'] ?? 'Our mission is to provide quality education to all students.',
            'vision' => $profile['vision'] ?? 'To be a leading educational institution.',
            'values' => $profile['values'] ?? [
                'Excellence in teaching',
                'Student-centered learning',
                'Innovation and creativity',
                'Integrity and respect'
            ],
            'history' => [
                'year_established' => $profile['year_established'] ?? now()->year,
                'story' => $profile['history'] ?? "Founded with the vision of making quality education accessible to all."
            ],
            'contact' => [
                'address' => $profile['address'] ?? '',
                'city' => $profile['city'] ?? '',
                'country' => $profile['country'] ?? 'Nigeria',
                'email' => $tenant->owner_email,
                'phone' => $profile['phone'] ?? ''
            ]
        ];
    }
    
}
