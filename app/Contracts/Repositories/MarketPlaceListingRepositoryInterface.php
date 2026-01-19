<?php

namespace App\Contracts\Repositories;

use App\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Collection;

interface MarketPlaceListingRepositoryInterface
{
    public function getById(int $id): MarketplaceListing;
    public function getFeturedSchools(): Collection;
}
