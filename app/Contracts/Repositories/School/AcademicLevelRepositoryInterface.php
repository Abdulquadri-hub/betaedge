<?php

namespace App\Contracts\Repositories\School;

use App\Models\AcademicLevel;
use Illuminate\Pagination\LengthAwarePaginator;

interface AcademicLevelRepositoryInterface
{
    /**
     * Get all active academic levels for tenant
     */
    public function getActive(): array;

    /**
     * Get academic level by ID
     */
    public function getById(int|string $id): ?AcademicLevel;

    /**
     * Get paginated academic levels with optional filters
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Create new academic level
     */
    public function create(array $data): AcademicLevel;

    /**
     * Update academic level
     */
    public function update(int|string $id, array $data): AcademicLevel;

    /**
     * Delete academic level
     */
    public function delete(int|string $id): bool;
    public function count(): int;
    public function isInUse(int|string $id): bool;
}
