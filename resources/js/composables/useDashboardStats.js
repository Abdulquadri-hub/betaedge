import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage } from '@inertiajs/vue3'

/**
 * useDashboardStats
 * ─────────────────────────────────────────────────────────────
 * Provides all stat cards, revenue data, and recent activity
 * for the Dashboard Home page.
 *
 * Laravel Integration (when ready):
 *   const { stats } = usePage().props
 *   Replace mock data with real props.
 *   Controller: DashboardController@index
 */

// ─── Mock Stats ───────────────────────────────────────────────
const MOCK_STATS = {
  totalStudents:    { value: 248,  change: +12,  trend: 'up' },
  activeStudents:   { value: 189,  change: +8,   trend: 'up' },
  totalBatches:     { value: 14,   change: +2,   trend: 'up' },
  activeBatches:    { value: 6,    change: 0,    trend: 'neutral' },
  totalCourses:     { value: 9,    change: +1,   trend: 'up' },
  monthRevenue:     { value: 1840000, change: +18, trend: 'up' },   // in kobo / lowest unit → display as ₦
  pendingEnrollments: { value: 7, change: +3,   trend: 'up' },
  sessionsThisWeek: { value: 11,  change: -2,   trend: 'down' },
}

// ─── Recent activity ──────────────────────────────────────────
const MOCK_ACTIVITY = [
  { id: 1, type: 'enrollment', actor: 'Ada Okonkwo',    action: 'enrolled in',  target: 'Web Dev Batch 3',     time: '2 min ago',  avatar: null },
  { id: 2, type: 'payment',    actor: 'Emeka Nwosu',    action: 'paid for',     target: 'Data Science Batch 1',time: '18 min ago', avatar: null },
  { id: 3, type: 'session',    actor: 'Mr. Chidi',      action: 'started',      target: 'JS Fundamentals',     time: '1 hr ago',   avatar: null },
  { id: 4, type: 'enrollment', actor: 'Ngozi Eze',      action: 'enrolled in',  target: 'UI/UX Batch 2',       time: '3 hrs ago',  avatar: null },
  { id: 5, type: 'complaint',  actor: 'Bola Adeleke',   action: 'filed complaint about', target: 'missed session', time: '5 hrs ago', avatar: null },
  { id: 6, type: 'payment',    actor: 'Seun Afolabi',   action: 'paid for',     target: 'Excel Mastery Batch 1','time': 'Yesterday', avatar: null },
]

// ─── Upcoming sessions ────────────────────────────────────────
const MOCK_UPCOMING_SESSIONS = [
  {
    id: 'sess-1',
    title: 'JavaScript: Async/Await Deep Dive',
    course: 'Web Development',
    instructor: 'Mr. Chidi Okeke',
    scheduledAt: new Date(Date.now() + 30 * 60 * 1000).toISOString(), // 30 min from now
    duration: 90,
    platform: 'jitsi',
    enrolledCount: 24,
  },
  {
    id: 'sess-2',
    title: 'Data Cleaning with Pandas',
    course: 'Data Science',
    instructor: 'Ms. Kemi Adebayo',
    scheduledAt: new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString(), // 2 hrs
    duration: 60,
    platform: 'zoom',
    enrolledCount: 18,
  },
  {
    id: 'sess-3',
    title: 'Figma: Component Libraries',
    course: 'UI/UX Design',
    instructor: 'Mr. Tayo Bello',
    scheduledAt: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(), // tomorrow
    duration: 75,
    platform: 'jitsi',
    enrolledCount: 15,
  },
]

// ─── Revenue chart (last 6 months) ───────────────────────────
const MOCK_REVENUE_CHART = [
  { month: 'Sep', revenue: 980000,  students: 38 },
  { month: 'Oct', revenue: 1120000, students: 45 },
  { month: 'Nov', revenue: 1350000, students: 52 },
  { month: 'Dec', revenue: 890000,  students: 31 }, // holiday dip
  { month: 'Jan', revenue: 1560000, students: 61 },
  { month: 'Feb', revenue: 1840000, students: 74 },
]

export function useDashboardStats() {
  const stats          = ref(MOCK_STATS)
  const recentActivity = ref(MOCK_ACTIVITY)
  const upcomingSessions = ref(MOCK_UPCOMING_SESSIONS)
  const revenueChart   = ref(MOCK_REVENUE_CHART)
  const isLoading      = ref(false)

  // ── Formatted helpers ─────────────────────────────────────
  /** ₦1,840,000 */
  const formattedRevenue = computed(() =>
    '₦' + (stats.value.monthRevenue.value / 100).toLocaleString('en-NG')
  )

  /** Revenue trend color */
  function trendColor(trend) {
    return trend === 'up' ? 'text-emerald-600' : trend === 'down' ? 'text-red-500' : 'text-muted-foreground'
  }

  function trendIcon(trend) {
    return trend === 'up' ? '↑' : trend === 'down' ? '↓' : '—'
  }

  /**
   * Simulate fetching — replace with actual Inertia reload or axios call.
   * TODO (Laravel): router.reload({ only: ['stats'] })
   */
  async function refresh() {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 600))
    isLoading.value = false
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