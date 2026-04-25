<?php

namespace App\Contracts\Repositories\School;

use App\Models\Material;

interface MaterialRepositoryInterface
{
    public function countForCourse(int $tenantId, int $courseId): int;
    public function createForCourse(array $data): Material;
    public function findForCourse(int $tenantId, int $courseId, int $materialId): ?Material;
    public function delete(Material $material): void;
}
