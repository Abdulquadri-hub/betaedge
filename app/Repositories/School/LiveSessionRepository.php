<?php

namespace App\Repositories\School;

use App\Contracts\Repositories\School\LiveSessionRepositoryInterface;
use App\Models\Batch;
use App\Models\ClassSession;
use Illuminate\Pagination\LengthAwarePaginator;


class LiveSessionRepository implements LiveSessionRepositoryInterface
{
    private function tenantId(): int
    {
        return (int) session('active_tenant_id');
    }

    public function getPaginated(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->with(['batch', 'course', 'instructor.user']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$s}%"))
                  ->orWhereHas('batch',  fn ($b) => $b->where('batch_name', 'like', "%{$s}%"));
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $dbStatus = $filters['status'] === 'live' ? 'in_progress' : $filters['status'];
            $query->where('status', $dbStatus);
        }

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', (int) $filters['batch_id']);
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', (int) $filters['course_id']);
        }

        return $query->orderBy('scheduled_start', 'desc')->paginate($perPage);
    }

    public function getById(int $id): ?ClassSession
    {
        return ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->with(['batch', 'course', 'instructor.user'])
            ->find($id);
    }

    public function getByBatch(int $batchId): array
    {
        return ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('batch_id', $batchId)
            ->with(['course'])
            ->orderBy('scheduled_start')
            ->get()
            ->toArray();
    }

    public function getUpcoming(int $limit = 5): array
    {
        return ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'scheduled')
            ->where('scheduled_start', '>=', now())
            ->with(['batch', 'course'])
            ->orderBy('scheduled_start')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getLiveNow(): ?ClassSession
    {
        return ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'in_progress')
            ->with(['batch', 'course'])
            ->first();
    }

    public function create(array $data): ClassSession
    {
        $data['tenant_id'] = $this->tenantId();

        if (isset($data['scheduled_date'], $data['scheduled_time'])) {
            $data['scheduled_start'] = $data['scheduled_date'] . ' ' . $data['scheduled_time'] . ':00';
            unset($data['scheduled_date'], $data['scheduled_time']);
        }

        if (!empty($data['scheduled_start']) && !empty($data['duration_minutes'])) {
            $data['scheduled_end'] = date(
                'Y-m-d H:i:s',
                strtotime($data['scheduled_start']) + ((int) $data['duration_minutes'] * 60)
            );
        }

        if (array_key_exists('meet_link', $data)) {
            $data['meeting_url'] = $data['meet_link'];
            unset($data['meet_link']);
        }

        if (!isset($data['total_enrolled']) && isset($data['batch_id'])) {
            $batch = Batch::withoutGlobalScopes()->find((int) $data['batch_id']);
            $data['total_enrolled'] = $batch ? $batch->activeStudents()->count() : 0;
        }

        // course_id must be provided explicitly — batch no longer has a single course_id
        // Caller must pass course_id. Validate upstream.
        if (empty($data['course_id'])) {
            throw new \InvalidArgumentException(
                'course_id is required when creating a class session. ' .
                'A batch has many courses — specify which subject this session is for.'
            );
        }

        // status mapping
        if (isset($data['status']) && $data['status'] === 'live') {
            $data['status'] = 'in_progress';
        }

        $data['session_type'] = $data['session_type'] ?? 'live';

        return ClassSession::create($data);
    }

    public function update(int $id, array $data): ClassSession
    {
        $session = $this->getById($id);
        if (!$session) {
            throw new \Exception("Session not found");
        }

        // Recombine date + time if either is provided
        if (isset($data['scheduled_date']) || isset($data['scheduled_time'])) {
            $date = $data['scheduled_date'] ?? $session->scheduled_start->toDateString();
            $time = $data['scheduled_time'] ?? $session->scheduled_start->format('H:i');
            $data['scheduled_start'] = $date . ' ' . $time . ':00';
            unset($data['scheduled_date'], $data['scheduled_time']);

            if (!empty($data['duration_minutes'])) {
                $data['scheduled_end'] = date(
                    'Y-m-d H:i:s',
                    strtotime($data['scheduled_start']) + ((int) $data['duration_minutes'] * 60)
                );
            }
        }

        if (array_key_exists('meet_link', $data)) {
            $data['meeting_url'] = $data['meet_link'];
            unset($data['meet_link']);
        }

        if (isset($data['status']) && $data['status'] === 'live') {
            $data['status'] = 'in_progress';
        }

        $session->update($data);
        return $session->fresh();
    }

    public function delete(int $id): bool
    {
        $session = $this->getById($id);
        if (!$session) {
            throw new \Exception("Session not found");
        }
        return (bool) $session->delete();
    }

    public function markLive(int $id): ClassSession
    {
        return $this->update($id, [
            'status'       => 'in_progress',
            'actual_start' => now()->toDateTimeString(),
        ]);
    }

    public function markCompleted(int $id, int $attendees): ClassSession
    {
        $session = $this->getById($id);
        if (!$session) {
            throw new \Exception("Session not found");
        }
        $session->endSession($attendees);
        return $session->fresh();
    }

    public function markCancelled(int $id): ClassSession
    {
        return $this->update($id, ['status' => 'cancelled']);
    }

    public function getStats(): array
    {
        $tid  = $this->tenantId();
        $base = fn () => ClassSession::withoutGlobalScopes()->where('tenant_id', $tid);

        return [
            'total'     => $base()->count(),
            'scheduled' => $base()->where('status', 'scheduled')->count(),
            'live'      => $base()->where('status', 'in_progress')->count(),
            'completed' => $base()->where('status', 'completed')->count(),
            'cancelled' => $base()->where('status', 'cancelled')->count(),
        ];
    }
}