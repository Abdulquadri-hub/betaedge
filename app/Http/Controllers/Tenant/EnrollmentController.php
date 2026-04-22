<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Tenant;
use App\Services\School\Payment\PaystackService;
use App\Services\School\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EnrollmentController extends Controller
{

    public function showForm(Request $request, string $tenant, string $batchSlug)
    {

        $tenantModel = $this->resolveTenant($tenant);
        $batch       = $this->resolveBatch($tenantModel, $batchSlug);

        if ($batch->enrollment_status !== 'open' || $batch->isFull()) {
            return redirect("https://{$tenantModel->slug}." . config('app.main_domain') . "/batches/{$batchSlug}")
                ->with('error', 'This programme is not currently accepting enrollments.');
        }

        $paystackPublicKey = null;
        try {
            $paystackPublicKey = (new PaystackService($tenantModel))->publicKey();
        } catch (\Throwable) {
            // School hasn't configured Paystack yet
        }

        return Inertia::render('School/Public/Enrollment/Form', [
            'tenant'              => $this->formatTenant($tenantModel),
            'batch'               => $this->formatBatch($batch),
            'paystack_public_key' => $paystackPublicKey,
            'meta'                => [
                'title'    => "Enroll in {$batch->batch_name} — {$tenantModel->name}",
                'canonical' => $request->url(),
            ],
        ]);
    }

    public function submit(Request $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->resolveTenant($tenant);
        $batch       = $this->resolveBatch($tenantModel, $batchSlug);

        $isMinor = $request->input('account_type') === 'parent';

        // Build validation rules
        $rules = [
            'student.name'     => 'required|string|max:255',
            'student.email'    => 'required|email|max:255',
            'student.password' => 'required|string|min:8|confirmed',
            'student.phone'    => 'required|string|max:30',
        ];


        if ($isMinor) {
            $rules['student.date_of_birth'] = 'required|date|after:' . now()->subYears(18)->toDateString();
            $rules = array_merge($rules, [
                'parent.name'  => 'required|string|max:255',
                'parent.email' => 'required|email|max:255|different:student.email',
                'parent.phone' => 'required|string|max:30',
                'parent.relationship' => 'required|in:father,mother,guardian,grandmother,grandfather,uncle,aunt,other',
            ]);
        } else {
            $rules['student.date_of_birth'] = 'required|date|before:' . now()->subYears(18)->toDateString();
        }

        $validator = Validator::make($request->all(), $rules);
       
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $domain      = config('app.main_domain');
            $callbackUrl = "https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}/payment/callback";

            $paystack = new PaystackService($tenantModel);
            $service  = new EnrollmentService($paystack);

            $result = $service->registerAndBeginEnrollment(
                batch: $batch,
                tenant: $tenantModel,
                studentData: $request->input('student'),
                parentData: $isMinor ? $request->input('parent') : null,
                callbackUrl: $callbackUrl,
            );

            // Hard redirect to Paystack — not an Inertia visit
            return Inertia::location($result['authorization_url']);
        } catch (\RuntimeException $e) {
            Log::error('Enrollment submit error', [
                'tenant' => $tenantModel->id,
                'batch'  => $batch->id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            return back()
                ->withErrors(['enrollment' => $e->getMessage()])
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('Enrollment submit error', [
                'tenant' => $tenantModel->id,
                'batch'  => $batch->id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['enrollment' => 'An unexpected error occurred. Please try again.'])
                ->withInput();
        }
    }

    public function paystackCallback(Request $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->resolveTenant($tenant);
        $reference   = $request->get('reference') ?? $request->get('trxref');
        $domain      = config('app.main_domain');

        if (!$reference) {
            return redirect("https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}")
                ->with('error', 'Payment reference missing. Contact support.');
        }

        try {
            $paystack   = new PaystackService($tenantModel);
            $service    = new EnrollmentService($paystack);
            $enrollment = $service->completeEnrollment($reference);

            return Inertia::render('School/Public/Enrollment/Success', [
                'tenant' => $this->formatTenant($tenantModel),
                'batch'  => $this->formatBatch($enrollment->batch),
                'enrollment' => [
                    'id'           => $enrollment->id,
                    'student_name' => $enrollment->student?->user?->full_name,
                    'batch_name'   => $enrollment->batch?->batch_name,
                    'start_date'   => $enrollment->batch?->start_date?->format('F j, Y'),
                    'whatsapp_link' => $enrollment->batch?->whatsapp_link,
                    'login_url'    => "https://{$domain}/auth/login",
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment callback failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);

            return Inertia::render('School/Public/Enrollment/Failed', [
                'tenant'    => $this->formatTenant($tenantModel),
                'reference' => $reference,
                'message'   => $e->getMessage(),
                'batch_url' => "https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}",
            ]);
        }
    }

    // ── Paystack webhook (called by Paystack servers) ─────────────────────────
    // Route must exclude CSRF middleware.

    public function paystackWebhook(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->first();
        if (!$tenantModel) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $payload   = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');

        try {
            $paystack = new PaystackService($tenantModel);

            if (!$paystack->validateWebhookSignature($payload, $signature)) {
                Log::warning('Invalid Paystack webhook signature', ['tenant' => $tenant]);
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        } catch (\Throwable $e) {
            // Can't validate without Paystack config — return 200 to avoid Paystack retries
            return response()->json(['message' => 'ok']);
        }

        $event = $request->json('event');

        if ($event === 'charge.success') {
            $reference = $request->json('data.reference');

            if ($reference) {
                try {
                    $paystack = new PaystackService($tenantModel);
                    $service  = new EnrollmentService($paystack);
                    $service->completeEnrollment($reference);
                } catch (\Throwable $e) {
                    Log::error('Webhook enrollment completion failed', [
                        'reference' => $reference,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'ok']);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function resolveTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->where('status', 'active')->first();
        if (!$tenant) abort(404, 'School not found');
        return $tenant;
    }

    private function resolveBatch(Tenant $tenant, string $batchSlug): Batch
    {
        $batch = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('enrollment_status', 'open')
            ->whereRaw('LOWER(REPLACE(batch_code, " ", "-")) = ?', [strtolower($batchSlug)])
            ->with(['batchCourses.course.academicLevel', 'batchCourses.instructor.user'])
            ->first();

        if (!$batch) abort(404, 'Programme not found');
        return $batch;
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'name'          => $tenant->name,
            'slug'          => $tenant->slug,
            'subdomain'     => $tenant->subdomain ?? $tenant->slug,
            'logo'          => $tenant->logo ? asset('storage/' . $tenant->logo) : null,
            'primary_color' => $tenant->primary_color,
            'description'   => $tenant->description,
            'phone'         => $tenant->phone,
        ];
    }

    private function formatBatch(Batch $batch): array
    {
        $courses   = $batch->batchCourses->map(fn($bc) => [
            'id'              => $bc->course_id,
            'title'           => $bc->course?->title ?? '—',
            'session_day'     => $bc->session_day,
            'session_time'    => $bc->session_time,
            'duration_minutes' => $bc->session_duration_minutes,
            'platform_label'  => $bc->platform_label,
            'schedule_summary' => $bc->schedule_summary,
            'instructor_name' => $bc->instructor?->user?->full_name,
        ]);

        $currentCount = $batch->activeStudents()->count();
        $spotsLeft    = max(0, $batch->max_students - $currentCount);

        return [
            'id'               => $batch->id,
            'name'             => $batch->batch_name,
            'slug'             => strtolower(str_replace([' ', '_'], '-', $batch->batch_code)),
            'description'      => $batch->description,
            'start_date'       => $batch->start_date?->format('F j, Y'),
            'end_date'         => $batch->end_date?->format('F j, Y'),
            'max_students'     => $batch->max_students,
            'current_count'    => $currentCount,
            'spots_left'       => $spotsLeft,
            'is_full'          => $spotsLeft === 0,
            'price'            => (float) ($batch->price ?? 0),
            'price_formatted'  => $batch->price !== null
                ? '₦' . number_format((float) $batch->price, 0)
                : 'Free',
            'price_note'       => $batch->price_note,
            'enrollment_status' => $batch->enrollment_status,
            'whatsapp_link'    => $batch->whatsapp_link,
            'courses'          => $courses,
            'duration_weeks'   => ($batch->start_date && $batch->end_date)
                ? (int) $batch->start_date->diffInWeeks($batch->end_date)
                : null,
        ];
    }
}
