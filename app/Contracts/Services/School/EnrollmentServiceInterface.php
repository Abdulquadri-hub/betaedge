<?php

namespace App\Contracts\Services\School;

use App\Models\Batch;
use App\Models\Tenant;
use App\Models\Enrollment;

interface EnrollmentServiceInterface
{
    public function registerAndBeginEnrollment(
        Batch $batch,
        Tenant $tenant,
        array $studentData,
        ?array $parentData,
        string $callbackUrl
    ): array;

    public function completeEnrollment(string $paystackReference): Enrollment;
}
