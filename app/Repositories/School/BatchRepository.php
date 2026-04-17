<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Models\Batch;
use Illuminate\Pagination\LengthAwarePaginator;
//use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class BatchRepository implements BatchRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Batch::where('tenant_id', session('active_tenant_id'));

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('batch_name', 'like', "%{$filters['search']}%")
                    ->orWhere('batch_code', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    /**
     * Get all active batches for the tenant
     */
    public function getActive()
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get batch by ID
     */
    public function getById(int $id): ?Batch
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->find($id);
    }

    /**
     * Get batch count for the tenant
     */
    public function count(): int
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->count();
    }

    /**
     * Get active batch count for the tenant
     */
    public function countActive(): int
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->where('status', 'active')
            ->count();
    }

    /**
     * Create a new batch
     */
    public function create(array $data): Batch
    {
        $data['tenant_id'] = session('active_tenant_id');

        return Batch::create($data);
    }

    /**
     * Update a batch
     */
    public function update(int $id, array $data): Batch
    {
        $batch = $this->getById($id);

        if (!$batch) {
            throw new \Exception("Batch not found");
        }

        $batch->update($data);

        return $batch;
    }

    /**
     * Delete a batch
     */
    public function delete(int $id): bool
    {
        $batch = $this->getById($id);

        if (!$batch) {
            return false;
        }

        return (bool) $batch->delete();
    }

    /**
     * Get batches by course
     */
    public function getByCourse(int $courseId)
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->where('course_id', $courseId)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get published batches
     */
    public function getPublished()
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->where('is_published', true)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get upcoming batches
     */
    public function getUpcoming(int $limit = 5)
    {
        return Batch::where('tenant_id', session('active_tenant_id'))
            ->where('start_date', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get batch statistics for the tenant
     */
    public function getStats(): array
    {
        $tenantId = session('active_tenant_id');

        return [
            'total' => Batch::where('tenant_id', $tenantId)->count(),
            'active' => Batch::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'upcoming' => Batch::where('tenant_id', $tenantId)
                ->where('start_date', '>', now())
                ->where('status', '!=', 'cancelled')
                ->count(),
            'completed' => Batch::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'total_students' => DB::table('batch_student')
                ->whereIn('batch_id', function ($query) use ($tenantId) {
                    $query->select('id')
                        ->from('batches')
                        ->where('tenant_id', $tenantId);
                })
                ->where('status', 'active')
                ->count(),
        ];
    }
}
