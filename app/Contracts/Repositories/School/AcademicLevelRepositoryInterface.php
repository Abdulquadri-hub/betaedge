<?php

namespace App\Contracts\Repositories\School;

use App\Models\AcademicLevel;
use Illuminate\Pagination\LengthAwarePaginator;

interface AcademicLevelRepositoryInterface
{
    public function getActive(): array;
    public function getById(int|string $id): ?AcademicLevel;
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): AcademicLevel;
    public function update(int|string $id, array $data): AcademicLevel;
    public function delete(int|string $id): bool;
    public function count(): int;
    public function isInUse(int|string $id): bool;
}
