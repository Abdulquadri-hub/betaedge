<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchCourse;
use App\Models\Course;
use App\Models\Instructor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchController extends Controller
{

    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');
        $status   = $request->get('status', '');

        $query = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['batchCourses.course', 'batchCourses.instructor.user'])
            ->withCount([
                'students as total_students',
                'students as active_students_count' => fn($q) => $q->where('batch_student.status', 'active'),
            ]);

        if ($search) {
            $query->where('batch_name', 'like', "%{$search}%");
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $batches = $query->latest()->paginate(15);

        $stats = [
            'total'     => Batch::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'active'    => Batch::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'planning'  => Batch::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'planning')->count(),
            'enrolling' => Batch::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('enrollment_status', 'open')->count(),
            'total_students' => DB::table('batch_student')
                ->join('batches', 'batches.id', '=', 'batch_student.batch_id')
                ->where('batches.tenant_id', $tenantId)
                ->where('batch_student.status', 'active')
                ->count(),
        ];

        return Inertia::render('School/Dashboard/Batches/Index', [
            'batches'    => $batches->getCollection()->map(fn($b) => $this->formatBatch($b)),
            'filters'    => compact('search', 'status'),
            'stats'      => $stats,
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'last_page'    => $batches->lastPage(),
                'total'        => $batches->total(),
            ],
        ]);
    }


    public function single(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect('/dashboard/batches')->with('error', 'Batch not found');
        }

        $students = $batch->activeStudents()
            ->with('user')
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'name'            => $s->full_name ?? '—',
                'email'           => $s->user?->email ?? '—',
                'type'            => ($s->date_of_birth && Carbon::parse($s->date_of_birth)->age < 18)
                                        ? 'child' : 'adult',
                'attendance_rate' => $s->calculateAttendanceRate(),
                'grade'           => null,
                'enrolled_at'     => $s->pivot?->enrolled_at,
            ]);

        $courses = $batch->batchCourses()
            ->with(['course', 'instructor.user'])
            ->orderBy('display_order')
            ->get()
            ->map(fn ($bc) => [
                'id'               => $bc->id,
                'course_id'        => $bc->course_id,
                'title'            => $bc->course?->title ?? '—',
                'description'      => $bc->course?->description,
                'course_code'      => $bc->course?->course_code,
                'status'           => $bc->course?->status ?? 'active',
                'duration_weeks'   => $bc->course?->duration_weeks,
                'instructor'       => $bc->instructor?->user?->full_name ?? 'No instructor assigned',
                'schedule_summary' => $bc->schedule_summary,
                'platform_label'   => $bc->platform_label,
                'session_day'      => $bc->session_day,
                'session_time'     => $bc->session_time,
                'display_order'    => $bc->display_order,
            ]);

        return Inertia::render('School/Dashboard/Batches/DetailPage', [
            'batch'    => $this->formatBatch($batch),
            'students' => $students,
            'courses'  => $courses,
        ]);
    }


    public function create(Request $request)
    {
        $tenantId    = (int) session('active_tenant_id');
        $courses     = $this->getCoursesForTenant($tenantId);
        $instructors = $this->getInstructorsForTenant($tenantId);

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch'       => null,
            'courses'     => $courses,
            'instructors' => $instructors,
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                               => 'required|string|max:255',
            'description'                        => 'nullable|string|max:1000',
            'start_date'                         => 'required|date',
            'end_date'                           => 'required|date|after:start_date',
            'max_students'                       => 'required|integer|min:1|max:1000',
            'price'                              => 'nullable|numeric|min:0',
            'price_note'                         => 'nullable|string|max:255',
            'whatsapp_link'                      => 'nullable|url',
            'notes'                              => 'nullable|string|max:1000',
            'courses'                            => 'nullable|array',
            'courses.*.course_id'                => 'required|exists:courses,id',
            'courses.*.session_day'              => 'nullable|string|max:100',
            'courses.*.session_time'             => 'nullable|string|max:10',
            'courses.*.session_duration_minutes' => 'nullable|integer|min:15|max:480',
            'courses.*.session_platform'         => 'nullable|string|max:50',
            'courses.*.session_frequency'        => 'nullable|string|max:50',
        ]);

        $tenantId = (int) session('active_tenant_id');

        $batch = Batch::create([
            'tenant_id'         => $tenantId,
            'batch_name'        => $validated['name'],
            'batch_code'        => $this->generateBatchCode($validated['name']),
            'description'       => $validated['description']   ?? null,
            'start_date'        => $validated['start_date'],
            'end_date'          => $validated['end_date'],
            'max_students'      => $validated['max_students'],
            'price'             => $validated['price']         ?? null,
            'price_note'        => $validated['price_note']    ?? null,
            'whatsapp_link'     => $validated['whatsapp_link'] ?? null,
            'notes'             => $validated['notes']         ?? null,
            'status'            => 'planning',
            'enrollment_status' => 'open',
            'is_published'      => false,
        ]);

        $this->syncBatchCourses($batch, $tenantId, $validated['courses'] ?? []);

        return redirect("/dashboard/batches/{$batch->id}")
            ->with('success', 'Batch created successfully');
    }


    public function edit(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect('/dashboard/batches')->with('error', 'Batch not found');
        }

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch'       => $this->formatBatch($batch),
            'courses'     => $this->getCoursesForTenant($tenantId),
            'instructors' => $this->getInstructorsForTenant($tenantId),
        ]);
    }


    public function update(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect('/dashboard/batches')->with('error', 'Batch not found');
        }

        $validated = $request->validate([
            'name'                               => 'required|string|max:255',
            'description'                        => 'nullable|string|max:1000',
            'start_date'                         => 'required|date',
            'end_date'                           => 'required|date|after:start_date',
            'max_students'                       => 'required|integer|min:1|max:1000',
            'price'                              => 'nullable|numeric|min:0',
            'price_note'                         => 'nullable|string|max:255',
            'whatsapp_link'                      => 'nullable|url',
            'notes'                              => 'nullable|string|max:1000',
            'courses'                            => 'nullable|array',
            'courses.*.course_id'                => 'required|exists:courses,id',
            'courses.*.session_day'              => 'nullable|string|max:100',
            'courses.*.session_time'             => 'nullable|string|max:10',
            'courses.*.session_duration_minutes' => 'nullable|integer|min:15|max:480',
            'courses.*.session_platform'         => 'nullable|string|max:50',
            'courses.*.session_frequency'        => 'nullable|string|max:50',
        ]);

        $batch->update([
            'batch_name'    => $validated['name'],
            'description'   => $validated['description']   ?? null,
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'],
            'max_students'  => $validated['max_students'],
            'price'         => $validated['price']         ?? null,
            'price_note'    => $validated['price_note']    ?? null,
            'whatsapp_link' => $validated['whatsapp_link'] ?? null,
            'notes'         => $validated['notes']         ?? null,
        ]);

        $this->syncBatchCourses($batch, $tenantId, $validated['courses'] ?? []);

        return redirect("/dashboard/batches/{$batchId}")
            ->with('success', 'Batch updated');
    }


    public function toggleEnrollment(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect()->back()->withErrors(['message' => 'Batch not found']);
        }

        $new = $batch->enrollment_status === 'open' ? 'closed' : 'open';
        $batch->update(['enrollment_status' => $new]);

        return redirect()->back()->with(
            'success',
            $new === 'open' ? 'Enrollment opened' : 'Enrollment closed'
        );
    }


    public function publish(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect()->back()->withErrors(['message' => 'Batch not found']);
        }

        if (!$batch->canBePublished()) {
            return redirect()->back()
                ->withErrors(['message' => 'Attach at least one course before publishing']);
        }

        $batch->publish();

        return redirect()->back()->with('success', 'Batch published — students can now see and enroll');
    }


    public function delete(Request $request, $tenant, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');
        $batch    = $this->findBatch($batchId, $tenantId);

        if (!$batch) {
            return redirect()->back()->withErrors(['message' => 'Batch not found']);
        }

        if ($batch->activeStudents()->count() > 0) {
            return redirect()->back()
                ->withErrors(['message' => 'Cannot delete a batch with active students']);
        }

        $batch->batchCourses()->delete();
        $batch->delete();

        return redirect('/dashboard/batches')->with('success', 'Batch deleted');
    }


    // ── Private helpers ────────────────────────────────────────────────────────

    private function findBatch(int|string $id, int $tenantId): ?Batch
    {
        return Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with([
                'batchCourses.course.academicLevel',
                'batchCourses.instructor.user',
            ])
            ->find((int) $id);
    }

    private function getCoursesForTenant(int $tenantId): array
    {
        return Course::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'archived')
            ->with('academicLevel')
            ->orderBy('title')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'course_code'    => $c->course_code,
                'duration_weeks' => $c->duration_weeks,
                'academic_level' => $c->academicLevel?->name,
                'thumbnail'      => $c->thumbnail ? asset('storage/' . $c->thumbnail) : null,
            ])
            ->toArray();
    }

    private function getInstructorsForTenant(int $tenantId): array
    {
        return Instructor::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->map(fn ($i) => [
                'id'   => $i->id,
                'name' => $i->user?->full_name ?? '—',
            ])
            ->toArray();
    }

    private function syncBatchCourses(Batch $batch, int $tenantId, array $courses): void
    {
        $incomingCourseIds = collect($courses)
            ->pluck('course_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        BatchCourse::where('batch_id', $batch->id)
            ->whereNotIn('course_id', $incomingCourseIds)
            ->delete();

        foreach ($courses as $index => $slot) {
            $courseId = (int) $slot['course_id'];

            BatchCourse::updateOrCreate(
                ['batch_id' => $batch->id, 'course_id' => $courseId],
                [
                    'tenant_id'                => $tenantId,
                    'session_day'              => $slot['session_day']              ?? null,
                    'session_time'             => $slot['session_time']             ?? null,
                    'session_duration_minutes' => $slot['session_duration_minutes'] ?? 90,
                    'session_platform'         => $slot['session_platform']         ?? null,
                    'session_frequency'        => $slot['session_frequency']        ?? null,
                    'display_order'            => $index,
                ]
            );
        }
    }

    private function formatBatch(Batch $batch): array
    {
        $batchCourses = $batch->batchCourses->map(fn ($bc) => [
            'id'                       => $bc->id,
            'course_id'                => $bc->course_id,
            'course_title'             => $bc->course?->title ?? '—',
            'course_code'              => $bc->course?->course_code ?? '—',
            'academic_level'           => $bc->course?->academicLevel?->name,
            'instructor_id'            => $bc->instructor_id,
            'instructor_name'          => $bc->instructor?->user?->full_name ?? 'No instructor',
            'session_day'              => $bc->session_day,
            'session_time'             => $bc->session_time,
            'session_duration_minutes' => $bc->session_duration_minutes,
            'session_platform'         => $bc->session_platform,
            'session_frequency'        => $bc->session_frequency,
            'schedule_summary'         => $bc->schedule_summary,
            'platform_label'           => $bc->platform_label,
            'display_order'            => $bc->display_order,
        ]);

        
        $scheduleLines = $batchCourses
            ->filter(fn ($bc) => !empty($bc['session_day']))
            ->map(fn ($bc) => $bc['schedule_summary'])
            ->filter()
            ->unique()
            ->values()
            ->all(); // plain array so JSON serializes correctly

        $currentEnrollment = $batch->activeStudents_count ?? $batch->activeStudents()->count();

        return [
            'id'                 => $batch->id,
            'name'               => $batch->batch_name,
            'batch_code'         => $batch->batch_code,
            'description'        => $batch->description,
            'status'             => $batch->status,
            'enrollment_status'  => $batch->enrollment_status ?? 'open',
            'start_date'         => $batch->start_date?->toDateString(),
            'end_date'           => $batch->end_date?->toDateString(),
            'max_students'       => $batch->max_students,
            'current_enrollment' => $currentEnrollment,
            'price'              => $batch->price,
            'price_note'         => $batch->price_note,
            'whatsapp_link'      => $batch->whatsapp_link,
            'notes'              => $batch->notes,
            'is_published'       => $batch->is_published,
            'can_publish'        => $batchCourses->isNotEmpty(),
            'batch_courses'      => $batchCourses,
            'subject_names'      => $batchCourses->pluck('course_title')->join(', '),
            'schedule_lines'     => $scheduleLines,
            'has_schedule'       => count($scheduleLines) > 0,
        ];
    }

    private function generateBatchCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 6));
        $code = $base . '-' . strtoupper(substr(uniqid(), -4));

        while (Batch::withoutGlobalScopes()->where('batch_code', $code)->exists()) {
            $code = $base . '-' . strtoupper(substr(uniqid(), -4));
        }

        return $code;
    }
}