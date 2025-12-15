<?php

namespace App\Services;

use Exception;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Contracts\Services\TenantServiceInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Contracts\Services\TenantSubscriptionServiceInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Contracts\Repositories\TenantPageRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Services\Email\TenantVerificationEmailService;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        protected SubscriptionPlanRepositoryInterface $subPlanRepo,
        protected OnboardingProcessRepositoryInterface $onboardingRepo,
        protected TenantRepositoryInterface $tenantRepo,
        protected TenantSubscriptionRepositoryInterface $tenantSubRepo,
        protected TenantSubscriptionServiceInterface $tenantSubService,
        protected TenantUserRepositoryInterface $tenantUserRepo,
        protected TenantPageRepositoryInterface $tenantPageRepo,
        protected TenantVerificationEmailService $tenantMailService,
    ){}

    public function setup(int $onboardingId)
    {
        try {

            $onboarding = $this->onboardingRepo->getById($onboardingId);

            if(!$onboarding) {
                Log::error("Onboarding process not found", ['id' => $onboardingId]);
                return;
            } 

            // return DB::transaction(function () use ($onboarding){
                
                //step -> 1
                $onboarding->updateProgress(10, 'Preparing your workspace...');
                sleep(1);

                $onboarding->updateProgress(20, 'Validating your information...');
                sleep(1);
                $this->validateOnboardingData($onboarding);

                //step -> 2
                $onboarding->updateProgress(30, 'Creating your school workspace...');
                sleep(1);
            
                $onboarding->updateProgress(40, 'Setting up your school profile...');
                sleep(1);
                $tenant = $this->tenantRepo->create($onboarding);

                //step -> 3
                $onboarding->updateProgress(50, 'Configuring your subscription...');
                sleep(1);
            
                $onboarding->updateProgress(60, 'Activating your plan...');
                sleep(1);
                $subscription = $this->tenantSubRepo->create($tenant, $onboarding);

                // Step  -> 4
                $onboarding->updateProgress(70, 'Processing payment...');
                sleep(1);
                $this->tenantSubService->subscribe($tenant, $subscription, $onboarding);

                 // Step -> 5
                $onboarding->updateProgress(75, 'Setting up your account...');
                $this->tenantUserRepo->create($tenant, $onboarding);

                // Step -> 6
                $onboarding->updateProgress(80, 'Creating your public pages...');
                sleep(1);
                
                $onboarding->updateProgress(85, 'Customizing your school  website...');
                sleep(1);
                $this->tenantPageRepo->generatePages($tenant, $onboarding);

                // Step -> 7
                $onboarding->updateProgress(90, 'Preparing verification email...');
                sleep(1);
                
                $onboarding->updateProgress(95, 'Sending verification email...');
                $this->tenantMailService->send($tenant);
    
                // Step -> 8
                $onboarding->updateProgress(100, 'Setup complete!');
                $onboarding->markAsCompleted($tenant->id);
                $this->tenantRepo->completeOnboarding($tenant);

            // });

        } catch (\Throwable $th) {
            Log::error("Error creating tenant {$th->getMessage()}");
            throw new Exception("Error creating tenant {$th->getMessage()}");
        }
    }

    public function retrySetup(int $tenantId, string $sessionId, array $onboardingData): ?array
    {
        throw new \Exception('Not implemented');
    }

    public function delete(Tenant $tenant): bool
    {
        throw new \Exception('Not implemented');
    }

    public function update(Tenant $tenant, array $data): bool
    {
        throw new \Exception('Not implemented');
    }

    private function validateOnboardingData($onboardingData): void {

        $profile = $onboardingData->getProfile();
        $plan = $onboardingData->getPlan();
        $payment = $onboardingData->getPayment();

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

        $subscriptionPlan = $this->subPlanRepo->getById($plan['plan_id']);
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
