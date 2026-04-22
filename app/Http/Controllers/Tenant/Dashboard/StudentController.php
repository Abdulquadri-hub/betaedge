<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Batch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');
        $batchId  = $request->get('batch_id', '');
        $status   = $request->get('status', '');

        $query = Student::withoutGlobalScopes()
            ->where('students.tenant_id', $tenantId)
            ->with(['user', 'academicLevel'])
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.*');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('students.student_id', 'like', "%{$search}%");
            });
        }

        if ($batchId) {
            $query->whereHas(
                'enrollments',
                fn($q) =>
                $q->where('batch_id', $batchId)->where('status', 'active')
            );
        }

        if ($status) {
            $query->where('students.enrollment_status', $status);
        }

        $students = $query->orderBy('users.email')->paginate(30);

        $batches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('start_date')
            ->pluck('batch_name', 'id');

        $stats = [
            'total'    => Student::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'active'   => Student::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('enrollment_status', 'active')->count(),
            'enrolled_this_month' => Enrollment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereMonth('enrollment_date', now()->month)
                ->whereYear('enrollment_date', now()->year)
                ->count(),
        ];

        return Inertia::render('School/Dashboard/Students/Index', [
            'students'   => $students->getCollection()->map(fn($s) => $this->formatStudent($s)),
            'filters'    => compact('search', 'batchId', 'status'),
            'batches'    => $batches,
            'stats'      => $stats,
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page'    => $students->lastPage(),
                'total'        => $students->total(),
            ],
        ]);
    }

    public function single(Request $request, $tenant, $studentId)
    {
        $tenantId = (int) session('active_tenant_id');

        $student = Student::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with([
                'user',
                'academicLevel',
                'parents.user',
            ])
            ->findOrFail($studentId);

        $enrollments = Enrollment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->with(['batch.batchCourses.course', 'payment'])
            ->orderByDesc('enrollment_date')
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'batch_id'        => $e->batch_id,
                'batch_name'      => $e->batch?->batch_name,
                'status'          => $e->status,
                'enrolled_at'     => $e->enrollment_date?->format('M j, Y'),
                'payment_status'  => $e->payment?->status ?? 'pending',
                'amount_paid'     => $e->payment?->amount_naira ?? 0,
                'courses'         => $e->batch?->batchCourses->map(fn($bc) => [
                    'title' => $bc->course?->title,
                ])->pluck('title')->filter()->join(', '),
            ]);

        return Inertia::render('School/Dashboard/Students/Detail', [
            'student'     => $this->formatStudent($student),
            'enrollments' => $enrollments,
        ]);
    }

    public function suspend(Request $request, $studentId)
    {
        $tenantId = (int) session('active_tenant_id');
        $student  = Student::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($studentId);

        $student->update(['enrollment_status' => 'inactive']);

        return redirect()->back()->with('success', 'Student suspended');
    }

    public function activate(Request $request, $studentId)
    {
        $tenantId = (int) session('active_tenant_id');
        $student  = Student::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($studentId);

        $student->update(['enrollment_status' => 'active']);

        return redirect()->back()->with('success', 'Student activated');
    }

    private function formatStudent(Student $student): array
    {
        $activeEnrollments = Enrollment::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->count();

        return [
            'id'                => $student->id,
            'student_id'        => $student->student_id,
            'name'              => $student->user?->full_name ?? '—',
            'email'             => $student->user?->email ?? '—',
            'phone'             => $student->user?->phone,
            'date_of_birth'     => $student->date_of_birth?->format('M j, Y'),
            'age'               => $student->date_of_birth
                ? (int) now()->diffInYears($student->date_of_birth)
                : null,
            'gender'            => $student->gender,
            'enrollment_status' => $student->enrollment_status,
            'academic_level'    => $student->academicLevel?->name,
            'active_enrollments' => $activeEnrollments,
            'has_parent'        => $student->parents()->exists(),
            'enrolled_at'       => $student->enrollment_date?->format('M j, Y'),
        ];
    }
}
