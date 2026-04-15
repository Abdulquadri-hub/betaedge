<?php

namespace App\Contracts\Services\School;

use Illuminate\Support\Collection;

interface DashboardServiceInterface
{
    public function getStats(): array;
    public function getTotalStudents(): int;
    public function getActiveBatchesCount(): int;
    public function getTotalBatchesCount(): int;
    public function getActiveCoursesCount(): int;
    public function getTotalCoursesCount(): int;
    public function getMonthlyRevenue();
    public function getPendingEnrollmentsCount(): int;
    public function getRevenueTrend(): Collection;
    public function getEnrollmentTrend(): Collection;
    public function getRecentActivity(int $limit = 6): Collection;
    public function getUpcomingSessions(int $limit = 3): Collection;
}
