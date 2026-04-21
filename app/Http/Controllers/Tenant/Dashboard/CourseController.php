<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');
        $status   = $request->get('status', '');

        $query = Course::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with('academicLevel')
            ->withCount(['batchCourses as batch_count']);

        if ($search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('course_code', 'like', "%{$search}%")
            );
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $paginated = $query->latest()->paginate(50);

        $courses = $paginated->getCollection()->map(fn ($c) => $this->formatCourse($c));

        $stats = [
            'total'    => Course::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'active'   => Course::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'draft'    => Course::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'draft')->count(),
            'archived' => Course::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'archived')->count(),
        ];

        return Inertia::render('School/Dashboard/Courses/Index', [
            'courses'    => $courses,
            'filters'    => compact('search', 'status'),
            'stats'      => $stats,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function single(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $materials = $this->mapMaterials($course->materials()->orderBy('display_order')->get());

        $batches = $course->batches()
            ->withoutGlobalScopes()
            ->where('batches.tenant_id', $tenantId)
            ->with(['batchCourses' => fn ($q) => $q->where('course_id', $course->id)->with('instructor.user')])
            ->get()
            ->map(fn ($b) => [
                'id'                 => $b->id,
                'name'               => $b->batch_name,
                'status'             => $b->status,
                'enrollment_status'  => $b->enrollment_status,
                'start_date'         => $b->start_date?->toDateString(),
                'end_date'           => $b->end_date?->toDateString(),
                'max_students'       => $b->max_students,
                'current_enrollment' => $b->activeStudents()->count(),
                'price'              => $b->price,
                'instructor_name'    => $b->batchCourses->first()?->instructor?->user?->full_name ?? '—',
            ]);

        return Inertia::render('School/Dashboard/Courses/Detail', [
            'course' => array_merge($this->formatCourse($course), [
                'materials' => $materials,
                'batches'   => $batches,
            ]),
        ]);
    }


    public function create()
    {
        $tenantId = (int) session('active_tenant_id');

        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'         => null,
            'materials'      => [],
            'academicLevels' => $this->getAcademicLevels($tenantId),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'course_code'      => 'required|string|max:50',
            'description'      => 'required|string',
            'academic_level_id'=> 'required|exists:academic_levels,id',
            'duration_weeks'   => 'nullable|integer|min:1',
            'thumbnail'        => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $tenantId = (int) session('active_tenant_id');

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store("tenants/{$tenantId}/course-thumbnails", 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $validated['tenant_id'] = $tenantId;
        $validated['status']    = 'draft';

        $course = Course::create($validated);

        return redirect("/dashboard/courses/{$course->id}/edit")
            ->with('success', 'Course created. Add materials below.');
    }

   
    public function edit(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $materials = $this->mapMaterials($course->materials()->orderBy('display_order')->get());

        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'         => $this->formatCourse($course),
            'materials'      => $materials,
            'academicLevels' => $this->getAcademicLevels($tenantId),
        ]);
    }

   
    public function update(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'course_code'      => 'sometimes|string|max:50',
            'description'      => 'nullable|string',
            'academic_level_id'=> 'nullable|exists:academic_levels,id',
            'duration_weeks'   => 'nullable|integer|min:1',
            'thumbnail'        => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store("tenants/{$tenantId}/course-thumbnails", 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $course->update($validated);

        return redirect()->back()->with('success', 'Course saved');
    }

    public function publish(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $course->update([
            'status'       => 'active',
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Course published');
    }

    public function archive(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $course->update(['status' => 'archived', 'is_published' => false]);

        return redirect()->back()->with('success', 'Course archived');
    }


    public function duplicate(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $copy = Course::create([
            'tenant_id'         => $tenantId,
            'title'             => $course->title . ' (Copy)',
            'course_code'       => $course->course_code . '_' . strtolower(substr(uniqid(), -4)),
            'description'       => $course->description,
            'academic_level_id' => $course->academic_level_id,
            'duration_weeks'    => $course->duration_weeks,
            'status'            => 'draft',
        ]);

        return redirect("/dashboard/courses/{$copy->id}/edit")
            ->with('success', 'Course duplicated as draft');
    }

    public function destroy(Request $request, $tenant, $courseId)
    {
        $tenantId = (int) session('active_tenant_id');
        $course   = $this->findCourse($courseId, $tenantId);

        if (!$course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        if ($course->batchCourses()->exists()) {
            return redirect()->back()
                ->withErrors(['message' => 'Remove this course from all batches before deleting']);
        }

        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return redirect('/dashboard/courses')->with('success', 'Course deleted');
    }


    private function findCourse(int|string $id, int $tenantId): ?Course
    {
        return Course::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with('academicLevel')
            ->find((int) $id);
    }

    private function getAcademicLevels(int $tenantId): array
    {
        return AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('level_number')
            ->get()
            ->map(fn ($l) => [
                'id'   => $l->id,
                'name' => $l->name,
                'code' => $l->code,
            ])
            ->toArray();
    }

    private function formatCourse(Course $course): array
    {
        return [
            'id'               => $course->id,
            'title'            => $course->title,
            'course_code'      => $course->course_code,
            'description'      => $course->description,
            'status'           => $course->status,
            'is_published'     => $course->is_published,
            'academic_level'   => $course->academicLevel?->name,
            'academic_level_id'=> $course->academic_level_id,
            'duration_weeks'   => $course->duration_weeks,
            'thumbnail'        => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
            'batch_count'      => $course->batch_count ?? $course->batchCourses()->count(),
            'created_at'       => $course->created_at,
        ];
    }


    private function mapMaterials($materials): array
    {
        return $materials->map(fn ($m) => [
            'id'          => $m->id,
            'course_id'   => $m->course_id,
            'title'       => $m->title,
            'type'        => $m->material_type === 'document' ? 'pdf' : $m->material_type,
            'module'      => $m->description ?? 'General',
            'url'         => $m->file_url ?? ($m->file_path ? asset('storage/' . $m->file_path) : null),
            'size_kb'     => $m->file_size_bytes ? (int) ($m->file_size_bytes / 1024) : 0,
            'downloads'   => $m->view_count ?? 0,
            'uploaded_at' => $m->created_at?->toDateString(),
        ])->toArray();
    }
}