<?php

use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;
use App\Contracts\Repositories\MarketPlaceListingRepositoryInterface;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Contracts\Repositories\PasswordReset\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Repositories\School\LiveSessionRepositoryInterface;
use App\Contracts\Repositories\School\TenantNotificationPreferenceRepositoryInterface;
use App\Contracts\Repositories\School\KycSubmissionRepositoryInterface;
use App\Contracts\Repositories\School\MaterialRepositoryInterface;
use App\Contracts\Repositories\School\TenantPaymentConfigRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\TenantPageRepositoryInterface;
use App\Contracts\Repositories\TenantPaymentRepositoryInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use App\Contracts\Services\MarketPlaceListingServiceInterface;
use App\Contracts\Services\OnboardingProcessServiceInterface;
use App\Contracts\Services\PasswordReset\PasswordResetServiceInterface;
use App\Contracts\Services\School\DashboardServiceInterface;
use App\Contracts\Services\School\PaystackServiceInterface;
use App\Contracts\Services\School\PublicBatchServiceInterface;
use App\Contracts\Services\School\PublicPageServiceInterface;
use App\Contracts\Services\School\EnrollmentServiceInterface;
use App\Contracts\Services\SubscriptionPlanServiceInterface;
use App\Contracts\Services\TenantPageServiceInterface;
use App\Contracts\Services\TenantPaymentServiceInterface;
use App\Contracts\Services\TenantServiceInterface;
use App\Contracts\Services\TenantSubscriptionServiceInterface;
use App\Contracts\Services\TenantUserServiceInterface;
use App\Contracts\Services\Verification\NinVerificationServiceInterface;
use App\Repositories\Auth\AuthenticationRepository;
use App\Repositories\MarketPlaceListingRepository;
use App\Repositories\OnboardingProcessRepository;
use App\Repositories\PasswordReset\PasswordResetRepository;
use App\Repositories\School\AcademicLevelRepository;
use App\Repositories\School\BatchRepository;
use App\Repositories\School\CourseRepository;
use App\Repositories\School\LiveSessionRepository;
use App\Repositories\School\TenantNotificationPreferenceRepository;
use App\Repositories\School\TenantPaymentConfigRepository;
use App\Repositories\School\KycSubmissionRepository;
use App\Repositories\School\MaterialRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Repositories\TenantPageRepository;
use App\Repositories\TenantPaymentRepository;
use App\Repositories\TenantRepository;
use App\Repositories\TenantSubscriptionRepository;
use App\Repositories\TenantUserRepository;
use App\Services\Auth\AuthenticationService;
use App\Services\MarketPlaceListingService;
use App\Services\OnboardingProcessService;
use App\Services\PasswordReset\PasswordResetService;
use App\Services\School\DashboardService;
use App\Services\School\Payment\PaystackService;
use App\Services\School\PublicBatchService;
use App\Services\School\PublicPageService;
use App\Services\School\EnrollmentService;
use App\Services\SubscriptionPlanService;
use App\Services\TenantPageService;
use App\Services\TenantPaymentService;
use App\Services\TenantService;
use App\Services\TenantSubscriptionSevice;
use App\Services\TenantUserService;
use App\Services\Verification\NinVerificationService;

return [

    'repositories' =>  [
        TenantRepositoryInterface::class => TenantRepository::class,
        OnboardingProcessRepositoryInterface::class => OnboardingProcessRepository::class,
        SubscriptionPlanRepositoryInterface::class => SubscriptionPlanRepository::class,
        TenantPageRepositoryInterface::class => TenantPageRepository::class,
        TenantPaymentRepositoryInterface::class => TenantPaymentRepository::class,
        TenantSubscriptionRepositoryInterface::class => TenantSubscriptionRepository::class,
        TenantUserRepositoryInterface::class => TenantUserRepository::class,
        MarketPlaceListingRepositoryInterface::class => MarketPlaceListingRepository::class,
        AuthenticationRepositoryInterface::class => AuthenticationRepository::class,
        PasswordResetRepositoryInterface::class => PasswordResetRepository::class,
        AcademicLevelRepositoryInterface::class => AcademicLevelRepository::class,
        BatchRepositoryInterface::class => BatchRepository::class,
        CourseRepositoryInterface::class => CourseRepository::class,
        KycSubmissionRepositoryInterface::class => KycSubmissionRepository::class,
        MaterialRepositoryInterface::class => MaterialRepository::class,
        TenantNotificationPreferenceRepositoryInterface::class => TenantNotificationPreferenceRepository::class,
        TenantPaymentConfigRepositoryInterface::class => TenantPaymentConfigRepository::class,
        LiveSessionRepositoryInterface::class => LiveSessionRepository::class,
    ],

    'services' => [
        TenantServiceInterface::class => TenantService::class,
        OnboardingProcessServiceInterface::class => OnboardingProcessService::class,
        SubscriptionPlanServiceInterface::class => SubscriptionPlanService::class,
        TenantPageServiceInterface::class => TenantPageService::class,
        TenantPaymentServiceInterface::class => TenantPaymentService::class,
        TenantSubscriptionServiceInterface::class => TenantSubscriptionSevice::class,
        TenantUserServiceInterface::class => TenantUserService::class,
        MarketPlaceListingServiceInterface::class => MarketPlaceListingService::class,
        AuthenticationServiceInterface::class => AuthenticationService::class,
        PasswordResetServiceInterface::class => PasswordResetService::class,
        DashboardServiceInterface::class => DashboardService::class,
        NinVerificationServiceInterface::class => NinVerificationService::class,
        PaystackServiceInterface::class => PaystackService::class,
        PublicBatchServiceInterface::class => PublicBatchService::class,
        PublicPageServiceInterface::class => PublicPageService::class,
        EnrollmentServiceInterface::class => EnrollmentService::class,
    ]
];
