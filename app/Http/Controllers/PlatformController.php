<?php

namespace App\Http\Controllers;

use Exception;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\MarketPlaceListingRepositoryInterface;

class PlatformController extends Controller
{
    public function __construct(
        protected MarketPlaceListingRepositoryInterface $repo,
        protected SubscriptionPlanRepositoryInterface $subRepo,
        protected TenantRepositoryInterface $tenantRepo,
    ){}

    public function landing() {
        try {

            $stats = $this->tenantRepo->getStats();
            $featuredSchools =  $this->repo->getFeturedSchools();
            $plans = $this->subRepo->getInOrdered();
    

            return Inertia::render('Welcome', [
                'stats' => $stats,
                'featuredSchools' => $featuredSchools,
                'plans' => $plans,
            ]);

        } catch (\Throwable $th) {
            Log::error("Error fetching landing page data", [
                'error' => $th->getMessage()
            ]);
        }
    }
}
