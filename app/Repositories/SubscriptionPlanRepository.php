<?php

namespace App\Repositories;

use App\Contracts\Repositories\SubscriptionPlanRepositoryInterface;
use App\Models\SubscriptionPlan;

class SubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function getById(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::find($id);
    }

    public function getInOrdered(): SubscriptionPlan
    {
        return SubscriptionPlan::active()->ordered()->get();
    }
}
