<?php

namespace App\Enums\Payments;

Enum Statuses:string
{
    case PENDING = "pending";
    case FAILED = "failed";
    case REFUNDED = "refunded";
    case COMPLETED = "completed";
    case EXPIRED = "expired";
    case SUSPENDED = "suspended";
}
