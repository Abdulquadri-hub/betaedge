import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * useDashboardStats
 * ─────────────────────────────────────────────────────────────
 * Provides all stat cards, revenue data, and recent activity
 * for the Dashboard Home page.
 *
 * Data Source: Laravel Controller passes props via Inertia
 * Controller: DashboardService injected in HomeController
 */

export function useDashboardStats() {
  const page = usePage()

  // Get data from Inertia props
  const statsProps = computed(() => page.props.stats || {})
  const recentActivityProps = computed(() => page.props.recentActivity || [])
  const upcomingSessionsProps = computed(() => page.props.upcomingSessions || [])
  const revenueChartProps = computed(() => page.props.revenueChart || [])

  // Transform stats to match component structure
  const stats = computed(() => ({
    totalStudents: statsProps.value.totalStudents || { value: 0, change: 0, trend: 'neutral' },
    activeStudents: { value: 0, change: 0, trend: 'neutral' },
    totalBatches: statsProps.value.totalBatches || { value: 0, change: 0, trend: 'neutral' },
    activeBatches: statsProps.value.activeBatches || { value: 0, change: 0, trend: 'neutral' },
    totalCourses: statsProps.value.totalCourses || { value: 0, change: 0, trend: 'neutral' },
    activeCourses: statsProps.value.activeCourses || { value: 0, change: 0, trend: 'neutral' },
    monthRevenue: statsProps.value.monthRevenue || { value: 0, change: 0, trend: 'neutral' },
    pendingEnrollments: statsProps.value.pendingEnrollments || { value: 0, change: 0, trend: 'neutral' },
    sessionsThisWeek: { value: 0, change: 0, trend: 'neutral' },
  }))

  const recentActivity = computed(() => recentActivityProps.value)
  const upcomingSessions = computed(() => upcomingSessionsProps.value)
  const revenueChart = computed(() => revenueChartProps.value)
  
  const isLoading = ref(false)

  // ── Formatted helpers ─────────────────────────────────────
  /** ₦1,840,000 */
  const formattedRevenue = computed(() => {
    const revenue = stats.value.monthRevenue.value
    return '₦' + (revenue / 100).toLocaleString('en-NG')
  })

  /** Revenue trend color */
  function trendColor(trend) {
    return trend === 'up' ? 'text-emerald-600' : trend === 'down' ? 'text-red-500' : 'text-muted-foreground'
  }

  function trendIcon(trend) {
    return trend === 'up' ? '↑' : trend === 'down' ? '↓' : '—'
  }

  /**
   * Refresh dashboard stats via Inertia reload
   */
  async function refresh() {
    isLoading.value = true
    try {
      // Reload only the stats/props from server
      window.location.reload()
    } finally {
      isLoading.value = false
    }
  }

  return {
    stats,
    recentActivity,
    upcomingSessions,
    revenueChart,
    isLoading,
    formattedRevenue,
    trendColor,
    trendIcon,
    refresh,
  }
}