<?php

namespace App\Contracts\Services\School;

use App\Models\Batch;

interface PublicBatchServiceInterface
{
    public function list(string $tenantSlug, array $filters = []): array;
    public function get(string $tenantSlug, string $batchSlug): array;
    public function resolveOpenBatch(string $tenantSlug, string $batchSlug): Batch;
    public function prepareEnrollmentData(string $tenantSlug, string $batchSlug): array;
}
