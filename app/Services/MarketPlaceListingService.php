<?php

namespace App\Services;

use App\Contracts\Repositories\MarketPlaceListingRepositoryInterface;
use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\MarketPlaceListingServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class MarketPlaceListingService implements MarketPlaceListingServiceInterface
{
    public function __construct(
        protected MarketPlaceListingRepositoryInterface $repo,
        protected SubscriptionPlanRepositoryInterface $subRepo,
        protected TenantRepositoryInterface $tenantRepo,
    ){}

    public function getLandingPageData(): ?array
    {
        try {
            return [
                'stats' => $this->tenantRepo->getStats(),
                'featured_schools' => $this->repo->getFeturedSchools(),
                'plans' => $this->subRepo->getInOrdered()
            ];
        } catch (\Throwable $th) {
            Log::error("Error fetching landing page data", [
                'error' => $th->getMessage()
            ]);
            throw new Exception("Error fetching landing page data {$th->getMessage()}");
        }

    }
}
