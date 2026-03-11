import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage, router } from '@inertiajs/vue3'

/**
 * useStudents
 * ─────────────────────────────────────────────────────────────
 * Manages student roster for the school.
 *
 * Laravel Integration:
 *   const { students } = usePage().props
 */

const MOCK_STUDENTS = [
  {
    id: 'stu-001',
    name: 'Ada Okonkwo',
    email: 'ada@gmail.com',
    phone: '+234 801 111 2222',
    avatar: null,
    type: 'adult',    // 'adult' | 'child'
    parentId: null,
    status: 'active', // 'active' | 'inactive' | 'suspended'
    enrolledCourses: ['course-001', 'course-003'],
    currentBatches: ['batch-001'],
    totalPaid: 120000,
    joinedAt: '2025-09-01',
    lastActive: '2026-02-27',
  },
  {
    id: 'stu-002',
    name: 'Emeka Nwosu',
    email: 'emeka.n@yahoo.com',
    phone: '+234 802 333 4444',
    avatar: null,
    type: 'adult',
    parentId: null,
    status: 'active',
    enrolledCourses: ['course-002'],
    currentBatches: ['batch-002'],
    totalPaid: 75000,
    joinedAt: '2026-01-15',
    lastActive: '2026-02-28',
  },
  {
    id: 'stu-003',
    name: 'Ngozi Eze',
    email: 'ngozi.eze@gmail.com',
    phone: '+234 803 555 6666',
    avatar: null,
    type: 'adult',
    parentId: null,
    status: 'active',
    enrolledCourses: ['course-003'],
    currentBatches: ['batch-003'],
    totalPaid: 55000,
    joinedAt: '2026-02-10',
    lastActive: '2026-02-26',
  },
  {
    id: 'stu-004',
    name: 'Timi Adeleke',
    email: 'bola.adeleke@gmail.com', // parent email
    phone: '+234 804 777 8888',
    avatar: null,
    type: 'child',
    parentId: 'parent-001',
    status: 'active',
    enrolledCourses: ['course-001'],
    currentBatches: ['batch-001'],
    totalPaid: 65000,
    joinedAt: '2026-01-20',
    lastActive: '2026-02-25',
  },
  {
    id: 'stu-005',
    name: 'Seun Afolabi',
    email: 'seun@hotmail.com',
    phone: '+234 805 999 0000',
    avatar: null,
    type: 'adult',
    parentId: null,
    status: 'inactive',
    enrolledCourses: ['course-004'],
    currentBatches: [],
    totalPaid: 35000,
    joinedAt: '2025-10-01',
    lastActive: '2025-12-15',
  },
]

export function useStudents() {
  const students  = ref(MOCK_STUDENTS)
  const isLoading = ref(false)
  const search    = ref('')
  const filterStatus = ref('all')

  const filteredStudents = computed(() => {
    let list = students.value
    if (filterStatus.value !== 'all') {
      list = list.filter(s => s.status === filterStatus.value)
    }
    if (search.value.trim()) {
      const q = search.value.toLowerCase()
      list = list.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.email.toLowerCase().includes(q) ||
        s.phone.includes(q)
      )
    }
    return list
  })

  const counts = computed(() => ({
    total:    students.value.length,
    active:   students.value.filter(s => s.status === 'active').length,
    inactive: students.value.filter(s => s.status === 'inactive').length,
    children: students.value.filter(s => s.type === 'child').length,
  }))

  /** ₦120,000 */
  function formatAmount(amount) {
    return '₦' + amount.toLocaleString('en-NG')
  }

  /** "TA" from "Tunde Adeyemi" */
  function initials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function getStudentById(id) {
    return students.value.find(s => s.id === id) ?? null
  }

  async function suspendStudent(id) {
    // TODO (Laravel): router.post(`/dashboard/students/${id}/suspend`)
    const s = students.value.find(s => s.id === id)
    if (s) s.status = 'suspended'
  }

  return {
    students,
    filteredStudents,
    isLoading,
    search,
    filterStatus,
    counts,
    formatAmount,
    initials,
    getStudentById,
    suspendStudent,
  }
}