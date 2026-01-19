<?php

namespace App\Repositories;

use App\Contracts\Repositories\MarketPlaceListingRepositoryInterface;
use App\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Collection;

class MarketPlaceListingRepository implements MarketPlaceListingRepositoryInterface
{
    public function getById(int $id): MarketplaceListing
    {
        return MarketplaceListing::where('id', $id)
            ->where('is_active', true)
            ->first();
    }

    public function getFeturedSchools(): Collection
    {
        return MarketplaceListing::with('tenant:id,name,slug,subdomain')
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('rating', 'desc')
            ->orderBy('total_students', 'desc')
            ->limit(6)
            ->get();
    }

}
