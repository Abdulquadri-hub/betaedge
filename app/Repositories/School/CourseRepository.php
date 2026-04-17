<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;
// use Illuminate\Pagination\Paginator;

class CourseRepository implements CourseRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Course::query()
            ->where('tenant_id', session('active_tenant_id'))
            ->withCount(['activeBatches']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('course_code', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['academic_level_id'])) {
            $query->where('academic_level_id', $filters['academic_level_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all active courses for the tenant
     */
    public function getActive()
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->where('status', 'active')
            ->orderBy('title', 'asc')
            ->get();
    }

    public function getById(int|string $id): ?Course
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->find((int) $id);
    }

    public function count(): int
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->count();
    }

    public function countPublished(): int
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->where('is_published', true)
            ->count();
    }

    public function create(array $data): Course
    {
        $data['tenant_id'] = session('active_tenant_id');

        return Course::create($data);
    }

    public function update(int|string $id, array $data): Course
    {
        $course = $this->getById($id);

        if (!$course) {
            throw new \Exception("Course not found");
        }

        $course->update($data);

        return $course;
    }

    public function delete(int $id): bool
    {
        $course = $this->getById($id);

        if (!$course) {
            return false;
        }

        return (bool) $course->delete();
    }

    public function getPublished()
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->where('is_published', true)
            ->orderBy('title', 'asc')
            ->get();
    }

    public function getFeatured(int $limit = 6)
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->where('is_published', true)
            ->where('status', 'active')
            ->limit($limit)
            ->get();
    }

    public function getByAcademicLevel(int $levelId)
    {
        return Course::where('tenant_id', session('active_tenant_id'))
            ->where('academic_level_id', $levelId)
            ->where('status', 'active')
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Get course statistics for the tenant
     */
    public function getStats(): array
    {
        $tenantId = session('active_tenant_id');

        return [
            'total' => Course::where('tenant_id', $tenantId)->count(),
            'active' => Course::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'draft' => Course::where('tenant_id', $tenantId)->where('status', 'draft')->count(),
            'published' => Course::where('tenant_id', $tenantId)->where('is_published', true)->count(),
            'archived' => Course::where('tenant_id', $tenantId)->where('status', 'archived')->count(),
        ];
    }
}
