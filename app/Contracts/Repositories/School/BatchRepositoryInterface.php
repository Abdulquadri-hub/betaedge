<?php

namespace App\Contracts\Repositories\School;

use App\Models\Batch;
use Illuminate\Pagination\LengthAwarePaginator;
//use Illuminate\Pagination\Paginator;

interface BatchRepositoryInterface
{

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function getActive();
    public function getById(int $id): ?Batch;
    public function count(): int;
    public function countActive(): int;
    public function getStats(): array;
    public function create(array $data): Batch;
    public function update(int $id, array $data): Batch;
    public function delete(int $id): bool;
}
