<?php

namespace App\Http\Controllers\Tenant;

use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\School\EnrollmentServiceInterface;
use App\Contracts\Services\School\PaystackServiceInterface;
use App\Contracts\Services\School\PublicBatchServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\EnrollmentSubmitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
        protected EnrollmentServiceInterface $enrollmentService,
        protected PaystackServiceInterface $paystackService,
        protected PublicBatchServiceInterface $batchService
    ) {}

    public function showForm(Request $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->tenantRepository->getBySlug($tenant);
        if (!$tenantModel) abort(404, 'School not found');

        $data = $this->batchService->prepareEnrollmentData($tenant, $batchSlug);

        try {
            $this->paystackService->setTenant($tenantModel);
            $data['paystack_public_key'] = $this->paystackService->publicKey();
        } catch (\Throwable) {
            $data['paystack_public_key'] = null;
        }

        return Inertia::render('School/Public/Enrollment/Form', $data);
    }

    public function submit(EnrollmentSubmitRequest $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->tenantRepository->getBySlug($tenant);
        if (! $tenantModel) {
            abort(404, 'School not found');
        }

        $batch = $this->batchService->resolveOpenBatch($tenant, $batchSlug);
        $parentData = $request->input('account_type') === 'parent'
            ? $request->input('parent')
            : null;

        try {
            $domain = config('app.main_domain');
            $callbackUrl = "https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}/payment/callback";

            $result = $this->enrollmentService->registerAndBeginEnrollment(
                batch: $batch,
                tenant: $tenantModel,
                studentData: $request->input('student'),
                parentData: $parentData,
                callbackUrl: $callbackUrl,
            );

            return Inertia::location($result['authorization_url']);
        } catch (\RuntimeException $e) {
            Log::error('Enrollment submit error', [
                'tenant' => $tenantModel->id,
                'batch' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['enrollment' => $e->getMessage()])
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('Enrollment submit error', [
                'tenant' => $tenantModel->id,
                'batch' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['enrollment' => 'An unexpected error occurred. Please try again.'])
                ->withInput();
        }
    }

    public function paystackCallback(Request $request, string $tenant, string $batchSlug)
    {
        $tenantModel = $this->tenantRepository->getBySlug($tenant);
        if (!$tenantModel) abort(404, 'School not found');

        $reference = $request->get('reference') ?? $request->get('trxref');
        $domain = config('app.main_domain');

        if (!$reference) {
            return redirect("https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}")
                ->with('error', 'Payment reference missing. Contact support.');
        }

        try {
            $this->paystackService->setTenant($tenantModel);
            $enrollment = $this->enrollmentService->completeEnrollment($reference);
            $publicData = $this->batchService->prepareEnrollmentData($tenant, $batchSlug);

            return Inertia::render('School/Public/Enrollment/Success', [
                'tenant' => $publicData['tenant'],
                'batch' => $publicData['batch'],
                'enrollment' => [
                    'id' => $enrollment->id,
                    'student_name' => $enrollment->student?->user?->full_name,
                    'batch_name' => $enrollment->batch?->batch_name,
                    'start_date' => $enrollment->batch?->start_date?->format('F j, Y'),
                    'whatsapp_link' => $enrollment->batch?->whatsapp_link,
                    'login_url' => "https://{$domain}/auth/login",
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment callback failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('School/Public/Enrollment/Failed', [
                'tenant' => $this->batchService->prepareEnrollmentData($tenant, $batchSlug)['tenant'],
                'reference' => $reference,
                'message' => $e->getMessage(),
                'batch_url' => "https://{$tenantModel->slug}.{$domain}/batches/{$batchSlug}",
            ]);
        }
    }

    public function paystackWebhook(Request $request, string $tenant)
    {
        $tenantModel = $this->tenantRepository->getBySlug($tenant);
        if (!$tenantModel) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $payload = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');

        try {
            $this->paystackService->setTenant($tenantModel);

            if (!$this->paystackService->validateWebhookSignature($payload, $signature)) {
                Log::warning('Invalid Paystack webhook signature', ['tenant' => $tenant]);
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'ok']);
        }

        $event = $request->json('event');

        if ($event === 'charge.success') {
            $reference = $request->json('data.reference');

            if ($reference) {
                try {
                    $this->enrollmentService->completeEnrollment($reference);
                } catch (\Throwable $e) {
                    Log::error('Webhook enrollment completion failed', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'ok']);
    }
}
