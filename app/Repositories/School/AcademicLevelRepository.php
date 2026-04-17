<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Models\AcademicLevel;
use Illuminate\Pagination\LengthAwarePaginator;

class AcademicLevelRepository implements AcademicLevelRepositoryInterface
{
    /**
     * Get all active academic levels for tenant
     */
    public function getActive(): array
    {
        return AcademicLevel::where('tenant_id', session('active_tenant_id'))
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get academic level by ID
     */
    public function getById(int|string $id): ?AcademicLevel
    {
        return AcademicLevel::where('tenant_id', session('active_tenant_id'))
            ->find((int) $id);
    }

    /**
     * Get paginated academic levels with optional filters
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = AcademicLevel::query()
            ->where('tenant_id', session('active_tenant_id'));

        // Search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        // Status filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('display_order', 'asc')->paginate($perPage);
    }

    /**
     * Create new academic level
     */
    public function create(array $data): AcademicLevel
    {
        $data['tenant_id'] = session('active_tenant_id');
        
        // If display_order not provided, set to next highest
        if (!isset($data['display_order'])) {
            $maxOrder = AcademicLevel::where('tenant_id', $data['tenant_id'])
                ->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 1;
        }

        return AcademicLevel::create($data);
    }

    /**
     * Update academic level
     */
    public function update(int|string $id, array $data): AcademicLevel
    {
        $level = $this->getById($id);

        if (!$level) {
            throw new \Exception("Academic level not found");
        }

        $level->update($data);

        return $level;
    }

    /**
     * Delete academic level
     */
    public function delete(int|string $id): bool
    {
        $level = $this->getById($id);

        if (!$level) {
            throw new \Exception("Academic level not found");
        }

        // Soft delete via model or hard delete
        return $level->delete();
    }

    /**
     * Get total count for tenant
     */
    public function count(): int
    {
        return AcademicLevel::where('tenant_id', session('active_tenant_id'))->count();
    }

    /**
     * Check if academic level is in use (has courses)
     */
    public function isInUse(int|string $id): bool
    {
        $level = $this->getById($id);

        if (!$level) {
            return false;
        }

        return $level->courses()->where('status', '!=', 'archived')->exists();
    }
}
