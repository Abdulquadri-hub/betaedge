<?php

namespace App\Repositories;

use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function getById(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::find($id);
    }

    public function getInOrdered(): Collection
    {
        return SubscriptionPlan::active()->ordered()->get();
    }
}
