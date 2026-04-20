<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;

class BatchController extends Controller
{
    public function __construct(
        protected BatchRepositoryInterface $batchRepo,
        protected CourseRepositoryInterface $courseRepo,
    ) {}

    public function index(Request $request)
    {
        $filters   = $request->only(['search', 'status', 'course_id']);
        $paginated = $this->batchRepo->getPaginated(15, $filters);

        return Inertia::render('School/Dashboard/Batches/Index', [
            'batches'    => $paginated->getCollection()->map(fn ($b) => $this->formatBatch($b)),
            'filters'    => $filters,
            'stats'      => $this->batchRepo->getStats(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function single(Request $request, $tenant, $batchId)
    {
        $batch = $this->batchRepo->getById((int) $batchId);
        if (!$batch) return redirect('/dashboard/batches')->with('error', 'Batch not found');

        $students = $batch->activeStudents()->with('user')->get()->map(fn ($s) => [
            'id'              => $s->id,
            'name'            => $s->user?->full_name,
            'email'           => $s->user?->email,
            'type'            => ($s->date_of_birth && now()->diffInYears($s->date_of_birth) < 18) ? 'child' : 'adult',
            'attendance_rate' => $s->calculateAttendanceRate(),
            'grade'           => null,
            'enrolled_at'     => $s->pivot?->enrolled_at,
        ]);

        return Inertia::render('School/Dashboard/Batches/DetailPage', [
            'batch'    => $this->formatBatch($batch),
            'students' => $students,
        ]);
    }

    public function create(Request $request)
    {
        $courseId = $request->get('course_id');
        $course   = $courseId ? $this->courseRepo->getById((int) $courseId) : null;

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch'   => null,
            'course'  => $course ? $this->formatCourseForBatch($course) : null,
            'courses' => $this->courseRepo->getActive()->map(fn ($c) => $this->formatCourseForBatch($c)),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'course_id'     => 'required|exists:courses,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'max_students'  => 'required|integer|min:1|max:500',
            'whatsapp_link' => 'nullable|url',
            'description'   => 'nullable|string|max:1000',
            'notes'         => 'nullable|string|max:1000',
            // enrollment_status set separately — not in creation form
        ]);

        $validated['tenant_id']         = session('active_tenant_id');
        $validated['batch_name']        = $validated['name'];
        $validated['batch_code']        = strtoupper(
            substr(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name']), 0, 8)
        ) . '-' . strtoupper(substr(uniqid(), -4));
        $validated['status']            = 'planning';
        $validated['enrollment_status'] = 'open';

        $batch = $this->batchRepo->create($validated);

        return redirect("/dashboard/batches/{$batch->id}")
            ->with('success', 'Batch created successfully');
    }

    public function edit(Request $request, $tenant, $batchId)
    {
        $batch = $this->batchRepo->getById((int) $batchId);
        if (!$batch) return redirect('/dashboard/batches')->with('error', 'Batch not found');

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch'   => $this->formatBatch($batch),
            'course'  => $batch->course ? $this->formatCourseForBatch($batch->course) : null,
            'courses' => $this->courseRepo->getActive()->map(fn ($c) => $this->formatCourseForBatch($c)),
        ]);
    }

    public function update(Request $request, $tenant, $batchId)
    {
        $batch = $this->batchRepo->getById((int) $batchId);
        if (!$batch) return redirect('/dashboard/batches')->with('error', 'Batch not found');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'course_id'     => 'required|exists:courses,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'max_students'  => 'required|integer|min:1|max:500',
            'whatsapp_link' => 'nullable|url',
            'description'   => 'nullable|string|max:1000',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $validated['batch_name'] = $validated['name'];
        $this->batchRepo->update((int) $batchId, $validated);

        return redirect("/dashboard/batches/{$batchId}")->with('success', 'Batch updated');
    }

    public function toggleEnrollment(Request $request, $tenant, $batchId)
    {
        $batch = $this->batchRepo->getById((int) $batchId);
        if (!$batch) return redirect()->back()->withErrors(['message' => 'Batch not found']);

        $newEnrollmentStatus = $batch->enrollment_status === 'open' ? 'closed' : 'open';
        $this->batchRepo->update((int) $batchId, ['enrollment_status' => $newEnrollmentStatus]);

        $message = $newEnrollmentStatus === 'open'
            ? 'Enrollment is now open — students can enroll'
            : 'Enrollment is now closed — no new students can join';

        return redirect()->back()->with('success', $message);
    }

    public function delete(Request $request, $tenant, $batchId)
    {
        $batch = $this->batchRepo->getById((int) $batchId);
        if (!$batch) return redirect()->back()->withErrors(['message' => 'Batch not found']);

        if ($batch->activeStudents()->count() > 0) {
            return redirect()->back()->withErrors(['message' => 'Cannot delete a batch with active students']);
        }

        $this->batchRepo->delete((int) $batchId);
        return redirect('/dashboard/batches')->with('success', 'Batch deleted');
    }

    private function formatBatch($batch): array
    {
        $instructor = $batch->course?->primaryInstructor()->with('user')->first();

        return [
            'id'                 => $batch->id,
            'name'               => $batch->batch_name,
            'course_id'          => $batch->course_id,
            'course_name'        => $batch->course?->title,
            'course_code'        => $batch->course?->course_code,
            'academic_level'     => $batch->course?->academicLevel?->name,
            'instructor_name'    => $instructor?->user?->full_name ?? '—',
            'status'             => $batch->status,
            'enrollment_status'  => $batch->enrollment_status ?? 'open',
            'start_date'         => $batch->start_date?->toDateString(),
            'end_date'           => $batch->end_date?->toDateString(),
            'max_students'       => $batch->max_students,
            'current_enrollment' => $batch->activeStudents()->count(),
            'price_per_student'  => $batch->course?->price ?? 0,
            'whatsapp_link'      => $batch->whatsapp_link,
            'description'        => $batch->description,
            'notes'              => $batch->notes,
            'is_published'       => $batch->is_published,
            'schedule_summary'   => $batch->schedule_summary_attribute ?? null,
            'course_session_day' => $batch->course?->session_day,
            'course_session_time'=> $batch->course?->session_time,
            'course_duration_min'=> $batch->course?->session_duration_minutes,
            'course_platform'    => $batch->course?->session_platform,
        ];
    }

    private function formatCourseForBatch($course): array
    {
        return [
            'id'                       => $course->id,
            'title'                    => $course->title,
            'course_code'              => $course->course_code,
            'price'                    => $course->price,
            'duration_weeks'           => $course->duration_weeks,
            'academic_level'           => $course->academicLevel?->name,
            'session_day'              => $course->session_day,
            'session_time'             => $course->session_time,
            'session_duration_minutes' => $course->session_duration_minutes,
            'session_platform'         => $course->session_platform,
            'session_frequency'        => $course->session_frequency,
        ];
    }
}