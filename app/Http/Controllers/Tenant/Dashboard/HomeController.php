<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Services\School\DashboardServiceInterface;

class HomeController extends Controller
{
    public function __construct(
        protected DashboardServiceInterface $dashboardService
    ) {}

    public function index(Request $request) {
        $stats = $this->dashboardService->getStats();
        $recentActivity = $this->dashboardService->getRecentActivity();
        $upcomingSessions = $this->dashboardService->getUpcomingSessions();
        $revenueChart = $this->dashboardService->getRevenueTrend();

        return Inertia::render('School/Dashboard/Home', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'upcomingSessions' => $upcomingSessions,
            'revenueChart' => $revenueChart,
        ]);
    }
}

