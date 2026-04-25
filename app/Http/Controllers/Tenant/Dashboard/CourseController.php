<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Repositories\School\MaterialRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepo,
        protected AcademicLevelRepositoryInterface $academicLevelRepo,
        protected MaterialRepositoryInterface $materialRepo
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        $paginated = $this->courseRepo->getPaginated(50, [
            'search' => $search,
            'status' => $status,
        ]);

        $courses = $paginated->getCollection()->map(fn ($c) => $this->formatCourse($c));

        $stats = $this->courseRepo->getStats();

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
        $course = $this->courseRepo->getWithRelations((int) $courseId);

        if (! $course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $materials = $this->mapMaterials($course->materials->sortBy('display_order'));

        $batches = $course->batches
            ->where('tenant_id', session('active_tenant_id'))
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
                'instructor_name'    => $b->batchCourses->first()?->course?->instructors->first()?->user?->full_name ?? '—',
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

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store("tenants/" . session('active_tenant_id') . "/course-thumbnails", 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $validated['status'] = 'draft';

        $course = $this->courseRepo->create($validated);

        return redirect("/dashboard/courses/{$course->id}/edit")
            ->with('success', 'Course created. Add materials below.');
    }

    public function edit(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getWithRelations((int) $courseId);

        if (! $course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $materials = $this->mapMaterials($course->materials->sortBy('display_order'));

        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'         => $this->formatCourse($course),
            'materials'      => $materials,
            'academicLevels' => $this->getAcademicLevels((int) session('active_tenant_id')),
        ]);
    }

    public function update(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById((int) $courseId);

        if (! $course) {
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
                ->store("tenants/" . session('active_tenant_id') . "/course-thumbnails", 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $this->courseRepo->update($course->id, $validated);

        return redirect()->back()->with('success', 'Course saved');
    }

    public function publish(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById((int) $courseId);

        if (! $course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $this->courseRepo->update($course->id, [
            'status'       => 'active',
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Course published');
    }

    public function archive(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById((int) $courseId);

        if (! $course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $this->courseRepo->update($course->id, [
            'status' => 'archived',
            'is_published' => false,
        ]);

        return redirect()->back()->with('success', 'Course archived');
    }

    public function duplicate(Request $request, $tenant, $courseId)
    {
        $course = $this->courseRepo->getById((int) $courseId);

        if (! $course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        $copy = $this->courseRepo->create([
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
        $course = $this->courseRepo->getWithRelations((int) $courseId);

        if (! $course) {
            return redirect()->back()->withErrors(['message' => 'Course not found']);
        }

        if ($course->batchCourses()->exists()) {
            return redirect()->back()
                ->withErrors(['message' => 'Remove this course from all batches before deleting']);
        }

        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $this->courseRepo->delete($course->id);

        return redirect('/dashboard/courses')->with('success', 'Course deleted');
    }

    private function getAcademicLevels(int $tenantId): array
    {
        return $this->academicLevelRepo->getActive();
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