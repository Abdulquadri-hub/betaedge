<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Contracts\Services\TenantServiceInterface;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\Log;

class TenantService implements TenantServiceInterface
{
    /**
     * Create a new class instance.
     */

    public function setup(array $onboardingData): ?array
    {
        //get input from onbaording process table

        try {
           return DB::transaction(function () use ($onboardingData) {
                return [];
            });

            //dispatch job here 

        } catch (\Throwable $th) {
            Log::error("Error creating tenant {$th->getMessage()}");
            throw new Exception("Error creating tenant {$th->getMessage()}");
        }
    }

    public function retrySetup(int $id, array $onboardingData): ?array
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
    
}
