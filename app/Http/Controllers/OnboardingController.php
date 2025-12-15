<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Enums\Status;
use App\GateWays\Paystack;
use Illuminate\Http\Request;
use App\Models\OnboardingProcess;
use Illuminate\Support\Facades\RateLimiter;
use App\Contracts\Services\OnboardingProcessServiceInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingProcessServiceInterface $service,
        private SubscriptionPlanRepositoryInterface $subRepo,
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

        RateLimiter::hit($key, 600); // 10 minutes

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

    private function canAccessJob(string $jobId, string $sessionId): bool
    {
        $onboarding = OnboardingProcess::where('job_id', $jobId)
            ->where('session_id', $sessionId)
            ->first();

        return $onboarding !== null;
    }
}
