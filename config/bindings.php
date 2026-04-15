<?php

use App\Contracts\Repositories\MarketPlaceListingRepositoryInterface;
use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Repositories\Auth\AuthenticationRepositoryInterface;
use App\Contracts\Services\School\DashboardServiceInterface;
use App\Contracts\Services\Auth\AuthenticationServiceInterface;
use App\Services\TenantService;
use App\Services\TenantPageService;
use App\Services\TenantUserService;
use App\Repositories\TenantRepository;
use App\Services\TenantPaymentService;
use App\Services\SubscriptionPlanService;
use App\Repositories\TenantPageRepository;
use App\Repositories\TenantUserRepository;
use App\Services\OnboardingProcessService;
use App\Services\TenantSubscriptionSevice;
use App\Services\TenantSubscriptionService;
use App\Repositories\TenantPaymentRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Repositories\OnboardingProcessRepository;
use App\Contracts\Services\TenantServiceInterface;
use App\Repositories\TenantSubscriptionRepository;
use App\Contracts\Services\TenantPageServiceInterface;
use App\Contracts\Services\TenantUserServiceInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\TenantPaymentServiceInterface;
use App\Contracts\Services\SubscriptionPlanServiceInterface;
use App\Contracts\Repositories\TenantPageRepositoryInterface;
use App\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Contracts\Services\OnboardingProcessServiceInterface;
use App\Contracts\Services\TenantSubscriptionServiceInterface;
use App\Contracts\Repositories\TenantPaymentRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\OnboardingProcessRepositoryInterface;
use App\Contracts\Repositories\TenantSubscriptionRepositoryInterface;
use App\Contracts\Services\MarketPlaceListingServiceInterface;
use App\Repositories\MarketPlaceListingRepository;
use App\Services\MarketPlaceListingService;
use App\Repositories\School\BatchRepository;
use App\Repositories\School\CourseRepository;
use App\Services\School\DashboardService;
use App\Repositories\Auth\AuthenticationRepository;
use App\Services\Auth\AuthenticationService;

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
        
        // Authentication Module
        AuthenticationRepositoryInterface::class => AuthenticationRepository::class,
        
        // School Dashboard Module
        BatchRepositoryInterface::class => BatchRepository::class,
        CourseRepositoryInterface::class => CourseRepository::class,
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
        
        // Authentication Module
        AuthenticationServiceInterface::class => AuthenticationService::class,
        
        // School Dashboard Module
        DashboardServiceInterface::class => DashboardService::class,
    ]
];
