<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Serves the school landing page at /{tenant}.teach.com/
 *
 * The {tenant} subdomain param is passed automatically by Laravel.
 */
class PublicPageController extends Controller
{
    public function landing(Request $request, string $tenant)
    {
        $tenantModel = $this->resolveTenant($tenant);

        // Published courses for this school
        $courses = Course::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('is_published', true)
            ->with('academicLevel')
            ->withCount(['batchCourses as batch_count'])
            ->orderBy('title')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'description'    => $c->description,
                'course_code'    => $c->course_code,
                'academic_level' => $c->academicLevel?->name,
                'duration_weeks' => $c->duration_weeks,
                'thumbnail'      => $c->thumbnail ? asset('storage/' . $c->thumbnail) : null,
                'batch_count'    => $c->batch_count,
                // Enroll goes to the batches listing page — no single-course enrollment
                'batches_url'    => '/batches',
            ]);

        // Published enrolling batches (for the "enroll now" section)
        $openBatches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('is_published', true)
            ->where('enrollment_status', 'open')
            ->with(['batchCourses.course'])
            ->orderBy('start_date')
            ->limit(6)
            ->get()
            ->map(fn ($b) => [
                'id'               => $b->id,
                'name'             => $b->batch_name,
                'slug'             => strtolower(str_replace([' ', '_'], '-', $b->batch_code)),
                'courses'          => $b->batchCourses->map(fn ($bc) => $bc->course?->title)->filter()->values(),
                'start_date'       => $b->start_date?->format('M j, Y'),
                'price_formatted'  => $b->price !== null ? '₦' . number_format((float) $b->price, 0) : 'Free',
                'max_students'     => $b->max_students,
                'current_count'    => $b->activeStudents()->count(),
                'enrollment_status'=> $b->enrollment_status,
            ]);

        // Active instructors
        $instructors = Instructor::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('status', 'active')
            ->with('user')
            ->limit(6)
            ->get()
            ->map(fn ($i) => [
                'id'            => $i->id,
                'name'          => $i->user?->full_name ?? '—',
                'qualification' => $i->qualification,
                'bio'           => $i->bio,
                'avatar'        => $i->avatar ? asset('storage/' . $i->avatar) : null,
                'courses_count' => $i->courses()->count(),
            ]);

        $stats = [
            'total_students'    => \App\Models\Student::withoutGlobalScopes()
                ->where('tenant_id', $tenantModel->id)->count(),
            'total_courses'     => Course::withoutGlobalScopes()
                ->where('tenant_id', $tenantModel->id)->where('is_published', true)->count(),
            'total_instructors' => Instructor::withoutGlobalScopes()
                ->where('tenant_id', $tenantModel->id)->where('status', 'active')->count(),
            'total_batches'     => Batch::withoutGlobalScopes()
                ->where('tenant_id', $tenantModel->id)->where('is_published', true)->count(),
        ];

        return Inertia::render('School/Public', [
            'tenant'       => $this->formatTenant($tenantModel),
            'courses'      => $courses,
            'open_batches' => $openBatches,
            'instructors'  => $instructors,
            'stats'        => $stats,
        ]);
    }

    private function resolveTenant(string $slug): Tenant
    {
        $t = Tenant::where('slug', $slug)->where('status', 'active')->first();
        if (!$t) abort(404, 'School not found');
        return $t;
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'name'            => $tenant->name,
            'slug'            => $tenant->slug,
            'subdomain'       => $tenant->subdomain ?? $tenant->slug,
            'logo'            => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
            'cover_image'     => $tenant->cover_image ? asset('storage/' . $tenant->cover_image) : null,
            'description'     => $tenant->description,
            'tagline'         => $tenant->tagline,
            'primary_color'   => $tenant->primary_color,
            'phone'           => $tenant->phone,
            'whatsapp'        => $tenant->whatsapp,
            'email'           => $tenant->owner_email,
            'website'         => $tenant->website,
            'city'            => $tenant->city,
            'address'         => $tenant->address,
            'school_type'     => $tenant->school_type,
            'year_established'=> $tenant->year_established,
        ];
    }
}