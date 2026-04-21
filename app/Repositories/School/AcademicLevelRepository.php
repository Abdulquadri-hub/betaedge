<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Models\AcademicLevel;
use Illuminate\Pagination\LengthAwarePaginator;

class AcademicLevelRepository implements AcademicLevelRepositoryInterface
{
    private function tenantId(): int
    {
        return (int) session('active_tenant_id');
    }

    public function getActive(): array
    {
        return AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('level_number', 'asc')
            ->get()
            ->toArray();
    }

    public function getById(int|string $id): ?AcademicLevel
    {
        return AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('id', (int) $id)
            ->first();
    }

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId());

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('display_order', 'asc')->orderBy('level_number', 'asc')->paginate($perPage);
    }

    public function create(array $data): AcademicLevel
    {
        $tenantId          = $this->tenantId();
        $data['tenant_id'] = $tenantId;

        if (!isset($data['display_order'])) {
            $maxOrder              = AcademicLevel::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 1;
        }

        return AcademicLevel::create($data);
    }

    public function update(int|string $id, array $data): AcademicLevel
    {
        $level = $this->getById($id);

        if (!$level) {
            throw new \Exception("Academic level not found");
        }

        $level->update($data);
        return $level->fresh();
    }

    public function delete(int|string $id): bool
    {
        $level = $this->getById($id);

        if (!$level) {
            throw new \Exception("Academic level not found");
        }

        return (bool) $level->delete();
    }

    public function count(): int
    {
        return AcademicLevel::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->count();
    }

    public function isInUse(int|string $id): bool
    {
        $level = $this->getById($id);

        if (!$level) {
            return false;
        }

        return $level->courses()->where('status', '!=', 'archived')->exists();
    }
}