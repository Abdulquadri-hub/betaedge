
import { ref, computed } from 'vue'

function sanitizeId(id) {
  if (!id || typeof id !== 'string') return null
  const clean = id.trim().slice(0, 100)
  return /^[a-zA-Z0-9_-]+$/.test(clean) ? clean : null
}

const MOCK_BATCHES = [
  {
    id:                  'batch-001',
    tenant_id:           'tenant-001',
    course_id:           'course-001',
    course_name:         'Full Stack Web Development',
    instructor_id:       'inst-001',
    instructor_name:     'Mr. Chidi Okeke',
    instructor_avatar:   null,
    name:                'Web Dev Batch 3',
    status:              'open',      // open | active | closed | completed
    start_date:          '2026-03-01',
    end_date:            '2026-05-31',
    max_students:        30,
    current_enrollment:  24,
    schedule_day:        'Mon/Wed/Fri',
    schedule_time:       '18:00',
    whatsapp_link:       'https://chat.whatsapp.com/abc123ExampleLink',
    price_per_student:   65000,
    description:         'Evening cohort for working professionals. 3 months of full-stack development.',
    created_at:          '2026-02-01',
    // Computed from enrollments table
    students: [],       // populated in BatchDetailPage
    sessions: [],
    completion_rate: null,
  },
  {
    id:                  'batch-002',
    tenant_id:           'tenant-001',
    course_id:           'course-002',
    course_name:         'Data Science & Analytics',
    instructor_id:       'inst-002',
    instructor_name:     'Ms. Kemi Adebayo',
    instructor_avatar:   null,
    name:                'Data Science Batch 1',
    status:              'active',
    start_date:          '2026-01-15',
    end_date:            '2026-04-15',
    max_students:        25,
    current_enrollment:  25,
    schedule_day:        'Tue/Thu',
    schedule_time:       '16:00',
    whatsapp_link:       'https://chat.whatsapp.com/def456ExampleLink',
    price_per_student:   75000,
    description:         null,
    created_at:          '2026-01-01',
    students: [],
    sessions: [],
    completion_rate: null,
  },
  {
    id:                  'batch-003',
    tenant_id:           'tenant-001',
    course_id:           'course-003',
    course_name:         'UI/UX Design',
    instructor_id:       'inst-003',
    instructor_name:     'Mr. Tayo Bello',
    instructor_avatar:   null,
    name:                'UI/UX Batch 2',
    status:              'open',
    start_date:          '2026-03-10',
    end_date:            '2026-05-10',
    max_students:        20,
    current_enrollment:  11,
    schedule_day:        'Sat',
    schedule_time:       '10:00',
    whatsapp_link:       null,
    price_per_student:   55000,
    description:         'Weekend intensive for beginners.',
    created_at:          '2026-02-10',
    students: [],
    sessions: [],
    completion_rate: null,
  },
  {
    id:                  'batch-004',
    tenant_id:           'tenant-001',
    course_id:           'course-004',
    course_name:         'Microsoft Excel Mastery',
    instructor_id:       'inst-001',
    instructor_name:     'Mr. Chidi Okeke',
    instructor_avatar:   null,
    name:                'Excel Mastery Batch 1',
    status:              'completed',
    start_date:          '2025-10-01',
    end_date:            '2025-12-01',
    max_students:        35,
    current_enrollment:  33,
    schedule_day:        'Wed/Fri',
    schedule_time:       '19:00',
    whatsapp_link:       null,
    price_per_student:   35000,
    description:         null,
    created_at:          '2025-09-15',
    students: [],
    sessions: [],
    completion_rate: 87,  // 87% completion — qualifies for instructor bonus
  },
]

const MOCK_BATCH_STUDENTS = {
  'batch-001': [
    { id: 'stu-001', name: 'Ada Okonkwo',    email: 'ada@gmail.com',   phone: '+234 801 111 2222', type: 'adult', status: 'active',  enrolled_at: '2026-02-10', paid_amount: 65000, grade: 78, attendance_rate: 100, rank: 3  },
    { id: 'stu-002', name: 'Emeka Nwosu',    email: 'emeka@yahoo.com', phone: '+234 802 333 4444', type: 'adult', status: 'active',  enrolled_at: '2026-02-11', paid_amount: 65000, grade: 91, attendance_rate: 95,  rank: 1  },
    { id: 'stu-003', name: 'Ngozi Eze',      email: 'ngozi@gmail.com', phone: '+234 803 555 6666', type: 'adult', status: 'active',  enrolled_at: '2026-02-12', paid_amount: 65000, grade: 85, attendance_rate: 90,  rank: 2  },
    { id: 'stu-004', name: 'Timi Adeleke',   email: 'bola@gmail.com',  phone: '+234 804 777 8888', type: 'child', status: 'active',  enrolled_at: '2026-02-13', paid_amount: 65000, grade: 72, attendance_rate: 80,  rank: 6  },
    { id: 'stu-005', name: 'Seun Afolabi',   email: 'seun@gmail.com',  phone: '+234 805 999 0000', type: 'adult', status: 'active',  enrolled_at: '2026-02-14', paid_amount: 65000, grade: 65, attendance_rate: 75,  rank: 9  },
    { id: 'stu-006', name: 'Chisom Okafor',  email: 'chisom@gmail.com',phone: '+234 806 111 2222', type: 'adult', status: 'active',  enrolled_at: '2026-02-15', paid_amount: 65000, grade: 88, attendance_rate: 100, rank: 2  },
  ],
}

export function useDashboardBatches() {

  const batches   = ref(MOCK_BATCHES)
  const isLoading = ref(false)
  const error     = ref(null)
  const search       = ref('')
  const filterStatus = ref('all') // 'all' | 'open' | 'active' | 'closed' | 'completed'
  const filterCourse = ref('all')

  const filteredBatches = computed(() => {
    let list = batches.value

    if (filterStatus.value !== 'all') {
      list = list.filter(b => b.status === filterStatus.value)
    }

    if (filterCourse.value !== 'all') {
      list = list.filter(b => b.course_id === filterCourse.value)
    }

    const q = search.value.trim().toLowerCase()
    if (q) {
      list = list.filter(b =>
        b.name.toLowerCase().includes(q) ||
        b.course_name.toLowerCase().includes(q) ||
        b.instructor_name.toLowerCase().includes(q)
      )
    }

    return list
  })

  const statusCounts = computed(() => ({
    all:       batches.value.length,
    open:      batches.value.filter(b => b.status === 'open').length,
    active:    batches.value.filter(b => b.status === 'active').length,
    closed:    batches.value.filter(b => b.status === 'closed').length,
    completed: batches.value.filter(b => b.status === 'completed').length,
  }))

  /** 0–100 integer */
  function enrollmentPct(batch) {
    if (!batch.max_students) return 0
    return Math.min(100, Math.round((batch.current_enrollment / batch.max_students) * 100))
  }

  function isFull(batch) {
    return batch.current_enrollment >= batch.max_students
  }

  /** Format 18:00 → 6:00 PM */
  function formatTime(time24) {
    if (!time24) return ''
    const [h, m] = time24.split(':').map(Number)
    const period = h >= 12 ? 'PM' : 'AM'
    const hour   = h === 0 ? 12 : h > 12 ? h - 12 : h
    return `${hour}:${String(m).padStart(2, '0')} ${period}`
  }

  /** ₦65,000 */
  function formatNaira(amount) {
    return '₦' + (amount ?? 0).toLocaleString('en-NG')
  }

  // ── Get batch by ID (with security sanitization) ───────────
  function getBatchById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return batches.value.find(b => b.id === safeId) ?? null
  }

  async function getBatchStudents(batchId) {
    const safeId = sanitizeId(batchId)
    if (!safeId) return []

    await new Promise(r => setTimeout(r, 300))
    return MOCK_BATCH_STUDENTS[safeId] ?? []
  }

  async function createBatch(data) {
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 800))

      const newBatch = {
        id:                 'batch-' + Date.now(),
        tenant_id:          'tenant-001',
        current_enrollment: 0,
        students:           [],
        sessions:           [],
        completion_rate:    null,
        created_at:         new Date().toISOString().split('T')[0],
        ...data,
      }
      batches.value.unshift(newBatch)
      return { success: true, batch: newBatch }
    } catch (err) {
        console.log(err);
        
      error.value = 'Failed to create batch'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function updateBatch(id, data) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 600))
      /**
       * TODO (Laravel 12):
       * router.put(route('dashboard.batches.update', safeId), data, {
       *   preserveScroll: true,
       * })
       */
      const idx = batches.value.findIndex(b => b.id === safeId)
      if (idx !== -1) {
        batches.value[idx] = { ...batches.value[idx], ...data }
      }
      return { success: true }
    } catch (err) {
        console.log(err);
        
      error.value = 'Failed to update batch'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function deleteBatch(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 500))
      /**
       * TODO (Laravel 12):
       * router.delete(route('dashboard.batches.destroy', safeId), {
       *   preserveScroll: true,
       * })
       */
      batches.value = batches.value.filter(b => b.id !== safeId)
      return { success: true }
    } catch (err) {
        console.log(err);
        
      error.value = 'Failed to delete batch'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function toggleEnrollment(id, open) {
    const safeId = sanitizeId(id)
    if (!safeId) return
    const newStatus = open ? 'open' : 'closed'
    return updateBatch(safeId, { status: newStatus })
  }

  return {
    // State
    batches,
    isLoading,
    error,
    search,
    filterStatus,
    filterCourse,
    // Computed
    filteredBatches,
    statusCounts,
    // Helpers
    enrollmentPct,
    isFull,
    formatTime,
    formatNaira,
    // Methods
    getBatchById,
    getBatchStudents,
    createBatch,
    updateBatch,
    deleteBatch,
    toggleEnrollment,
  }
}