<?php

namespace App\Contracts\Repositories\School;

use App\Models\ClassSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface LiveSessionRepositoryInterface
{
    public function getPaginated(int $perPage = 20, array $filters = []): LengthAwarePaginator;
    public function getById(int $id): ?ClassSession;
    public function getByBatch(int $batchId): array;
    public function getUpcoming(int $limit = 5): array;
    public function getLiveNow(): ?ClassSession;
    public function create(array $data): ClassSession;
    public function update(int $id, array $data): ClassSession;
    public function delete(int $id): bool;
    public function markLive(int $id): ClassSession;
    public function markCompleted(int $id, int $attendees): ClassSession;
    public function markCancelled(int $id): ClassSession;
    public function getStats(): array;
}