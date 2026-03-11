
import { ref, computed } from 'vue'

function sanitizeId(id) {
  if (!id || typeof id !== 'string') return null
  const clean = id.trim().slice(0, 100)
  return /^[a-zA-Z0-9_-]+$/.test(clean) ? clean : null
}

export const ACADEMIC_LEVELS = [
  'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
  'JSS 1', 'JSS 2', 'JSS 3',
  'SSS 1', 'SSS 2', 'SSS 3',
  'University', 'Professional', 'All Ages',
]

export const SESSION_PLATFORMS = [
  { value: 'google_meet', label: 'Google Meet' },
  { value: 'zoom',        label: 'Zoom'        },
  { value: 'jitsi',       label: 'Jitsi Meet'  },
  { value: 'microsoft_teams', label: 'Microsoft Teams' },
  { value: 'custom',      label: 'Other / Custom' },
]

export const SESSION_FREQUENCIES = [
  { value: 'weekly',      label: 'Weekly'          },
  { value: 'twice_weekly',label: 'Twice a Week'    },
  { value: 'daily',       label: 'Daily (Mon-Fri)' },
  { value: 'weekends',    label: 'Weekends Only'   },
  { value: 'custom',      label: 'Custom Schedule' },
]

export const SCHEDULE_DAYS = [
  'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
  'Mon/Wed', 'Tue/Thu', 'Mon/Wed/Fri', 'Sat/Sun',
]

const MOCK_COURSES = [
  {
    id:                       'course-001',
    tenant_id:                'tenant-001',
    title:                    'Full Stack Web Development',
    slug:                     'full-stack-web-development',
    description:              'Master HTML, CSS, JavaScript, Vue 3, and Laravel from scratch. Build real-world projects and deploy to production.',
    academic_level:           'Professional',
    duration_weeks:           12,
    price_per_student:        65000,
    session_frequency:        'twice_weekly',
    session_day:              'Mon/Wed/Fri',
    session_time:             '18:00',
    session_duration_minutes: 90,
    session_platform:         'jitsi',
    status:                   'published', // 'draft' | 'published' | 'archived'
    thumbnail:                null,
    // Aggregated from batches/enrollments
    total_batches:            3,
    active_batches:           1,
    total_students:           87,
    total_revenue:            5655000, // 87 × 65000
    avg_rating:               4.8,
    completion_rate:          88,
    created_at:               '2025-06-01',
    updated_at:               '2026-01-10',
  },
  {
    id:                       'course-002',
    tenant_id:                'tenant-001',
    title:                    'Data Science & Analytics',
    slug:                     'data-science-analytics',
    description:              'Python, Pandas, NumPy, data visualisation, machine learning basics and real-world data projects.',
    academic_level:           'University',
    duration_weeks:           12,
    price_per_student:        75000,
    session_frequency:        'twice_weekly',
    session_day:              'Tue/Thu',
    session_time:             '16:00',
    session_duration_minutes: 60,
    session_platform:         'zoom',
    status:                   'published',
    thumbnail:                null,
    total_batches:            1,
    active_batches:           1,
    total_students:           25,
    total_revenue:            1875000,
    avg_rating:               4.9,
    completion_rate:          null,
    created_at:               '2025-09-01',
    updated_at:               '2026-01-05',
  },
  {
    id:                       'course-003',
    tenant_id:                'tenant-001',
    title:                    'UI/UX Design Fundamentals',
    slug:                     'ui-ux-design',
    description:              'Figma, design systems, user research, wireframing, prototyping and portfolio building.',
    academic_level:           'All Ages',
    duration_weeks:           8,
    price_per_student:        55000,
    session_frequency:        'weekends',
    session_day:              'Sat',
    session_time:             '10:00',
    session_duration_minutes: 120,
    session_platform:         'google_meet',
    status:                   'published',
    thumbnail:                null,
    total_batches:            2,
    active_batches:           1,
    total_students:           41,
    total_revenue:            2255000,
    avg_rating:               4.7,
    completion_rate:          85,
    created_at:               '2025-08-01',
    updated_at:               '2025-12-20',
  },
  {
    id:                       'course-004',
    tenant_id:                'tenant-001',
    title:                    'Microsoft Excel Mastery',
    slug:                     'excel-mastery',
    description:              'From basics to advanced formulas, pivot tables, Power Query, dashboards and automation with macros.',
    academic_level:           'Professional',
    duration_weeks:           8,
    price_per_student:        35000,
    session_frequency:        'twice_weekly',
    session_day:              'Wed/Fri',
    session_time:             '19:00',
    session_duration_minutes: 60,
    session_platform:         'google_meet',
    status:                   'published',
    thumbnail:                null,
    total_batches:            1,
    active_batches:           0,
    total_students:           33,
    total_revenue:            1155000,
    avg_rating:               4.6,
    completion_rate:          87,
    created_at:               '2025-07-15',
    updated_at:               '2025-12-01',
  },
  {
    id:                       'course-005',
    tenant_id:                'tenant-001',
    title:                    'Digital Marketing Pro',
    slug:                     'digital-marketing-pro',
    description:              'SEO, social media strategy, email campaigns, Google Ads, analytics and conversion optimisation.',
    academic_level:           'Professional',
    duration_weeks:           6,
    price_per_student:        45000,
    session_frequency:        'weekly',
    session_day:              'Saturday',
    session_time:             '14:00',
    session_duration_minutes: 90,
    session_platform:         'zoom',
    status:                   'draft',
    thumbnail:                null,
    total_batches:            0,
    active_batches:           0,
    total_students:           0,
    total_revenue:            0,
    avg_rating:               null,
    completion_rate:          null,
    created_at:               '2026-02-01',
    updated_at:               '2026-02-25',
  },
]

// ─── Mock materials (course_materials table) ──────────────────────────────────
const MOCK_MATERIALS = {
  'course-001': [
    { id: 'mat-001', course_id: 'course-001', title: 'Course Outline & Curriculum', type: 'pdf',  module: 'Week 1',  week: 1, url: '#', size_kb: 1240, downloads: 87, uploaded_at: '2026-02-01' },
    { id: 'mat-002', course_id: 'course-001', title: 'HTML Foundations Cheat Sheet',  type: 'pdf',  module: 'Week 1',  week: 1, url: '#', size_kb:  410, downloads: 82, uploaded_at: '2026-02-01' },
    { id: 'mat-003', course_id: 'course-001', title: 'CSS Flexbox Practice Exercise', type: 'pdf',  module: 'Week 2',  week: 2, url: '#', size_kb:  820, downloads: 61, uploaded_at: '2026-02-08' },
    { id: 'mat-004', course_id: 'course-001', title: 'JavaScript Exercises Pack',     type: 'pdf',  module: 'Week 3',  week: 3, url: '#', size_kb: 2100, downloads: 45, uploaded_at: '2026-02-15' },
    { id: 'mat-005', course_id: 'course-001', title: 'Vue 3 Reference Guide',         type: 'link', module: 'Week 5',  week: 5, url: 'https://vuejs.org', size_kb: 0, downloads: 39, uploaded_at: '2026-02-22' },
  ],
}

// ─── Composable ───────────────────────────────────────────────────────────────
export function useDashboardCourses() {
  // TODO (Laravel 12): const { courses } = usePage().props
  const courses   = ref(MOCK_COURSES)
  const isLoading = ref(false)
  const error     = ref(null)

  const search       = ref('')
  const filterStatus = ref('all') // 'all' | 'draft' | 'published' | 'archived'

  // ── Filtered list ─────────────────────────────────────────────────────
  const filteredCourses = computed(() => {
    let list = courses.value
    if (filterStatus.value !== 'all') {
      list = list.filter(c => c.status === filterStatus.value)
    }
    const q = search.value.trim().toLowerCase()
    if (q) {
      list = list.filter(c =>
        c.title.toLowerCase().includes(q) ||
        c.academic_level.toLowerCase().includes(q) ||
        c.description.toLowerCase().includes(q)
      )
    }
    return list
  })

  // ── Published courses for dropdowns ───────────────────────────────────
  const publishedCourses = computed(() =>
    courses.value.filter(c => c.status === 'published')
  )

  const statusCounts = computed(() => ({
    all:       courses.value.length,
    published: courses.value.filter(c => c.status === 'published').length,
    draft:     courses.value.filter(c => c.status === 'draft').length,
    archived:  courses.value.filter(c => c.status === 'archived').length,
  }))

  // ── Summary stats ─────────────────────────────────────────────────────
  const totalRevenue = computed(() =>
    courses.value.reduce((sum, c) => sum + (c.total_revenue ?? 0), 0)
  )

  const totalStudents = computed(() =>
    courses.value.reduce((sum, c) => sum + (c.total_students ?? 0), 0)
  )

  // ── Helpers ───────────────────────────────────────────────────────────
  function formatNaira(amount) {
    if (amount >= 1000000) return '₦' + (amount / 1000000).toFixed(1) + 'M'
    if (amount >= 1000)    return '₦' + (amount / 1000).toFixed(0) + 'K'
    return '₦' + (amount ?? 0).toLocaleString('en-NG')
  }

  function formatTime(time24) {
    if (!time24) return ''
    const [h, m] = time24.split(':').map(Number)
    const period = h >= 12 ? 'PM' : 'AM'
    const hour   = h === 0 ? 12 : h > 12 ? h - 12 : h
    return `${hour}:${String(m).padStart(2, '0')} ${period}`
  }

  function platformLabel(val) {
    return SESSION_PLATFORMS.find(p => p.value === val)?.label ?? val
  }

  function frequencyLabel(val) {
    return SESSION_FREQUENCIES.find(f => f.value === val)?.label ?? val
  }

  function getCourseById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return courses.value.find(c => c.id === safeId) ?? null
  }

  async function getMaterials(courseId) {
    const safeId = sanitizeId(courseId)
    if (!safeId) return []
    // TODO (Laravel 12): passed via usePage().props in CourseBuilderPage
    await new Promise(r => setTimeout(r, 200))
    return MOCK_MATERIALS[safeId] ?? []
  }

  // ── CRUD ──────────────────────────────────────────────────────────────
  async function createCourse(data) {
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 900))
      /**
       * TODO (Laravel 12):
       * router.post(route('dashboard.courses.store'), data, {
       *   onSuccess: () => {},
       *   onError:   (e) => { error.value = e },
       *   preserveScroll: true,
       * })
       */
      const slug    = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
      const newCourse = {
        id:              'course-' + Date.now(),
        tenant_id:       'tenant-001',
        slug,
        total_batches:   0,
        active_batches:  0,
        total_students:  0,
        total_revenue:   0,
        avg_rating:      null,
        completion_rate: null,
        status:          'draft',
        created_at:      new Date().toISOString().split('T')[0],
        updated_at:      new Date().toISOString().split('T')[0],
        ...data,
      }
      courses.value.unshift(newCourse)
      return { success: true, course: newCourse }
    } catch {
      error.value = 'Failed to create course'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function updateCourse(id, data) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 600))
      /**
       * TODO (Laravel 12):
       * router.put(route('dashboard.courses.update', safeId), data, {
       *   preserveScroll: true,
       * })
       */
      const idx = courses.value.findIndex(c => c.id === safeId)
      if (idx !== -1) {
        courses.value[idx] = {
          ...courses.value[idx],
          ...data,
          updated_at: new Date().toISOString().split('T')[0],
        }
      }
      return { success: true }
    } catch {
      error.value = 'Failed to update course'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function publishCourse(id) {
    return updateCourse(id, { status: 'published' })
    // TODO (Laravel 12): router.post(route('dashboard.courses.publish', id))
  }

  async function archiveCourse(id) {
    return updateCourse(id, { status: 'archived' })
    // TODO (Laravel 12): router.post(route('dashboard.courses.archive', id))
  }

  async function deleteCourse(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 500))
      // TODO (Laravel 12): router.delete(route('dashboard.courses.destroy', safeId))
      courses.value = courses.value.filter(c => c.id !== safeId)
      return { success: true }
    } catch {
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function duplicateCourse(id) {
    const original = getCourseById(id)
    if (!original) return { success: false }
    return createCourse({
      ...original,
      id:             undefined,
      title:          original.title + ' (Copy)',
      status:         'draft',
      total_batches:  0,
      active_batches: 0,
      total_students: 0,
      total_revenue:  0,
    })
    // TODO (Laravel 12): router.post(route('dashboard.courses.duplicate', id))
  }

  return {
    courses,
    filteredCourses,
    publishedCourses,
    isLoading,
    error,
    search,
    filterStatus,
    statusCounts,
    totalRevenue,
    totalStudents,
    formatNaira,
    formatTime,
    platformLabel,
    frequencyLabel,
    getCourseById,
    getMaterials,
    createCourse,
    updateCourse,
    publishCourse,
    archiveCourse,
    deleteCourse,
    duplicateCourse,
  }
}