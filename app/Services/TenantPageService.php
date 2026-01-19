<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Contracts\Services\TenantPageServiceInterface;
use App\Contracts\Repositories\TenantPageRepositoryInterface;

class TenantPageService implements TenantPageServiceInterface
{
    public function __construct(
        protected TenantPageRepositoryInterface $repo,
    ) {}

    public function getLanding(Request $request): ?array
    {
        try {
            $tenant = $request->get('tenant');

            if (!$tenant) {
                abort(404, 'School not found');
            }

            $page = $this->repo->getPageContent($tenant, 'landing');

            $featuredCourses = Course::where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->with('academicLevel')
                ->limit(6)
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'description' => $course->description,
                        'thumbnail' => $course->thumbnail,
                        'price' => $course->price,
                        'level' => $course->academicLevel?->name ?? $course->level,
                        'duration_weeks' => $course->duration_weeks,
                    ];
                });

                
            $stats = [
                'students' => $tenant->students()->where('enrollment_status', 'active')->count(),
                'courses' => $tenant->courses()->where('status', 'active')->count(),
                'instructors' => $tenant->instructors()->where('status', 'active')->count(),
            ];

            // Contact info
            $contactInfo = [
                'phone' => $tenant->phone ?? 'N/A',
                'email' => $tenant->owner_email,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'country' => $tenant->country,
            ];

            return [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'description' => $tenant->description,
                    'logo' => $tenant->logo,
                    'primary_color' => $tenant->primary_color ?? '#3B82F6',
                    'secondary_color' => $tenant->secondary_color ?? '#10B981',
                    'subdomain' => $tenant->subdomain,
                    'owner_email' => $tenant->owner_email,
                ],
                'pageContent' => $page?->content ?? $this->getDefaultLandingContent($tenant),
                'stats' => $stats,
                'featuredCourses' => $featuredCourses,
                'contactInfo' => $contactInfo,
            ];
        } catch (\Throwable $th) {
            Log::error('Error fetching landing page', [
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    public function getAbout(Request $request): ?array
    {
        try {
            $tenant = $request->get('tenant');

            if (!$tenant) {
                abort(404, 'School not found');
            }

            $page = $this->repo->getPageContent($tenant, 'about');

            return [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo' => $tenant->logo,
                    'primary_color' => $tenant->primary_color ?? '#3B82F6',
                    'secondary_color' => $tenant->secondary_color ?? '#10B981',
                ],
                'pageContent' => $page?->content ?? $this->getDefaultAboutContent($tenant),
            ];
        } catch (\Throwable $th) {
            Log::error('Error fetching about page', [
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    public function getRegisterStudent(Request $request): ?array
    {
        try {
            $tenant = $request->get('tenant');

            if (!$tenant) {
                abort(404, 'School not found');
            }

            return [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo' => $tenant->logo,
                    'primary_color' => $tenant->primary_color ?? '#3B82F6',
                ],
                'academicLevels' => $tenant->academicLevels()
                    ->where('is_active', true)
                    ->ordered()
                    ->get()
                    ->map(fn($level) => [
                        'id' => $level->id,
                        'name' => $level->name,
                        'grade_number' => $level->grade_number,
                    ]),
            ];
        } catch (\Throwable $th) {
            Log::error('Error fetching landing page', [
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    public function saveStudent() {}

    public function getRegisterParent(Request $request): ?array
    {
        try {
            $tenant = $request->get('tenant');

            if (!$tenant) {
                abort(404, 'School not found');
            }

            return [
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo' => $tenant->logo,
                    'primary_color' => $tenant->primary_color ?? '#3B82F6',
                ],
            ];
        } catch (\Throwable $th) {
            Log::error('Error fetching landing page', [
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    public function saveParent() {}

    private function getDefaultLandingContent($tenant): array
    {
        return [
            'hero' => [
                'heading' => "Welcome to {$tenant->name}",
                'subheading' => 'Quality education for every student',
            ],
            'features' => [
                [
                    'icon' => 'book',
                    'title' => 'Quality Courses',
                    'description' => 'Comprehensive and well-structured courses'
                ],
                [
                    'icon' => 'video',
                    'title' => 'Live Classes',
                    'description' => 'Interactive online sessions'
                ],
                [
                    'icon' => 'check',
                    'title' => 'Certifications',
                    'description' => 'Recognized certificates'
                ],
                [
                    'icon' => 'trending',
                    'title' => 'Progress Tracking',
                    'description' => 'Monitor your learning journey'
                ],
            ],
            'about_preview' => $tenant->description ?? 'A leading educational institution.',
            'cta_section' => [
                'heading' => 'Ready to Start Learning?',
                'subheading' => "Join thousands of students already learning at {$tenant->name}",
            ],
        ];
    }

    private function getDefaultAboutContent($tenant): array
    {
        return [
            'introduction' => "Welcome to {$tenant->name}",
            'mission' => 'To provide quality education to all students.',
            'vision' => 'To be a leading educational institution.',
            'values' => [
                'Excellence in teaching',
                'Student-centered learning',
                'Innovation and creativity',
            ],
            'contact' => [
                'email' => $tenant->owner_email,
                'city' => $tenant->city,
                'country' => $tenant->country,
            ],
        ];
    }
}
