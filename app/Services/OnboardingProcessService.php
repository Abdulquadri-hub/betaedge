<?php

namespace App\Services;

use Exception;
use RuntimeException;
use Illuminate\Support\Str;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessTenantOnboardingJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Contracts\Services\OnboardingProcessServiceInterface;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;

class OnboardingProcessService implements OnboardingProcessServiceInterface
{
    public function __construct(
        protected OnboardingProcessRepositoryInterface $repo,
    ){}

    public function save(int $sessionId, array $stepData, string $step): ?array
    {
        try{

            $this->validateStepData($step, $stepData);

            $onboardingData = [
                'current_step' => $step,
                $step => $stepData
            ];

            $draft =  DB::transaction(function () use ($onboardingData, $sessionId) {
                return $this->repo->update($sessionId, $onboardingData);
            });

            if(!$draft) {
                Log::error("Error saving onboarding process", [
                    'data' => $draft
                ]);
                throw new Exception("Error saving onboarding process");
            }

            return [
                'draft' => [
                    'profile' => $draft->getProfile(),
                    'plan' => $draft->getPlan(),
                    'payment' => $draft->getPayment(),
                    'current_step' => $draft->current_step
                ]
            ];

        } 
        catch(ValidationException $e) {
            Log::error("Validation error", [
                'errors' => $e->errors(),
                'message' => 'validation failed'
            ]);
            throw new Exception("validation error: " . json_encode($e->errors(), JSON_PRETTY_PRINT));
        } 
        catch (\Throwable $th) {
            Log::error("Error drafting onboarding process:", [
                'session_id' => $sessionId,
                'step' => $step,
                'error' => $th->getMessage()
            ]);
            throw new Exception("Error drating onboarding process: {$th->getMessage()}");
        }
    }

    public function fetch(int $sessionId): ?array
    {
        try {
            $draft = $this->repo->getBySessionId($sessionId);
    
            if (!$draft) {
                return null;
            }
    
            return [
                'profile' => $draft->getProfile(),
                'plan' => $draft->getPlan(),
                'payment' => $draft->getPayment(),
                'current_step' => $draft->current_step,
                'progress_percentage' => $draft->progress_percentage
            ];

        } catch (\Throwable $th) {
            Log::error("Error fetching drafted onboarding process:", [
                'session_id' => $sessionId,
                'error' => $th->getMessage()
            ]);
            throw new Exception("Error fetching drafted onboarding process: {$th->getMessage()}");
        }

    }

    public function submit(int $sessionId): ?array
    {
         try {
            $draft = $this->repo->getBySessionId($sessionId);

            if (!$draft) {
                throw new RuntimeException("Draft not found");
            }

            $this->finalValidation($draft);
  
            if ($draft->status === 'processing' && $draft->job_id) {
                return [
                    'job_id' => $draft->job_id,
                    'message' => 'Onboarding already in progress'
                ];
            }

            $jobId = $this->generateJobId();

            $draft->markAsProcessing($jobId);

            ProcessTenantOnboardingJob::dispatch($draft->id, $jobId)->onQueue('onboarding');

            return [
                'job_id' => $jobId,
                'message' => 'Onboarding process started'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to submit onboarding', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Error starting onboarding: {$e->getMessage()}");
        }
    }

    public function getProgress(string $jobId): ?array
    {
        try {
            $draft = $this->repo->getByJobId($jobId);

            if (!$draft) {
                throw new Exception("job not found");
            }

            return [
                'status' => $draft->status,
                'progress' => $draft->progress_percentage,
                'message' => $draft->progress_message,
                'error' => $draft->error_message,
                'session_id' => $draft->sesssion_id,
                'tenant_id' => $draft->tenant_id,
                'completed_at' => $draft->completed_at?->toIso8601String()
            ];
        } catch (\Throwable $th) {
            log::error("Failed to fetch progress", [
                'tenant_id' => $draft->tenant_id ?? null,
                'session_id' => $draft->session_id ?? null,
                'message' => $th->getMessage()
            ]);
            return [];
        }
    }

    public function getOrCreateSessionId($request): string
    {
        $sessionKey = 'onboarding_session_id';
        
        if ($request->session()->has($sessionKey)) {
            return $request->session()->get($sessionKey);
        }

        $sessionId = 'onb_' . Str::uuid()->toString();
        $request->session()->put($sessionKey, $sessionId);
        
        return $sessionId;
    }

    private function generateJobId(): string {
        return Str::uuid()->toString();
    }

    private function validateStepData(string $step, array $data): void {
        
        $rules = match($step) {
            'profile' => [
                'school_name' => 'required|string|max:255',
                'owner_email' => 'required|email|max:255',
                'school_type' => 'nullable|in:primary,secondary,tertiary,training',
                'country' => 'required|string|size:2',
                'city' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:1000',
                'logo' => 'nullable|url',
                'primary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
                'secondary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            ],
            'plan' => [
                'plan_id' => 'required|exists:subscription_plans,id',
                'billing_cycle' => 'required|in:monthly,yearly',
            ],
            'payment' => [
                'paystack_reference' => 'required_if:is_free,false|string',
            ],
            default => []
        };

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function finalValidation($draft): void {
        
        $profile = $draft->getProfile();
        $plan = $draft->getPlan();
        $payment = $draft->getPayment();

        throw_if(
            empty($profile['school_name']), 
            ValidationException::withMessages([
            'school_name' => 'School name is required'
        ]));

        throw_if(
            empty($profile['owner_email']) || 
            !filter_var($profile['owner_email'], FILTER_VALIDATE_EMAIL), 
            ValidationException::withMessages([
            'owner_email' => 'Valid email is required'
        ]));

        throw_if(
            empty($plan['plan_id']), 
            ValidationException::withMessages([
            'plan_id' => 'Please select a subscription plan'
        ]));

        $subscriptionPlan = SubscriptionPlan::find($plan['plan_id']);
        throw_if(
            !$subscriptionPlan || !$subscriptionPlan->is_active, 
            ValidationException::withMessages([
            'plan' => 'Selected plan is not available'
        ]));

        if (!$subscriptionPlan->isFree()) {
            throw_if(
                empty($payment['paystack_reference']),
                ValidationException::withMessages([
                    'payment' => 'Payment reference is required for paid plans'
                ])
            );
        }
    }

}
