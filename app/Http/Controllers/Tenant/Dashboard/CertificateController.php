<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateController extends Controller
{
    // ── Dashboard: List ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');
        $batchId  = $request->get('batch_id', '');

        $query = Certificate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['student.user', 'batch'])
            ->orderByDesc('issued_at');

        if ($search) {
            $query->whereHas('student.user', fn ($q) =>
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )->orWhere('certificate_code', 'like', "%{$search}%");
        }

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        $certs = $query->paginate(30);

        $batches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->pluck('batch_name', 'id');

        $stats = [
            'total'           => Certificate::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'this_month'      => Certificate::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->whereMonth('issued_at', now()->month)->count(),
            'batches_with_certs' => Certificate::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->distinct('batch_id')->count(),
        ];

        return Inertia::render('School/Dashboard/Certificates/Index', [
            'certificates' => $certs->getCollection()->map(fn ($c) => [
                'id'               => $c->id,
                'certificate_code' => $c->certificate_code,
                'student_name'     => $c->student?->user?->full_name ?? '—',
                'student_email'    => $c->student?->user?->email ?? '—',
                'batch_name'       => $c->batch?->batch_name ?? '—',
                'final_grade'      => $c->final_grade,
                'grade_letter'     => $c->grade_letter,
                'batch_rank'       => $c->batch_rank,
                'total_students'   => $c->total_students,
                'attendance_rate'  => $c->attendance_rate,
                'issued_at'        => $c->issued_at?->format('M j, Y'),
                'verification_url' => $c->verification_url,
            ]),
            'filters'    => compact('search', 'batchId'),
            'batches'    => $batches,
            'stats'      => $stats,
            'pagination' => [
                'current_page' => $certs->currentPage(),
                'last_page'    => $certs->lastPage(),
                'total'        => $certs->total(),
            ],
        ]);
    }

    // ── Generate certificates for all completed students in a batch ────────────

    public function generateForBatch(Request $request, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');

        $batch  = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($batchId);

        $tenant = Tenant::findOrFail($tenantId);

        // Get all active/completed enrollments for this batch
        $enrollments = Enrollment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('batch_id', $batch->id)
            ->whereIn('status', ['active', 'completed'])
            ->with('student')
            ->get();

        $generated = 0;
        $skipped   = 0;

        foreach ($enrollments as $enrollment) {
            // Skip if certificate already exists
            $exists = Certificate::withoutGlobalScopes()
                ->where('student_id', $enrollment->student_id)
                ->where('batch_id', $batch->id)
                ->exists();

            if ($exists) { $skipped++; continue; }

            $code = Certificate::generateCode($batch, $tenant);

            Certificate::create([
                'tenant_id'        => $tenantId,
                'student_id'       => $enrollment->student_id,
                'batch_id'         => $batch->id,
                'certificate_code' => $code,
                'final_grade'      => $enrollment->final_grade,
                'grade_letter'     => $enrollment->grade_letter,
                'batch_rank'       => null, // computed separately
                'total_students'   => $enrollments->count(),
                'issued_at'        => now()->toDateString(),
                'issued_by'        => $tenant->name,
            ]);

            $generated++;
        }

        $message = $generated > 0
            ? "{$generated} certificate(s) generated"
            : "No new certificates — {$skipped} already exist";

        return redirect()->back()->with('success', $message);
    }

    // ── Revoke ─────────────────────────────────────────────────────────────────

    public function revoke(Request $request, $certificateId)
    {
        $tenantId = (int) session('active_tenant_id');

        $cert = Certificate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($certificateId);

        $cert->delete();

        return redirect()->back()->with('success', 'Certificate revoked');
    }

    // ── Public certificate verification (no auth) ──────────────────────────────

    public function verify(Request $request, string $tenantSlug, string $certificateCode)
    {
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->first();
        if (!$tenant) abort(404);

        $certificate = Certificate::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('certificate_code', $certificateCode)
            ->with(['student.user', 'batch'])
            ->first();

        if (!$certificate) {
            return Inertia::render('Public/Certificate/Verify', [
                'valid'  => false,
                'code'   => $certificateCode,
                'tenant' => ['name' => $tenant->name, 'logo' => $tenant->logo ? asset('storage/' . $tenant->logo) : null],
            ]);
        }

        return Inertia::render('Public/Certificate/Verify', [
            'valid'   => true,
            'code'    => $certificateCode,
            'tenant'  => ['name' => $tenant->name, 'logo' => $tenant->logo ? asset('storage/' . $tenant->logo) : null],
            'certificate' => [
                'certificate_code' => $certificate->certificate_code,
                'student_name'     => $certificate->student?->user?->full_name,
                'batch_name'       => $certificate->batch?->batch_name,
                'final_grade'      => $certificate->final_grade,
                'grade_letter'     => $certificate->grade_letter,
                'batch_rank'       => $certificate->batch_rank,
                'total_students'   => $certificate->total_students,
                'issued_at'        => $certificate->issued_at?->format('F j, Y'),
                'issued_by'        => $certificate->issued_by,
            ],
        ]);
    }
}