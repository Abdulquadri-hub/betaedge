<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\GateWays\Paystack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Contracts\Services\OnboardingProcessServiceInterface;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingProcessServiceInterface $service,
        private SubscriptionPlanRepositoryInterface $subRepo,
        private OnboardingProcessRepositoryInterface $onboardingRepo,
        private TenantRepositoryInterface $tenantRepository,
        private Paystack $gateway,
    ){}


    public function index(Request $request) {

        $sessionId = $this->service->getOrCreateSessionId($request);

        $draft = $this->service->fetch($sessionId);
        $plans = $this->subRepo->getInOrdered()->toArray();

        return Inertia::render('Onboarding/Index', [
            'draft' => $draft,
            'plans' => $plans,
            'job_id' => $draft['job_id'] ?? null,
            'paystackPublicKey' => $this->gateway->getPublicKey(),
            'name' => config('app.name')
        ]);
    }

    public function save(Request $request) {

        $sessionId = $this->service->getOrCreateSessionId($request);

        $result = $this->service->save(
            $sessionId,
            $request->input('data', []),
            $request->input('step')
        );

        return back()->with([
            'draft' => $result['draft'] ?? null,
        ]);
    }

    public function submit(Request $request)
    {
        $sessionId = $this->service->getOrCreateSessionId($request);

        $key = 'onboarding-submit:' . $sessionId;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many submission attempts. Please wait before trying again.'
            ], 429);
        }

        RateLimiter::hit($key, 600);

        $result = $this->service->submit($sessionId);
    
        if (!($result['success'] ?? false)) {
            return back()->withErrors([
                'server' => $result['message'] ?? 'Submission failed',
            ]);
        }
    
        return back()->with([
            'job_id' => $result['job_id'] ?? null,
            'message' => $result['message'] ?? 'Onboarding process started',
        ]);
    }

    
    public function status(Request $request, string $jobId)
    {
        $sessionId = $this->service->getOrCreateSessionId($request);

        $key = 'onboarding-status:' . $request->ip() . ':' . $jobId;
        
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'status' => 'rate_limited',
                'message' => 'Too many requests'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        if (!$this->canAccessJob($jobId, $sessionId)) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Access denied'
            ], 403);
        }

        $progress = $this->service->getProgress($jobId);

        return response()->json($progress);
    }

    public function validateSlug(Request $request)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:7', 'regex:/^[a-z0-9-]+$/']
        ]);

        $slug = $validated['slug'];
        $errors = [];

        if ($this->tenantRepository->existsBySlug($slug)) {
            $errors[] = 'This slug is already taken. Please choose another.';
        }

        if ($this->tenantRepository->isSlugReserved($slug)) {
            $errors[] = 'This slug is reserved and cannot be used. Please choose another.';
        }

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
        ], [
            'logo.required' => 'Logo file is required',
            'logo.image' => 'Uploaded file must be an image',
            'logo.mimes' => 'Logo must be a JPG or PNG file',
            'logo.max' => 'Logo must not exceed 2MB'
        ]);

        try {
            $file = $validated['logo'];
            $filename = 'logo-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('onboarding/logos', $filename, 'public');
            $url = asset('storage/' . $path);

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function canAccessJob(string $jobId, string $sessionId): bool
    {
        $onboarding = $this->onboardingRepo->getByJobId($jobId);

        return $onboarding !== null && $onboarding->session_id === $sessionId;
    }
}
