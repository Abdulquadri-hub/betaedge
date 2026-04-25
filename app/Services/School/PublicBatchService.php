<?php

namespace App\Services\School;

use App\Contracts\Services\School\PublicBatchServiceInterface;
use App\Models\AcademicLevel;
use App\Models\Batch;
use App\Models\Tenant;

class PublicBatchService implements PublicBatchServiceInterface
{
    public function list(string $tenantSlug, array $filters = []): array
    {
        $tenantModel = $this->resolveTenant($tenantSlug);

        $query = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('enrollment_status', 'open')
            ->with([
                'batchCourses.course.academicLevel',
                'batchCourses.course.instructors.user',
            ]);

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('batch_name', 'like', "%{$search}%")
                    ->orWhereHas('batchCourses.course', fn ($c) =>
                        $c->where('title', 'like', "%{$search}%")
                    );
            });
        }

        if (!empty($filters['level']) && $filters['level'] !== 'all') {
            $level = $filters['level'];
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

        return [
            'tenant' => $this->formatTenant($tenantModel),
            'batches' => $batches,
            'levels' => $levels,
            'filters' => [
                'search' => $filters['q'] ?? '',
                'level' => $filters['level'] ?? '',
            ],
            'meta' => [
                'title' => "Enroll Now — {$tenantModel->name}",
                'description' => "Browse all available programmes at {$tenantModel->name}. Enroll today.",
                'canonical' => url()->current(),
            ],
        ];
    }

    public function get(string $tenantSlug, string $batchSlug): array
    {
        $tenantModel = $this->resolveTenant($tenantSlug);

        $batch = $this->resolveOpenBatch($tenantModel, $batchSlug);

        $currentCount = $batch->activeStudents()->count();
        $spotsLeft = max(0, $batch->max_students - $currentCount);

        $batchData = $this->formatPublicBatch($batch);
        $batchData['current_count'] = $currentCount;
        $batchData['spots_left'] = $spotsLeft;
        $batchData['is_full'] = $spotsLeft === 0;

        $materials = $batch->batchCourses
            ->flatMap(fn ($bc) => $bc->course?->materials()->where('is_published', true)->get() ?? collect())
            ->map(fn ($m) => [
                'title' => $m->title,
                'material_type' => $m->material_type,
                'module' => $m->description,
            ])
            ->take(6);

        return [
            'tenant' => $this->formatTenant($tenantModel),
            'batch' => $batchData,
            'materials' => $materials,
            'meta' => [
                'title' => "{$batch->batch_name} — {$tenantModel->name}",
                'description' => $batch->description
                    ?? "Enroll in {$batch->batch_name} at {$tenantModel->name}. {$spotsLeft} spots remaining.",
                'canonical' => url()->current(),
                'og_image' => $batch->batchCourses->first()?->course?->thumbnail
                    ? asset('storage/' . $batch->batchCourses->first()->course->thumbnail)
                    : null,
            ],
        ];
    }

    public function resolveOpenBatch(string $tenantSlug, string $batchSlug): Batch
    {
        
        $tenantModel = $this->resolveTenant($tenantSlug);
        
        return Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantModel->id)
            ->where('enrollment_status', 'open')
            ->whereRaw('LOWER(REPLACE(batch_code, " ", "-")) = ?', [strtolower($batchSlug)])
            ->with([
                'batchCourses.course.academicLevel',
                'batchCourses.course.instructors.user',
            ])
            ->firstOrFail();
    }

    public function prepareEnrollmentData(string $tenantSlug, string $batchSlug): array
    {
        $tenantModel = $this->resolveTenant($tenantSlug);
        $batch = $this->resolveOpenBatch($tenantSlug, $batchSlug);

        return [
            'tenant' => $this->formatTenant($tenantModel),
            'batch' => $this->formatPublicBatch($batch),
            'meta' => [
                'title' => "Enroll in {$batch->batch_name} — {$tenantModel->name}",
                'canonical' => url()->current(),
            ],
        ];
    }

    private function resolveTenant(string $slug): Tenant
    {
        
        return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function formatPublicBatch(Batch $batch): array
    {
        $currentCount = $batch->activeStudents()->count();
        $spotsLeft = max(0, $batch->max_students - $currentCount);

        $courses = $batch->batchCourses->map(fn ($bc) => [
            'id' => $bc->course_id,
            'title' => $bc->course?->title ?? '—',
            'description' => $bc->course?->description,
            'academic_level' => $bc->course?->academicLevel?->name,
            'thumbnail' => $bc->course?->thumbnail
                ? asset('storage/' . $bc->course->thumbnail)
                : null,
            'session_day' => $bc->session_day,
            'session_time' => $bc->session_time,
            'duration_minutes' => $bc->session_duration_minutes,
            'platform' => $bc->session_platform,
            'platform_label' => $bc->platform_label,
            'frequency' => $bc->session_frequency,
            'schedule_summary' => $bc->schedule_summary,
            'instructor' => $bc->course?->instructors->first()?->user ? [
                'name' => $bc->course->instructors->first()->user->full_name,
                'qualification' => $bc->course->instructors->first()->qualification ?? null,
                'bio' => $bc->course->instructors->first()->bio ?? null,
            ] : null,
            'materials' => $bc->course?->materials?->map(fn ($m) => [
                'title' => $m->title,
                'material_type' => $m->material_type,
                'module' => $m->description,
            ])->take(6)->values()->all() ?? [],
        ]);

        return [
            'id' => $batch->id,
            'name' => $batch->batch_name,
            'slug' => strtolower(str_replace([' ', '_'], '-', $batch->batch_code)),
            'description' => $batch->description,
            'start_date' => $batch->start_date?->format('F j, Y'),
            'start_date_iso' => $batch->start_date?->toDateString(),
            'end_date' => $batch->end_date?->format('F j, Y'),
            'end_date_iso' => $batch->end_date?->toDateString(),
            'max_students' => $batch->max_students,
            'current_count' => $currentCount,
            'spots_left' => $spotsLeft,
            'is_full' => $spotsLeft === 0,
            'price' => $batch->price,
            'price_formatted' => $batch->price !== null
                ? '₦' . number_format((float) $batch->price, 0)
                : 'Free',
            'price_note' => $batch->price_note,
            'status' => $batch->status,
            'enrollment_status' => $batch->enrollment_status,
            'whatsapp_link' => $batch->whatsapp_link,
            'courses' => $courses,
            'subject_count' => $courses->count(),
            'duration_weeks' => ($batch->start_date && $batch->end_date)
                ? (int) $batch->start_date->diffInWeeks($batch->end_date)
                : null,
        ];
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'subdomain' => $tenant->subdomain ?? $tenant->slug,
            'logo' => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
            'primary_color' => $tenant->primary_color,
            'tagline' => $tenant->tagline,
            'description' => $tenant->description,
            'phone' => $tenant->phone,
            'whatsapp' => $tenant->whatsapp,
        ];
    }
}
