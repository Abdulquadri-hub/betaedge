

import { ref, computed } from 'vue'

function sanitizeId(id) {
  if (!id || typeof id !== 'string') return null
  const clean = id.trim().slice(0, 100)
  return /^[a-zA-Z0-9_-]+$/.test(clean) ? clean : null
}

export const SESSION_STATUS = {
  scheduled: { label: 'Scheduled', variant: 'secondary', color: 'text-secondary'        },
  live:      { label: 'Live Now',  variant: 'default',   color: 'text-emerald-600'      },
  completed: { label: 'Completed', variant: 'outline',   color: 'text-muted-foreground' },
  cancelled: { label: 'Cancelled', variant: 'destructive', color: 'text-destructive'    },
}

export const ATTENDANCE_STATUS = {
  present: { label: 'Present', variant: 'default',     color: 'text-emerald-600' },
  late:    { label: 'Late',    variant: 'secondary',   color: 'text-amber-600'   },
  absent:  { label: 'Absent',  variant: 'destructive', color: 'text-destructive' },
  excused: { label: 'Excused', variant: 'outline',     color: 'text-muted-foreground' },
}

// ─── Mock sessions data ───────────────────────────────────────────────────────
const MOCK_SESSIONS = [
  {
    id: 'ses-001', batch_id: 'batch-001', course_id: 'course-001', tenant_id: 'tenant-001',
    title: 'Week 9 – Vue 3 Composition API Deep Dive',
    course_name: 'Full Stack Web Development', batch_name: 'Web Dev Batch 3',
    instructor_name: 'Mr. Chidi Okeke',
    scheduled_date: new Date(Date.now() + 2 * 86400000).toISOString().split('T')[0],
    scheduled_time: '18:00',
    duration_minutes: 90,
    meet_link: 'https://meet.jit.si/webdev-batch3-week9',
    status: 'scheduled',
    recording_url: null,
    notes: 'Cover computed(), watch(), watchEffect(). Students should review Week 8 code before class.',
    total_enrolled: 24,
    total_attendees: null,
    created_at: '2026-03-01',
  },
  {
    id: 'ses-002', batch_id: 'batch-002', course_id: 'course-002', tenant_id: 'tenant-001',
    title: 'Week 6 – Machine Learning with Scikit-Learn',
    course_name: 'Data Science & Analytics', batch_name: 'Data Science Batch 1',
    instructor_name: 'Ms. Kemi Adebayo',
    scheduled_date: new Date(Date.now() + 4 * 86400000).toISOString().split('T')[0],
    scheduled_time: '16:00',
    duration_minutes: 60,
    meet_link: 'https://zoom.us/j/datasci-batch1-week6',
    status: 'scheduled',
    recording_url: null,
    notes: 'Install scikit-learn before class: pip install scikit-learn',
    total_enrolled: 25,
    total_attendees: null,
    created_at: '2026-03-02',
  },
  {
    id: 'ses-003', batch_id: 'batch-001', course_id: 'course-001', tenant_id: 'tenant-001',
    title: 'Week 8 – Laravel API + Vue 3 Integration',
    course_name: 'Full Stack Web Development', batch_name: 'Web Dev Batch 3',
    instructor_name: 'Mr. Chidi Okeke',
    scheduled_date: new Date(Date.now() - 5 * 86400000).toISOString().split('T')[0],
    scheduled_time: '18:00',
    duration_minutes: 90,
    meet_link: 'https://meet.jit.si/webdev-batch3-week8',
    status: 'completed',
    recording_url: 'https://drive.google.com/recording/week8',
    notes: '',
    total_enrolled: 24,
    total_attendees: 21,
    created_at: '2026-02-22',
  },
  {
    id: 'ses-004', batch_id: 'batch-003', course_id: 'course-003', tenant_id: 'tenant-001',
    title: 'Week 5 – Figma Auto Layout & Components',
    course_name: 'UI/UX Design Fundamentals', batch_name: 'UI/UX Batch 2',
    instructor_name: 'Mr. Tayo Bello',
    scheduled_date: new Date(Date.now() - 2 * 86400000).toISOString().split('T')[0],
    scheduled_time: '10:00',
    duration_minutes: 120,
    meet_link: 'https://meet.google.com/uiux-batch2-week5',
    status: 'completed',
    recording_url: null,
    notes: '',
    total_enrolled: 18,
    total_attendees: 15,
    created_at: '2026-03-01',
  },
  {
    id: 'ses-005', batch_id: 'batch-001', course_id: 'course-001', tenant_id: 'tenant-001',
    title: 'Week 7 – Inertia.js Server-Side Rendering',
    course_name: 'Full Stack Web Development', batch_name: 'Web Dev Batch 3',
    instructor_name: 'Mr. Chidi Okeke',
    scheduled_date: new Date(Date.now() - 12 * 86400000).toISOString().split('T')[0],
    scheduled_time: '18:00',
    duration_minutes: 90,
    meet_link: 'https://meet.jit.si/webdev-batch3-week7',
    status: 'completed',
    recording_url: 'https://drive.google.com/recording/week7',
    notes: '',
    total_enrolled: 24,
    total_attendees: 22,
    created_at: '2026-02-15',
  },
]

// ─── Mock attendance per session ──────────────────────────────────────────────
const MOCK_ATTENDANCE = {
  'ses-003': [
    { id: 'att-001', student_id: 'stu-001', student_name: 'Ada Okonkwo',       status: 'present', joined_at: '18:02', left_at: '19:32', duration_minutes: 90 },
    { id: 'att-002', student_id: 'stu-002', student_name: 'Emeka Nwosu',        status: 'present', joined_at: '18:00', left_at: '19:30', duration_minutes: 90 },
    { id: 'att-003', student_id: 'stu-003', student_name: 'Chiamaka Eze',       status: 'late',    joined_at: '18:22', left_at: '19:30', duration_minutes: 68 },
    { id: 'att-004', student_id: 'stu-004', student_name: 'Timi Adeleke',       status: 'present', joined_at: '18:01', left_at: '19:30', duration_minutes: 89 },
    { id: 'att-005', student_id: 'stu-005', student_name: 'Ibrahim Abdullahi',  status: 'absent',  joined_at: null,    left_at: null,    duration_minutes: 0  },
    { id: 'att-006', student_id: 'stu-006', student_name: 'Ngozi Okafor',       status: 'present', joined_at: '18:05', left_at: '19:30', duration_minutes: 85 },
  ],
  'ses-004': [
    { id: 'att-007', student_id: 'stu-001', student_name: 'Ada Okonkwo',  status: 'present', joined_at: '10:00', left_at: '12:00', duration_minutes: 120 },
    { id: 'att-008', student_id: 'stu-002', student_name: 'Emeka Nwosu',  status: 'absent',  joined_at: null,    left_at: null,    duration_minutes: 0   },
    { id: 'att-009', student_id: 'stu-006', student_name: 'Ngozi Okafor', status: 'present', joined_at: '10:03', left_at: '12:00', duration_minutes: 117 },
  ],
}

// ─── Composable ───────────────────────────────────────────────────────────────
export function useLiveSessionsManager() {
  const sessions  = ref(MOCK_SESSIONS)
  const isLoading = ref(false)
  const error     = ref(null)

  // Filters
  const search       = ref('')
  const filterStatus = ref('all')
  const filterBatch  = ref('all')

  // ── Derived lists ─────────────────────────────────────────────────────────
  const upcomingSessions = computed(() =>
    sessions.value
      .filter(s => s.status === 'scheduled' || s.status === 'live')
      .sort((a, b) => new Date(`${a.scheduled_date}T${a.scheduled_time}`) - new Date(`${b.scheduled_date}T${b.scheduled_time}`))
  )

  const pastSessions = computed(() =>
    sessions.value
      .filter(s => s.status === 'completed' || s.status === 'cancelled')
      .sort((a, b) => new Date(`${b.scheduled_date}T${b.scheduled_time}`) - new Date(`${a.scheduled_date}T${a.scheduled_time}`))
  )

  const liveNow = computed(() => sessions.value.find(s => s.status === 'live') ?? null)

  const filteredSessions = computed(() => {
    let list = sessions.value
    if (filterStatus.value !== 'all') list = list.filter(s => s.status === filterStatus.value)
    if (filterBatch.value  !== 'all') list = list.filter(s => s.batch_id === filterBatch.value)
    const q = search.value.trim().toLowerCase()
    if (q) list = list.filter(s =>
      s.title.toLowerCase().includes(q)       ||
      s.batch_name.toLowerCase().includes(q)  ||
      s.course_name.toLowerCase().includes(q)
    )
    return list.sort((a, b) =>
      new Date(`${b.scheduled_date}T${b.scheduled_time}`) -
      new Date(`${a.scheduled_date}T${a.scheduled_time}`)
    )
  })

  const statusCounts = computed(() => ({
    all:       sessions.value.length,
    scheduled: sessions.value.filter(s => s.status === 'scheduled').length,
    live:      sessions.value.filter(s => s.status === 'live').length,
    completed: sessions.value.filter(s => s.status === 'completed').length,
    cancelled: sessions.value.filter(s => s.status === 'cancelled').length,
  }))

  // ── Helpers ───────────────────────────────────────────────────────────────
  function formatTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':').map(Number)
    const p      = h >= 12 ? 'PM' : 'AM'
    const hr     = h === 0 ? 12 : h > 12 ? h - 12 : h
    return `${hr}:${String(m).padStart(2, '0')} ${p}`
  }

  function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
  }

  function formatDateShort(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' })
  }

  function isToday(iso) {
    if (!iso) return false
    const today = new Date().toISOString().split('T')[0]
    return iso === today
  }

  function isTomorrow(iso) {
    if (!iso) return false
    const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0]
    return iso === tomorrow
  }

  function dayLabel(iso) {
    if (isToday(iso))    return 'Today'
    if (isTomorrow(iso)) return 'Tomorrow'
    return formatDate(iso)
  }

  function attendanceRate(session) {
    if (!session.total_attendees || !session.total_enrolled) return null
    return Math.round((session.total_attendees / session.total_enrolled) * 100)
  }

  function getSessionById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return sessions.value.find(s => s.id === safeId) ?? null
  }

  // ── Attendance ─────────────────────────────────────────────────────────────
  async function getAttendance(sessionId) {
    const safeId = sanitizeId(sessionId)
    if (!safeId) return []
    await new Promise(r => setTimeout(r, 200))
    // TODO (Laravel 12): passed via Inertia props in session detail, or fetched via router.get
    return MOCK_ATTENDANCE[safeId] ?? []
  }

  async function markAttendance(sessionId, studentId, status) {
    const safeSesId = sanitizeId(sessionId)
    const safeStuId = sanitizeId(studentId)
    if (!safeSesId || !safeStuId) return { success: false }
    /**
     * TODO (Laravel 12):
     * router.patch(route('dashboard.sessions.attendance.mark', { session: safeSesId, student: safeStuId }), {
     *   status,
     * }, { preserveScroll: true })
     */
    const list = MOCK_ATTENDANCE[safeSesId]
    if (list) {
      const idx = list.findIndex(a => a.student_id === safeStuId)
      if (idx !== -1) list[idx].status = status
    }
    return { success: true }
  }

  async function bulkMarkAttendance(sessionId, records) {
    const safeId = sanitizeId(sessionId)
    if (!safeId) return { success: false }
    /**
     * TODO (Laravel 12):
     * router.post(route('dashboard.sessions.attendance.bulk', safeId), { records }, {
     *   preserveScroll: true,
     * })
     */
    return { success: true }
  }

  // ── CRUD ──────────────────────────────────────────────────────────────────
  async function createSession(data) {
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 700))
      /**
       * TODO (Laravel 12):
       * router.post(route('dashboard.sessions.store'), data, {
       *   onSuccess: () => {},
       *   onError:   (e) => { error.value = e },
       *   preserveScroll: true,
       * })
       */
      const newSession = {
        id:              'ses-' + Date.now(),
        tenant_id:       'tenant-001',
        recording_url:   null,
        total_attendees: null,
        status:          'scheduled',
        created_at:      new Date().toISOString().split('T')[0],
        ...data,
      }
      sessions.value.unshift(newSession)
      return { success: true, session: newSession }
    } catch {
      error.value = 'Failed to create session'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function updateSession(id, data) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 500))
      /**
       * TODO (Laravel 12):
       * router.put(route('dashboard.sessions.update', safeId), data, { preserveScroll: true })
       */
      const idx = sessions.value.findIndex(s => s.id === safeId)
      if (idx !== -1) sessions.value[idx] = { ...sessions.value[idx], ...data }
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  async function deleteSession(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.delete(route('dashboard.sessions.destroy', safeId))
    sessions.value = sessions.value.filter(s => s.id !== safeId)
    return { success: true }
  }

  async function goLive(id) {
    // TODO (Laravel 12): router.patch(route('dashboard.sessions.goLive', id))
    return updateSession(id, { status: 'live' })
  }

  async function endSession(id, totalAttendees) {
    // TODO (Laravel 12): router.post(route('dashboard.sessions.end', id), { total_attendees: totalAttendees })
    return updateSession(id, { status: 'completed', total_attendees: totalAttendees })
  }

  async function cancelSession(id) {
    // TODO (Laravel 12): router.post(route('dashboard.sessions.cancel', id))
    return updateSession(id, { status: 'cancelled' })
  }

  return {
    sessions, filteredSessions, upcomingSessions, pastSessions, liveNow,
    isLoading, error,
    search, filterStatus, filterBatch, statusCounts,
    formatTime, formatDate, formatDateShort, dayLabel, isToday, isTomorrow,
    attendanceRate, getSessionById, getAttendance,
    markAttendance, bulkMarkAttendance,
    createSession, updateSession, deleteSession,
    goLive, endSession, cancelSession,
  }
}