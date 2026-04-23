<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
// use App\Mail\InstructorInviteMail;
use App\Models\Batch;
use App\Models\BatchCourse;
use App\Models\Instructor;
use App\Models\InstructorBatchPayment;
use App\Models\InstructorPaymentAgreement;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InstructorController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $search   = $request->get('search', '');

        $query = Instructor::withoutGlobalScopes()
            ->where('instructors.tenant_id', $tenantId)
            ->with([
                'user',
                'paymentAgreements' => fn($q) =>
                $q->where('tenant_id', $tenantId)
            ])
            ->withCount([
                'batchCourses as active_batch_count' => fn($q) =>
                $q->whereHas('batch', fn($b) => $b->where('status', 'active')),
                'batchCourses as done_batch_count' => fn($q) =>
                $q->whereHas('batch', fn($b) => $b->where('status', 'completed')),
            ]);

        if ($search) {
            $query->whereHas(
                'user',
                fn($q) =>
                $q->Where('email',      'like', "%{$search}%")
            );
        }

        $instructors = $query->get()->map(fn($i) => $this->formatInstructor($i, $tenantId));

        // Batches for the "assign to batch" select in invite dialog
        $batches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['planning', 'active'])
            ->with('batchCourses.course')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn($b) => [
                'id'       => $b->id,
                'name'     => $b->batch_name,
                'subjects' => $b->batchCourses->map(fn($bc) => $bc->course?->title)->filter()->join(', '),
                'count'    => $b->activeStudents()->count(),
                'max'      => $b->max_students,
            ]);

        $stats = [
            'total'  => Instructor::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'active' => Instructor::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'active')->count(),
        ];

        return Inertia::render('School/Dashboard/Instructors/Index', [
            'instructors' => $instructors,
            'batches'     => $batches,
            'filters'     => compact('search'),
            'stats'       => $stats,
        ]);
    }

    // ── Single ─────────────────────────────────────────────────────────────────

public function single(Request $request, $tenant, $instructorId)
{
    $tenantId   = (int) session('active_tenant_id');
    $instructor = $this->findInstructor($instructorId, $tenantId);

    if (!$instructor) {
        return redirect('/dashboard/instructors')->with('error', 'Instructor not found');
    }

    // Fetch all paid batch IDs for this instructor in this tenant
    $paidBatchIds = InstructorBatchPayment::where('instructor_id', $instructor->id)
        ->where('payment_status', 'paid')
        ->pluck('batch_id')
        ->flip()
        ->all();

    $batches = BatchCourse::withoutGlobalScopes()
        ->where('instructor_id', $instructor->id)
        ->whereHas('batch', fn ($q) => $q->where('tenant_id', $tenantId))
        ->with(['batch', 'course'])
        ->get()
        ->map(fn ($bc) => [
            'batch_id'       => $bc->batch_id,
            'batch_name'     => $bc->batch?->batch_name,
            'batch_status'   => $bc->batch?->status,
            'course_title'   => $bc->course?->title,
            'schedule'       => $bc->schedule_summary,
            'student_count'  => $bc->batch?->activeStudents()->count() ?? 0,
            'payment_status' => isset($paidBatchIds[$bc->batch_id]) ? 'paid' : 'pending',
        ]);

    return Inertia::render('School/Dashboard/Instructors/Detail', [
        'instructor' => $this->formatInstructor($instructor, $tenantId),
        'batches'    => $batches,
    ]);
}

    // ── Invite ─────────────────────────────────────────────────────────────────

    public function invite(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');

        $validated = $request->validate([
            'email'                   => 'required|email|max:255',
            'name'                    => 'nullable|string|max:255',
            'phone'                   => 'nullable|string|max:30',
            'batch_ids'               => 'nullable|array',
            'batch_ids.*'             => 'exists:batches,id',
            // Payment agreement
            'payment_type'            => 'required|in:per_batch,per_student,monthly,custom',
            'payment_amount'          => 'required|numeric|min:0',
            'payment_terms'           => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated, $tenantId) {
            $tenant = Tenant::findOrFail($tenantId);

            // Find or create user account for the instructor
            $user = User::where('email', $validated['email'])->first();
            $isNewUser = !$user;

            if (!$user) {
                $tempPassword = Str::random(12);
                $nameParts    = explode(' ', trim($validated['name'] ?? ''), 2);

                $user = User::create([
                    'email'      => $validated['email'],
                    'phone'      => $validated['phone']   ?? null,
                    'password'   => Hash::make($tempPassword),
                    'user_type'  => 'instructor',
                ]);
            }

            // Create or retrieve instructor record
            // Create or retrieve instructor record
            $instructor = Instructor::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$instructor) {
                // Auto-generate a unique instructor code for this tenant
                $lastCode = Instructor::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->orderByDesc('id')
                    ->value('instructor_id');

                $nextNumber = $lastCode
                    ? (int) filter_var($lastCode, FILTER_SANITIZE_NUMBER_INT) + 1
                    : 1;

                $instructorCode = 'INST-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $instructor = Instructor::create([
                    'tenant_id'     => $tenantId,
                    'user_id'       => $user->id,
                    'instructor_id' => $instructorCode,   
                    'status'        => 'active',
                ]);
            }

            // Link to tenant (multi-school instructors)
            TenantUser::firstOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $user->id],
                ['role' => 'instructor', 'status' => 'active', 'joined_at' => now()]
            );

            // Payment agreement
            InstructorPaymentAgreement::updateOrCreate(
                ['instructor_id' => $instructor->id, 'tenant_id' => $tenantId],
                [
                    'payment_type'  => $validated['payment_type'],
                    'amount'        => $validated['payment_amount'],
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'status'        => 'active',
                ]
            );

            // Assign instructor to selected batches (via batch_courses)
            if (!empty($validated['batch_ids'])) {
                foreach ($validated['batch_ids'] as $batchId) {
                    BatchCourse::withoutGlobalScopes()
                        ->where('batch_id', $batchId)
                        ->where('tenant_id', $tenantId)
                        ->whereNull('instructor_id')
                        ->update(['instructor_id' => $instructor->id]);
                }
            }

            // Send invitation email
            try {
                // Mail::to($validated['email'])->send(
                //     InstructorInviteMail($tenant, $user, $isNewUser, $tempPassword ?? null)
                // );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Instructor invite email failed', [
                    'instructor' => $instructor->id,
                    'error'      => $e->getMessage(),
                ]);
                // Don't fail the invite — just log it
            }

            return redirect()->back()->with(
                'success',
                $isNewUser
                    ? "Invitation sent to {$validated['email']}. They'll receive login credentials."
                    : "{$user->full_name} added to your school."
            );
        });
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, $tenant, $instructorId)
    {
        $tenantId   = (int) session('active_tenant_id');
        $instructor = $this->findInstructor($instructorId, $tenantId);

        if (!$instructor) {
            return redirect()->back()->withErrors(['message' => 'Instructor not found']);
        }

        $validated = $request->validate([
            'payment_type'   => 'required|in:per_batch,per_student,monthly,custom',
            'payment_amount' => 'required|numeric|min:0',
            'payment_terms'  => 'nullable|string|max:1000',
            'status'         => 'nullable|in:active,inactive',
        ]);

        // Update payment agreement
        InstructorPaymentAgreement::updateOrCreate(
            ['instructor_id' => $instructor->id, 'tenant_id' => $tenantId],
            [
                'payment_type'  => $validated['payment_type'],
                'amount'        => $validated['payment_amount'],
                'payment_terms' => $validated['payment_terms'] ?? null,
            ]
        );

        if (isset($validated['status'])) {
            $instructor->update(['status' => $validated['status']]);
        }

        return redirect()->back()->with('success', 'Instructor updated');
    }

    // ── Mark paid ──────────────────────────────────────────────────────────────

    public function markPaid(Request $request, $tenant,  $instructorId)
    {
        $tenantId   = (int) session('active_tenant_id');
        $instructor = $this->findInstructor($instructorId, $tenantId);

        if (!$instructor) {
            return redirect()->back()->withErrors(['message' => 'Instructor not found']);
        }

        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'amount'   => 'required|numeric|min:0',
            'note'     => 'nullable|string|max:255',
        ]);

        InstructorBatchPayment::updateOrCreate(
            [
                'instructor_id' => $instructor->id,
                'batch_id'      => $request->batch_id,
            ],
            [
                'amount_due'      => $request->amount,
                'payment_status'  => 'paid',
                'marked_paid_at'  => now(),
                'note'            => $request->note,
            ]
        );

        return redirect()->back()->with('success', 'Payment marked as paid');
    }

    public function destroy(Request $request, $tenant, $instructorId)
    {
        $tenantId   = (int) session('active_tenant_id');
        $instructor = $this->findInstructor($instructorId, $tenantId);

        if (!$instructor) {
            return redirect()->back()->withErrors(['message' => 'Instructor not found']);
        }

        // Remove from all batch courses in this tenant
        BatchCourse::withoutGlobalScopes()
            ->where('instructor_id', $instructor->id)
            ->whereHas('batch', fn($q) => $q->where('tenant_id', $tenantId))
            ->update(['instructor_id' => null]);

        // Remove TenantUser link
        TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $instructor->user_id)
            ->delete();

        // Soft-delete or deactivate (don't hard-delete — they may teach elsewhere)
        $instructor->update(['status' => 'inactive']);

        return redirect()->back()->with('success', 'Instructor removed from school');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function findInstructor(int|string $id, int $tenantId): ?Instructor
    {
        return Instructor::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['user', 'paymentAgreements' => fn($q) => $q->where('tenant_id', $tenantId)])
            ->find((int) $id);
    }

    private function formatInstructor(Instructor $instructor, int $tenantId): array
    {
        $agreement = $instructor->paymentAgreements->first();

        // Calculate earnings for this instructor at this school
        $completedBatchCount = BatchCourse::withoutGlobalScopes()
            ->where('instructor_id', $instructor->id)
            ->whereHas(
                'batch',
                fn($q) =>
                $q->where('tenant_id', $tenantId)->where('status', 'completed')
            )->count();

        $totalExpected = 0;
        $totalPaid     = 0;

        if ($agreement) {
            $totalPaid = \App\Models\InstructorBatchPayment::withoutGlobalScopes()
                ->where('instructor_id', $instructor->id)
                ->whereHas('batch', fn($q) => $q->where('tenant_id', $tenantId))
                ->where('payment_status', 'paid')
                ->sum('amount_due');

            if ($agreement->payment_type === 'per_batch') {
                $totalExpected = $completedBatchCount * $agreement->amount;
            } elseif ($agreement->payment_type === 'per_student') {
                $students = BatchCourse::withoutGlobalScopes()
                    ->where('instructor_id', $instructor->id)
                    ->whereHas(
                        'batch',
                        fn($q) =>
                        $q->where('tenant_id', $tenantId)->where('status', 'completed')
                    )->with('batch')
                    ->get()
                    ->sum(fn($bc) => $bc->batch?->activeStudents()->count() ?? 0);
                $totalExpected = $students * $agreement->amount;
            } elseif ($agreement->payment_type === 'monthly') {
                $totalExpected = $agreement->amount; // current month
            }
        }

        $hasPending = $totalExpected > $totalPaid;

        // Completion rate (ratio of completed to total batches)
        $totalBatches = BatchCourse::withoutGlobalScopes()
            ->where('instructor_id', $instructor->id)
            ->whereHas('batch', fn($q) => $q->where('tenant_id', $tenantId))
            ->count();

        $completionRate = $totalBatches > 0
            ? round(($completedBatchCount / $totalBatches) * 100)
            : null;

        // Also teaches at other schools
        $otherSchools = TenantUser::where('user_id', $instructor->user_id)
            ->where('tenant_id', '!=', $tenantId)
            ->where('role', 'instructor')
            ->count();

        return [
            'id'              => $instructor->id,
            'name'            => $instructor->user?->full_name ?? '—',
            'email'           => $instructor->user?->email ?? '—',
            'phone'           => $instructor->user?->phone,
            'avatar'          => $instructor->avatar ? asset('storage/' . $instructor->avatar) : null,
            'qualification'   => $instructor->qualification,
            'bio'             => $instructor->bio,
            'specialties'     => $instructor->specialties ?? [],
            'rating'          => $instructor->rating,
            'status'          => $instructor->status,
            'active_batches'  => $instructor->active_batch_count ?? 0,
            'done_batches'    => $instructor->done_batch_count ?? 0,
            'completion_rate' => $completionRate,
            'other_schools'   => $otherSchools,
            'payment_agreement' => $agreement ? [
                'type'        => $agreement->payment_type,
                'amount'      => (float) $agreement->amount,
                'terms'       => $agreement->payment_terms,
            ] : null,
            'earnings' => [
                'total_expected' => $totalExpected,
                'total_paid'     => $totalPaid,
                'has_pending'    => $hasPending,
                'balance_due'    => max(0, $totalExpected - $totalPaid),
            ],
        ];
    }
}
