<?php

namespace App\Contracts\Repositories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionPlanRepositoryInterface
{
    public function getById(int $id): SubscriptionPlan;
    public function getInOrdered(): Collection;
}
