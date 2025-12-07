<?php

use App\Services\TenantService;
use App\Repositories\TenantRepository;
use App\Contracts\Services\TenantServiceInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;

return [

    'repositories' =>  [
        TenantRepositoryInterface::class, TenantRepository::class
    ],

    'services' => [
        TenantServiceInterface::class, TenantService::class
    ]
];
