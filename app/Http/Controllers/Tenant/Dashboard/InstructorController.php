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
use App\Models\TenantInvitation;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ]);

        if ($search) {
            $query->whereHas(
                'user',
                fn($q) =>
                $q->Where('email',      'like', "%{$search}%")
            );
        }

        $instructors = $query->get()->map(fn($i) => $this->formatInstructor($i, $tenantId));

        $pendingInvitesQuery = TenantInvitation::where('tenant_id', $tenantId)
            ->where('status', 'pending');

        if ($search) {
            $pendingInvitesQuery->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $pendingInvites = $pendingInvitesQuery
            ->with('inviter')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($invite) => [
                'id'         => $invite->id,
                'email'      => $invite->email,
                'name'       => $invite->full_name ?? '—',
                'status'     => $invite->status,
                'expires_at' => $invite->expires_at?->toDateTimeString(),
                'invited_by' => $invite->inviter?->full_name ?? $invite->inviter?->email,
                'type'       => 'invite',
            ]);

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
            'instructors'     => $instructors,
            'pendingInvites'  => $pendingInvites,
            'batches'         => $batches,
            'filters'         => compact('search'),
            'stats'           => array_merge($stats, ['pending_invites' => $pendingInvites->count()]),
        ]);
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');

        // Batches for assignment
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

        return Inertia::render('School/Dashboard/Instructors/Create', [
            'batches' => $batches,
        ]);
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request)
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
                    'tenant_id'      => $tenantId,
                    'user_id'        => $user->id,
                    'instructor_id'  => $instructorCode,
                    'status'         => 'active',
                    'invite_status'  => 'accepted',
                    'invited_at'     => now(),
                    'accepted_at'    => now(),
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

            // Assign instructor to courses in selected batches
            if (!empty($validated['batch_ids'])) {
                $courseIds = BatchCourse::withoutGlobalScopes()
                    ->whereIn('batch_id', $validated['batch_ids'])
                    ->where('tenant_id', $tenantId)
                    ->pluck('course_id')
                    ->unique();

                foreach ($courseIds as $courseId) {
                    $instructor->courses()->attach($courseId, [
                        'assigned_date' => now(),
                        'is_primary_instructor' => true
                    ]);
                }
            }

            return redirect('/dashboard/instructors')->with(
                'success',
                $isNewUser
                    ? "Instructor created successfully. Temporary password: {$tempPassword}"
                    : "Instructor added to your school."
            );
        });
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
        ->whereHas('course.instructors', fn($i) => $i->where('instructors.id', $instructor->id))
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

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            $invitation = TenantInvitation::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'email'     => $validated['email'],
                    'status'    => 'pending',
                ],
                [
                    'full_name'   => $validated['name'] ?? null,
                    'role'        => 'instructor',
                    'invited_by'  => Auth::id(),
                    'expires_at'  => now()->addDays(7),
                    'metadata'    => [
                        'phone'          => $validated['phone'] ?? null,
                        'batch_ids'      => $validated['batch_ids'] ?? [],
                        'payment_type'   => $validated['payment_type'],
                        'payment_amount' => $validated['payment_amount'],
                        'payment_terms'  => $validated['payment_terms'] ?? null,
                    ],
                ]
            );

            try {
                // Trigger email or notification for pending invite
                // event(new TenantInvitationSent($invitation));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Instructor invite event failed', [
                    'invite' => $invitation->id,
                    'error'  => $e->getMessage(),
                ]);
            }

            return redirect()->back()->with(
                'success',
                "Invitation sent to {$validated['email']}. The instructor can accept when ready."
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

    public function acceptInvitation(Request $request, $tenant, $invitationId)
    {
        $tenantId   = (int) session('active_tenant_id');
        $invitation = TenantInvitation::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->find($invitationId);

        if (!$invitation) {
            return redirect()->back()->withErrors(['message' => 'Invitation not found or already processed']);
        }

        $meta = $invitation->metadata ?? [];
        $user = User::firstWhere('email', $invitation->email);

        if (!$user) {
            $tempPassword = Str::random(12);
            $user = User::create([
                'email'      => $invitation->email,
                'password'   => Hash::make($tempPassword),
                'user_type'  => 'instructor',
                'full_name'  => $invitation->full_name,
                'phone'      => $meta['phone'] ?? null,
            ]);
        } else {
            $user->fill([
                'full_name' => $user->full_name ?: $invitation->full_name,
                'phone'     => $user->phone ?: ($meta['phone'] ?? null),
                'user_type' => $user->user_type ?: 'instructor',
            ])->save();
        }

        $instructor = Instructor::withoutGlobalScopes()
            ->firstOrCreate([
                'tenant_id' => $tenantId,
                'user_id'   => $user->id,
            ], [
                'instructor_id' => $this->generateInstructorCode($tenantId),
                'status'        => 'active',
                'invite_status' => 'accepted',
                'invited_at'    => $invitation->created_at,
                'accepted_at'   => now(),
            ]);

        TenantUser::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $user->id],
            ['role' => 'instructor', 'status' => 'active', 'joined_at' => now()]
        );

        if ($meta['payment_type'] ?? null) {
            InstructorPaymentAgreement::updateOrCreate(
                ['instructor_id' => $instructor->id, 'tenant_id' => $tenantId],
                [
                    'payment_type'  => $meta['payment_type'],
                    'amount'        => $meta['payment_amount'] ?? 0,
                    'payment_terms' => $meta['payment_terms'] ?? null,
                    'status'        => 'active',
                ]
            );
        }

        if (!empty($meta['batch_ids'])) {
            $courseIds = BatchCourse::withoutGlobalScopes()
                ->whereIn('batch_id', $meta['batch_ids'])
                ->where('tenant_id', $tenantId)
                ->pluck('course_id')
                ->unique();

            $attachPayload = collect($courseIds)
                ->mapWithKeys(fn ($courseId) => [
                    $courseId => [
                        'assigned_date' => now(),
                        'is_primary_instructor' => true,
                    ],
                ])
                ->all();

            $instructor->courses()->syncWithoutDetaching($attachPayload);
        }

        $invitation->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $request->user()->id(),
        ]);

        return redirect()->route('dashboard.instructors.index')
            ->with('success', 'Invitation accepted and instructor created successfully');
    }

    public function destroyInvite(Request $request, $tenant, $invitationId)
    {
        $tenantId = (int) session('active_tenant_id');

        $invitation = TenantInvitation::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->find($invitationId);

        if (!$invitation) {
            return redirect()->back()->withErrors(['message' => 'Pending invitation not found']);
        }

        $invitation->delete();

        return redirect()->back()->with('success', 'Pending invitation removed');
    }

    public function destroy(Request $request, $tenant, $instructorId)
    {
        $tenantId   = (int) session('active_tenant_id');
        $instructor = $this->findInstructor($instructorId, $tenantId);

        if ($instructor) {
            // Remove from all courses
            $instructor->courses()->detach();

            // Remove TenantUser link
            TenantUser::where('tenant_id', $tenantId)
                ->where('user_id', $instructor->user_id)
                ->delete();

            // Soft-delete or deactivate (don't hard-delete — they may teach elsewhere)
            $instructor->update(['status' => 'inactive']);

            return redirect()->back()->with('success', 'Instructor removed from school');
        }

        $invitation = TenantInvitation::where('tenant_id', $tenantId)
            ->where('id', $instructorId)
            ->first();

        if ($invitation) {
            $invitation->delete();
            return redirect()->back()->with('success', 'Pending invitation removed');
        }

        return redirect()->back()->withErrors(['message' => 'Instructor or invitation not found']);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function generateInstructorCode(int $tenantId): string
    {
        $lastCode = Instructor::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->value('instructor_id');

        $nextNumber = $lastCode
            ? (int) filter_var($lastCode, FILTER_SANITIZE_NUMBER_INT) + 1
            : 1;

        return 'INST-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

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

        // Calculate batch counts
        $activeBatchCount = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('courses', fn($q) => $q->whereHas('instructors', fn($i) => $i->where('instructors.id', $instructor->id)))
            ->count();

        $doneBatchCount = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereHas('courses', fn($q) => $q->whereHas('instructors', fn($i) => $i->where('instructors.id', $instructor->id)))
            ->count();

        $totalBatches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereHas('courses', fn($q) => $q->whereHas('instructors', fn($i) => $i->where('instructors.id', $instructor->id)))
            ->count();

        $totalExpected = 0;
        $totalPaid     = 0;

        if ($agreement) {
            $totalPaid = \App\Models\InstructorBatchPayment::withoutGlobalScopes()
                ->where('instructor_id', $instructor->id)
                ->whereHas('batch', fn($q) => $q->where('tenant_id', $tenantId))
                ->where('payment_status', 'paid')
                ->sum('amount_due');

            if ($agreement->payment_type === 'per_batch') {
                $totalExpected = $doneBatchCount * $agreement->amount;
            } elseif ($agreement->payment_type === 'per_student') {
                $students = Batch::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'completed')
                    ->whereHas('courses', fn($q) => $q->whereHas('instructors', fn($i) => $i->where('instructors.id', $instructor->id)))
                    ->withCount('activeStudents')
                    ->get()
                    ->sum('active_students_count');
                $totalExpected = $students * $agreement->amount;
            } elseif ($agreement->payment_type === 'monthly') {
                $totalExpected = $agreement->amount; // current month
            }
        }

        $hasPending = $totalExpected > $totalPaid;

        // Completion rate (ratio of completed to total batches)
        $completionRate = $totalBatches > 0
            ? round(($doneBatchCount / $totalBatches) * 100)
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
            'active_batches'  => $activeBatchCount,
            'done_batches'    => $doneBatchCount,
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
