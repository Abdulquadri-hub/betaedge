<?php

namespace App\Services\School;

use App\Models\Student;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\ActivityLog;
use App\Models\ClassSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Services\School\DashboardServiceInterface;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        protected BatchRepositoryInterface $batchRepo,
        protected CourseRepositoryInterface $courseRepo,
    ) {}

    /**
     * Get dashboard statistics for the active tenant
     */
    public function getStats(): array
    {
        return [
            'totalStudents' => [
                'value' => $this->getTotalStudents(),
                'change' => $this->getStudentTrendChange(),
                'trend' => $this->getStudentTrend(),
            ],
            'activeBatches' => [
                'value' => $this->getActiveBatchesCount(),
                'change' => 0,
                'trend' => 'neutral',
            ],
            'totalBatches' => [
                'value' => $this->getBatchStats()['total'],
                'change' => 0,
                'trend' => 'neutral',
            ],
            'totalCourses' => [
                'value' => $this->getTotalCoursesCount(),
                'change' => 0,
                'trend' => 'neutral',
            ],
            'activeCourses' => [
                'value' => $this->getActiveCoursesCount(),
                'change' => 0,
                'trend' => 'neutral',
            ],
            'monthRevenue' => [
                'value' => $this->getMonthlyRevenue(),
                'change' => $this->getMonthlyRevenueChange(),
                'trend' => $this->getRevenueTrendDirection(),
            ],
            'pendingEnrollments' => [
                'value' => $this->getPendingEnrollmentsCount(),
                'change' => 0,
                'trend' => 'neutral',
            ],
        ];
    }

    /**
     * Get total students count for the tenant
     */
    public function getTotalStudents(): int
    {
        return Student::where('tenant_id', session('active_tenant_id'))->count();
    }

    /**
     * Get active batches count
     */
    public function getActiveBatchesCount(): int
    {
        return $this->batchRepo->countActive();
    }

    /**
     * Get total batches count
     */
    public function getTotalBatchesCount(): int
    {
        return $this->batchRepo->count();
    }

    /**
     * Get active courses count
     */
    public function getActiveCoursesCount(): int
    {
        return $this->courseRepo->getActive()->count();
    }

    /**
     * Get total courses count
     */
    public function getTotalCoursesCount(): int
    {
        return $this->courseRepo->count();
    }

    /**
     * Get monthly revenue in currency's smallest unit (e.g., kobo for NGN)
     */
    public function getMonthlyRevenue()
    {
        return (int) Payment::where('tenant_id', session('active_tenant_id'))
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get pending enrollments count
     */
    public function getPendingEnrollmentsCount(): int
    {
        return Enrollment::where('tenant_id', session('active_tenant_id'))
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Get revenue trend data (last 6 months)
     */
    public function getRevenueTrend(): Collection
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i);
        }

        return collect($months)->map(function ($month) {
            $revenue = (int) Payment::where('tenant_id', session('active_tenant_id'))
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->where('status', 'completed')
                ->sum('amount');

            return [
                'month' => $month->format('M'),
                'revenue' => $revenue,
                'students' => 0, // Can be calculated from enrollments if needed
            ];
        });
    }

    /**
     * Get enrollment trend data (last 6 months)
     */
    public function getEnrollmentTrend(): Collection
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i);
        }

        return collect($months)->map(function ($month) {
            $count = Enrollment::where('tenant_id', session('active_tenant_id'))
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            return [
                'month' => $month->format('M'),
                'enrollments' => $count,
            ];
        });
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 6): Collection
    {
        $tenantId = session('active_tenant_id');

        // Get recent enrollments
        $enrollments = Enrollment::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'type' => 'enrollment',
                    'actor' => $enrollment->student?->user?->name ?? 'Student',
                    'action' => 'enrolled in',
                    'target' => $enrollment->course?->title ?? 'Unknown Course',
                    'time' => $enrollment->created_at->diffForHumans(),
                    'avatar' => $enrollment->student?->user?->avatar,
                ];
            });

        return $enrollments;
    }

    /**
     * Get upcoming sessions
     */
    public function getUpcomingSessions(int $limit = 3): Collection
    {
        return ClassSession::where('tenant_id', session('active_tenant_id'))
            ->where('scheduled_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title,
                    'course' => $session->course?->title ?? 'Unknown',
                    'instructor' => $session->instructor?->user?->name ?? 'Unknown',
                    'scheduledAt' => $session->scheduled_at->toIso8601String(),
                    'duration' => $session->duration_minutes,
                    'platform' => 'google_meet', // Can be stored in DB
                    'enrolledCount' => $session->course?->activeEnrollments()->count() ?? 0,
                ];
            });
    }

    /**
     * Get batch statistics
     */
    public function getBatchStats(): array
    {
        return $this->batchRepo->getStats();
    }

    /**
     * Get course statistics
     */
    public function getCourseStats(): array
    {
        return $this->courseRepo->getStats();
    }

    /**
     * Get student trend change
     */
    private function getStudentTrendChange(): int
    {
        $current = Student::where('tenant_id', session('active_tenant_id'))
            ->whereMonth('created_at', now()->month)
            ->count();

        $previous = Student::where('tenant_id', session('active_tenant_id'))
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        if ($previous === 0) {
            return $current;
        }

        return round((($current - $previous) / $previous) * 100);
    }

    /**
     * Get student trend direction
     */
    private function getStudentTrend(): string
    {
        $change = $this->getStudentTrendChange();

        if ($change > 0) {
            return 'up';
        } elseif ($change < 0) {
            return 'down';
        }

        return 'neutral';
    }

    /**
     * Get monthly revenue change
     */
    private function getMonthlyRevenueChange(): int
    {
        $current = $this->getMonthlyRevenue();

        $previous = (int) Payment::where('tenant_id', session('active_tenant_id'))
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->where('status', 'completed')
            ->sum('amount');

        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100);
    }

    /**
     * Get revenue trend direction
     */
    private function getRevenueTrendDirection(): string
    {
        $change = $this->getMonthlyRevenueChange();

        if ($change > 0) {
            return 'up';
        } elseif ($change < 0) {
            return 'down';
        }

        return 'neutral';
    }
}
