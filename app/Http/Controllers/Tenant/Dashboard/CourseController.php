<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Contracts\Repositories\School\BatchRepositoryInterface;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepo,
        protected AcademicLevelRepositoryInterface $academicLevelRepo,
        protected BatchRepositoryInterface $batchRepo,
    ) {}

    public function index(Request $request)
    {
        $filters   = $request->only(['search', 'status']);
        $paginated = $this->courseRepo->getPaginated(50, $filters);
        $courses   = $paginated->getCollection()->map(fn ($c) => $this->formatCourse($c));

        return Inertia::render('School/Dashboard/Courses/Index', [
            'courses'       => $courses,
            'filters'       => $filters,
            'stats'         => $this->courseRepo->getStats(),
            'totalStudents' => $courses->sum('total_students'),
            'totalRevenue'  => $courses->sum('total_revenue'),
        ]);
    }

    public function single(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect('/dashboard/courses')->with('error', 'Course not found');

        $batches = $course->batches()->with('course.academicLevel')->get()->map(fn ($b) => [
            'id'                 => $b->id,
            'name'               => $b->batch_name,
            'status'             => $b->status,
            'enrollment_status'  => $b->enrollment_status,
            'start_date'         => $b->start_date?->toDateString(),
            'end_date'           => $b->end_date?->toDateString(),
            'max_students'       => $b->max_students,
            'current_enrollment' => $b->activeStudents()->count(),
        ]);

        $materials = $this->mapMaterials($course->materials()->orderBy('display_order')->get());

        return Inertia::render('School/Dashboard/Courses/Detail', [
            'course' => array_merge($this->formatCourse($course), [
                'batches'   => $batches,
                'materials' => $materials,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'         => null,
            'materials'      => [],
            'batches'        => [],
            'academicLevels' => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'title'                    => 'required|string|max:255',
            'course_code'              => 'required|string|max:50',
            'description'              => 'required|string',
            'academic_level_id'        => 'required|exists:academic_levels,id',
            'duration_weeks'           => 'nullable|integer|min:1',
            'price'                    => 'nullable|numeric|min:0',
            'max_students'             => 'nullable|integer|min:1',
            'session_frequency'        => 'nullable|string|max:50',
            'session_day'              => 'nullable|string|max:100',
            'session_time'             => 'nullable|string|max:10',
            'session_duration_minutes' => 'nullable|integer|min:15',
            'session_platform'         => 'nullable|string|max:50',
            'thumbnail'                => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $tenantId = session('active_tenant_id');
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store("tenants/{$tenantId}/course-thumbnails", 'public');
        }

        $course = $this->courseRepo->create($validated);

        return redirect("/dashboard/courses/{$course->id}/edit")
            ->with('success', 'Course created. Add materials and batches below.');
    }

    public function edit(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect('/dashboard/courses')->with('error', 'Course not found');

        $materials = $this->mapMaterials($course->materials()->orderBy('display_order')->get());

        $batches = $course->batches()->get()->map(fn ($b) => [
            'id'                 => $b->id,
            'course_id'          => $b->course_id,
            'name'               => $b->batch_name,
            'status'             => $b->status,
            'enrollment_status'  => $b->enrollment_status,
            'start_date'         => $b->start_date?->toDateString(),
            'end_date'           => $b->end_date?->toDateString(),
            'max_students'       => $b->max_students,
            'current_enrollment' => $b->activeStudents()->count(),
            'price_per_student'  => $course->price ?? 0,
        ]);

        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'         => $this->formatCourse($course),
            'materials'      => $materials,
            'batches'        => $batches,
            'academicLevels' => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function update(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect('/dashboard/courses')->with('error', 'Course not found');

        $validated = $request->validate([
            'title'                    => 'required|string|max:255',
            'course_code'              => 'sometimes|string|max:50',
            'description'              => 'nullable|string',
            'academic_level_id'        => 'nullable|exists:academic_levels,id',
            'duration_weeks'           => 'nullable|integer|min:1',
            'price'                    => 'nullable|numeric|min:0',
            'max_students'             => 'nullable|integer|min:1',
            'session_frequency'        => 'nullable|string|max:50',
            'session_day'              => 'nullable|string|max:100',
            'session_time'             => 'nullable|string|max:10',
            'session_duration_minutes' => 'nullable|integer|min:15',
            'session_platform'         => 'nullable|string|max:50',
            'thumbnail'                => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            if ($course->thumbnail) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->thumbnail);
            }
            $tenantId = session('active_tenant_id');
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store("tenants/{$tenantId}/course-thumbnails", 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $this->courseRepo->update($courseId, $validated);
        return redirect()->back()->with('success', 'Course saved successfully');
    }

    public function publish(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect()->back()->withErrors(['message' => 'Course not found']);

        $this->courseRepo->update($courseId, [
            'is_published' => true,
            'status'       => 'active',
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Course published');
    }

    public function archive(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect()->back()->withErrors(['message' => 'Course not found']);

        $this->courseRepo->update($courseId, ['status' => 'archived', 'is_published' => false]);
        return redirect()->back()->with('success', 'Course archived');
    }

    public function duplicate(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect()->back()->withErrors(['message' => 'Course not found']);

        $copy = $this->courseRepo->create([
            'title'                    => $course->title . ' (Copy)',
            'course_code'              => $course->course_code . '_' . strtolower(substr(uniqid(), -4)),
            'description'              => $course->description,
            'academic_level_id'        => $course->academic_level_id,
            'duration_weeks'           => $course->duration_weeks,
            'price'                    => $course->price,
            'max_students'             => $course->max_students,
            'session_frequency'        => $course->session_frequency,
            'session_day'              => $course->session_day,
            'session_time'             => $course->session_time,
            'session_duration_minutes' => $course->session_duration_minutes,
            'session_platform'         => $course->session_platform,
            'status'                   => 'draft',
        ]);

        return redirect("/dashboard/courses/{$copy->id}/edit")->with('success', 'Course duplicated as draft');
    }

    public function destroy(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById($courseId);
        if (!$course) return redirect()->back()->withErrors(['message' => 'Course not found']);

        if ($course->activeBatches()->count() > 0) {
            return redirect()->back()->withErrors(['message' => 'Cannot delete a course with active batches']);
        }

        $course->delete();
        return redirect('/dashboard/courses')->with('success', 'Course deleted');
    }

    private function formatCourse($course): array
    {
        return [
            'id'                       => $course->id,
            'title'                    => $course->title,
            'course_code'              => $course->course_code,
            'description'              => $course->description,
            'status'                   => $course->status,
            'is_published'             => $course->is_published,
            'academic_level'           => $course->academicLevel?->name,
            'academic_level_id'        => $course->academic_level_id,
            'duration_weeks'           => $course->duration_weeks,
            'price'                    => $course->price,
            'max_students'             => $course->max_students,
            'thumbnail'                => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
            'session_frequency'        => $course->session_frequency,
            'session_day'              => $course->session_day,
            'session_time'             => $course->session_time,
            'session_duration_minutes' => $course->session_duration_minutes,
            'session_platform'         => $course->session_platform,
            'total_batches'            => $course->batches()->count(),
            'active_batches'           => $course->activeBatches()->count(),
            'total_students'           => $course->activeEnrollments()->count(),
            'total_revenue'            => ($course->price ?? 0) * $course->activeEnrollments()->count(),
            'created_at'               => $course->created_at,
        ];
    }

    private function mapMaterials($materials): array
    {
        return $materials->map(fn ($m) => [
            'id'          => $m->id,
            'course_id'   => $m->course_id,
            'title'       => $m->title,
            'type'        => $m->material_type,        
            'module'      => $m->description ?? 'General', 
            'url'         => $m->url,                
            'size_kb'     => $m->file_size_bytes ? (int) ($m->file_size_bytes / 1024) : 0,
            'downloads'   => $m->view_count ?? 0,  
            'uploaded_at' => $m->created_at?->toDateString(),
        ])->toArray();
    }
}