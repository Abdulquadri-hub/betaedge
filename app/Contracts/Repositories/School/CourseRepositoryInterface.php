<?php

namespace App\Contracts\Repositories\School;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

interface CourseRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function getActive();
    public function getById(int $id): ?Course;
    public function count(): int;
    public function countPublished(): int;
    public function create(array $data): Course;
    public function update(int $id, array $data): Course;
    public function delete(int $id): bool;
    public function getStats(): array;
}
