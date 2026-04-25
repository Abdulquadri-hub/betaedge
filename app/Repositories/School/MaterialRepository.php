<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\MaterialRepositoryInterface;
use App\Models\Material;

class MaterialRepository implements MaterialRepositoryInterface
{
    public function countForCourse(int $tenantId, int $courseId): int
    {
        return Material::where('tenant_id', $tenantId)
            ->where('course_id', $courseId)
            ->count();
    }

    public function createForCourse(array $data): Material
    {
        return Material::create($data);
    }

    public function findForCourse(int $tenantId, int $courseId, int $materialId): ?Material
    {
        return Material::where('tenant_id', $tenantId)
            ->where('course_id', $courseId)
            ->find($materialId);
    }

    public function delete(Material $material): void
    {
        $material->delete();
    }
}
