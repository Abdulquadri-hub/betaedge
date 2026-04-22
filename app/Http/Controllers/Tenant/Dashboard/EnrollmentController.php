<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');
        $batchId  = $request->get('batch_id', '');
        $status   = $request->get('status', '');

        $query = Enrollment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['student.user', 'batch', 'payment'])
            ->orderByDesc('enrollment_date');

        if ($search) {
            $query->whereHas('student.user', fn ($q) =>
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($batchId) $query->where('batch_id', $batchId);
        if ($status)  $query->where('status', $status);

        $enrollments = $query->paginate(30);

        return Inertia::render('School/Dashboard/Enrollments/Index', [
            'enrollments' => $enrollments->getCollection()->map(fn ($e) => [
                'id'              => $e->id,
                'student_name'    => $e->student?->user?->full_name ?? '—',
                'student_email'   => $e->student?->user?->email ?? '—',
                'batch_name'      => $e->batch?->batch_name ?? '—',
                'status'          => $e->status,
                'payment_status'  => $e->payment?->status ?? 'pending',
                'amount_paid'     => $e->payment?->amount_naira ?? 0,
                'enrollment_route'=> $e->enrollment_route,
                'enrolled_at'     => $e->enrollment_date?->format('M j, Y'),
            ]),
            'filters'    => compact('search', 'batchId', 'status'),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'last_page'    => $enrollments->lastPage(),
                'total'        => $enrollments->total(),
            ],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $tenantId   = (int) session('active_tenant_id');
        $enrollment = Enrollment::withoutGlobalScopes()->where('tenant_id', $tenantId)->findOrFail($id);
        $enrollment->markActive();
        return redirect()->back()->with('success', 'Enrollment approved');
    }

    public function reject(Request $request, $id)
    {
        $tenantId   = (int) session('active_tenant_id');
        $enrollment = Enrollment::withoutGlobalScopes()->where('tenant_id', $tenantId)->findOrFail($id);
        $enrollment->update(['status' => 'dropped']);
        return redirect()->back()->with('success', 'Enrollment rejected');
    }
}