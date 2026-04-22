<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Batch;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicBatchController extends Controller
{
    // ── Batch listing ──────────────────────────────────────────────────────────

    public function index(Request $request, string $tenant)
    {
        $tenantModel = $this->resolveTenant($tenant);
        $search      = $request->get('q', '');
        $level       = $request->get('level', '');

        $query = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('enrollment_status', 'open')
            ->with([
                'batchCourses.course.academicLevel',
                'batchCourses.instructor.user',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_name', 'like', "%{$search}%")
                  ->orWhereHas('batchCourses.course', fn ($c) =>
                      $c->where('title', 'like', "%{$search}%")
                  );
            });
        }

        if ($level && $level !== 'all') {
            $query->whereHas('batchCourses.course.academicLevel', fn ($q) =>
                $q->where('name', $level)
            );
        }

        $batches = $query
            ->orderByRaw("FIELD(enrollment_status, 'open', 'closed')")
            ->orderBy('start_date')
            ->get()
            ->map(fn ($b) => $this->formatPublicBatch($b));

        $levels = AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('is_active', true)
            ->orderBy('level_number')
            ->pluck('name');

        return Inertia::render('School/Public/Batches/Index', [
            'tenant'  => $this->formatTenant($tenantModel),
            'batches' => $batches,
            'levels'  => $levels,
            'filters' => compact('search', 'level'),
            'meta'    => [
                'title'       => "Enroll Now — {$tenantModel->name}",
                'description' => "Browse all available programmes at {$tenantModel->name}. Enroll today.",
                'canonical'   => $request->url(),
            ],
        ]);
    }

    // ── Batch detail ───────────────────────────────────────────────────────────

    public function show(Request $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->resolveTenant($tenant);

        $batch = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('enrollment_status', 'open')
            ->whereRaw('LOWER(REPLACE(batch_code, " ", "-")) = ?', [strtolower($batchSlug)])
            ->with([
                'batchCourses.course.academicLevel',
                'batchCourses.course.materials',
                'batchCourses.instructor.user',
            ])
            ->first();

        if (!$batch) {
            // Friendly redirect to batch list instead of 404
            // return redirect("/{$batch?->batch_code ?? 'batches'}")->with(
            //     'error', 'Programme not found.'
            // );
        }

        $currentCount = $batch->activeStudents()->count();
        $spotsLeft    = max(0, $batch->max_students - $currentCount);

        $batchData = $this->formatPublicBatch($batch);
        $batchData['current_count']  = $currentCount;
        $batchData['spots_left']     = $spotsLeft;
        $batchData['is_full']        = $spotsLeft === 0;

        $materials = $batch->batchCourses
            ->flatMap(fn ($bc) => $bc->course?->materials()->where('is_published', true)->get() ?? collect())
            ->map(fn ($m) => [
                'title'         => $m->title,
                'material_type' => $m->material_type,
                'module'        => $m->description,
            ])
            ->take(6);

        return Inertia::render('School/Public/Batches/Show', [
            'tenant'    => $this->formatTenant($tenantModel),
            'batch'     => $batchData,
            'materials' => $materials,
            'meta'      => [
                'title'       => "{$batch->batch_name} — {$tenantModel->name}",
                'description' => $batch->description
                    ?? "Enroll in {$batch->batch_name} at {$tenantModel->name}. {$spotsLeft} spots remaining.",
                'canonical'   => $request->url(),
                'og_image'    => $batch->batchCourses->first()?->course?->thumbnail
                    ? asset('storage/' . $batch->batchCourses->first()->course->thumbnail)
                    : null,
            ],
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Resolve tenant from the subdomain slug ({tenant} route param).
     * The tenant middleware already validates the tenant, so this is
     * just a lookup — abort(404) if somehow invalid.
     */
    private function resolveTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            abort(404, 'School not found');
        }

        return $tenant;
    }

    private function formatPublicBatch(Batch $batch): array
    {
        $currentCount = $batch->activeStudents()->count();
        $spotsLeft    = max(0, $batch->max_students - $currentCount);

        $courses = $batch->batchCourses->map(fn ($bc) => [
            'id'              => $bc->course_id,
            'title'           => $bc->course?->title ?? '—',
            'description'     => $bc->course?->description,
            'academic_level'  => $bc->course?->academicLevel?->name,
            'thumbnail'       => $bc->course?->thumbnail
                ? asset('storage/' . $bc->course->thumbnail)
                : null,
            'session_day'     => $bc->session_day,
            'session_time'    => $bc->session_time,
            'duration_minutes'=> $bc->session_duration_minutes,
            'platform'        => $bc->session_platform,
            'platform_label'  => $bc->platform_label,
            'frequency'       => $bc->session_frequency,
            'schedule_summary'=> $bc->schedule_summary,
            'instructor'      => $bc->instructor?->user ? [
                'name'          => $bc->instructor->user->full_name,
                'qualification' => $bc->instructor->qualification ?? null,
                'bio'           => $bc->instructor->bio ?? null,
            ] : null,
        ]);

        return [
            'id'               => $batch->id,
            'name'             => $batch->batch_name,
            'slug'             => strtolower(str_replace([' ', '_'], '-', $batch->batch_code)),
            'description'      => $batch->description,
            'start_date'       => $batch->start_date?->format('F j, Y'),
            'start_date_iso'   => $batch->start_date?->toDateString(),
            'end_date'         => $batch->end_date?->format('F j, Y'),
            'end_date_iso'     => $batch->end_date?->toDateString(),
            'max_students'     => $batch->max_students,
            'current_count'    => $currentCount,
            'spots_left'       => $spotsLeft,
            'is_full'          => $spotsLeft === 0,
            'price'            => $batch->price,
            'price_formatted'  => $batch->price !== null
                ? '₦' . number_format((float) $batch->price, 0)
                : 'Free',
            'price_note'       => $batch->price_note,
            'status'           => $batch->status,
            'enrollment_status'=> $batch->enrollment_status,
            'whatsapp_link'    => $batch->whatsapp_link,
            'courses'          => $courses,
            'subject_count'    => $courses->count(),
            'duration_weeks'   => ($batch->start_date && $batch->end_date)
                ? (int) $batch->start_date->diffInWeeks($batch->end_date)
                : null,
        ];
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'name'          => $tenant->name,
            'slug'          => $tenant->slug,
            'subdomain'     => $tenant->subdomain ?? $tenant->slug,
            'logo'          => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
            'primary_color' => $tenant->primary_color,
            'tagline'       => $tenant->tagline,
            'description'   => $tenant->description,
            'phone'         => $tenant->phone,
            'whatsapp'      => $tenant->whatsapp,
        ];
    }
}