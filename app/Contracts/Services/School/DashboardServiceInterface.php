<?php

namespace App\Contracts\Services\School;

use Illuminate\Support\Collection;

interface DashboardServiceInterface
{
    /**
     * Get dashboard statistics for the active tenant
     *
     * @return array
     */
    public function getStats(): array;

    /**
     * Get total students count for the tenant
     *
     * @return int
     */
    public function getTotalStudents(): int;

    /**
     * Get active batches count
     *
     * @return int
     */
    public function getActiveBatchesCount(): int;

    /**
     * Get total batches count
     *
     * @return int
     */
    public function getTotalBatchesCount(): int;

    /**
     * Get active courses count
     *
     * @return int
     */
    public function getActiveCoursesCount(): int;

    /**
     * Get total courses count
     *
     * @return int
     */
    public function getTotalCoursesCount(): int;

    /**
     * Get monthly revenue in currency's smallest unit (e.g., kobo for NGN)
     *
     * @return int|float
     */
    public function getMonthlyRevenue();

    /**
     * Get pending enrollments count
     *
     * @return int
     */
    public function getPendingEnrollmentsCount(): int;

    /**
     * Get revenue trend data (last 6 months)
     *
     * @return Collection
     */
    public function getRevenueTrend(): Collection;

    /**
     * Get enrollment trend data (last 6 months)
     *
     * @return Collection
     */
    public function getEnrollmentTrend(): Collection;

    /**
     * Get recent activity
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivity(int $limit = 6): Collection;

    /**
     * Get upcoming sessions
     *
     * @param int $limit
     * @return Collection
     */
    public function getUpcomingSessions(int $limit = 3): Collection;
}
