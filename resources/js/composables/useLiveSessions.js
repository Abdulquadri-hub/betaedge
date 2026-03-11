import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage, router } from '@inertiajs/vue3'

/**
 * useLiveSessions
 * ─────────────────────────────────────────────────────────────
 * Manages live session data, scheduling, and status.
 *
 * Laravel Integration:
 *   const { sessions } = usePage().props
 *   router.post('/dashboard/live-sessions', data)
 */

const MOCK_SESSIONS = [
  {
    id: 'sess-001',
    title: 'JavaScript: Async/Await Deep Dive',
    courseId: 'course-001',
    courseName: 'Full Stack Web Development',
    batchId: 'batch-001',
    batchName: 'Web Dev Batch 3',
    instructorId: 'inst-001',
    instructorName: 'Mr. Chidi Okeke',
    instructorAvatar: null,
    platform: 'jitsi',          // 'jitsi' | 'zoom' | 'custom'
    status: 'scheduled',        // 'scheduled' | 'live' | 'completed' | 'cancelled'
    scheduledAt: new Date(Date.now() + 30 * 60 * 1000).toISOString(),
    duration: 90,
    meetingLink: 'https://meet.jit.si/teach-sess-001',
    enrolledCount: 24,
    attendeeCount: null,
    description: 'Deep dive into promises, async/await and error handling.',
    createdAt: '2026-02-20',
  },
  {
    id: 'sess-002',
    title: 'Data Cleaning with Pandas',
    courseId: 'course-002',
    courseName: 'Data Science & Analytics',
    batchId: 'batch-002',
    batchName: 'Data Science Batch 1',
    instructorId: 'inst-002',
    instructorName: 'Ms. Kemi Adebayo',
    instructorAvatar: null,
    platform: 'zoom',
    status: 'live',
    scheduledAt: new Date(Date.now() - 20 * 60 * 1000).toISOString(), // 20 min ago — live
    duration: 60,
    meetingLink: 'https://zoom.us/j/987654321',
    enrolledCount: 25,
    attendeeCount: 21,
    description: null,
    createdAt: '2026-02-18',
  },
  {
    id: 'sess-003',
    title: 'Figma: Component Libraries',
    courseId: 'course-003',
    courseName: 'UI/UX Design',
    batchId: 'batch-003',
    batchName: 'UI/UX Batch 2',
    instructorId: 'inst-003',
    instructorName: 'Mr. Tayo Bello',
    instructorAvatar: null,
    platform: 'jitsi',
    status: 'completed',
    scheduledAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
    duration: 75,
    meetingLink: null,
    enrolledCount: 11,
    attendeeCount: 9,
    description: null,
    createdAt: '2026-02-15',
  },
]

export function useLiveSessions() {
  const sessions  = ref(MOCK_SESSIONS)
  const isLoading = ref(false)
  const filterStatus = ref('all')

  const filteredSessions = computed(() => {
    if (filterStatus.value === 'all') return sessions.value
    return sessions.value.filter(s => s.status === filterStatus.value)
  })

  const liveSessions = computed(() =>
    sessions.value.filter(s => s.status === 'live')
  )

  const upcomingSessions = computed(() =>
    sessions.value.filter(s => s.status === 'scheduled')
  )

  const counts = computed(() => ({
    all:       sessions.value.length,
    live:      sessions.value.filter(s => s.status === 'live').length,
    scheduled: sessions.value.filter(s => s.status === 'scheduled').length,
    completed: sessions.value.filter(s => s.status === 'completed').length,
  }))

  async function createSession(data) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 800))
    // TODO (Laravel): router.post('/dashboard/live-sessions', data)
    const newSession = {
      id: 'sess-' + Date.now(),
      enrolledCount: 0,
      attendeeCount: null,
      createdAt: new Date().toISOString().split('T')[0],
      ...data,
    }
    sessions.value.unshift(newSession)
    isLoading.value = false
    return newSession
  }

  async function deleteSession(id) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 500))
    // TODO (Laravel): router.delete(`/dashboard/live-sessions/${id}`)
    sessions.value = sessions.value.filter(s => s.id !== id)
    isLoading.value = false
  }

  return {
    sessions,
    filteredSessions,
    liveSessions,
    upcomingSessions,
    isLoading,
    filterStatus,
    counts,
    createSession,
    deleteSession,
  }
}