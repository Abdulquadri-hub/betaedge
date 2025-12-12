<?php

namespace App\Contracts\Repositories;

use App\Models\SubscriptionPlan;

interface SubscriptionPlanRepositoryInterface
{
    public function getById(int $id): SubscriptionPlan;
    public function getInOrdered(): SubscriptionPlan;
}
