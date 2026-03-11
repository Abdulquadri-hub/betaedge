import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage, router } from '@inertiajs/vue3'

/**
 * useInstructors
 * ─────────────────────────────────────────────────────────────
 * Manages instructors (tenant_users with role=instructor).
 *
 * Laravel Integration:
 *   const { instructors } = usePage().props
 *   Invite: router.post('/dashboard/instructors/invite', data)
 */

const MOCK_INSTRUCTORS = [
  {
    id: 'inst-001',
    userId: 'user-inst-001',
    name: 'Mr. Chidi Okeke',
    email: 'chidi.okeke@gmail.com',
    phone: '+234 811 111 2222',
    avatar: null,
    bio: 'Full-stack developer with 8 years experience. Teaches JavaScript, Vue, and Laravel.',
    specialties: ['JavaScript', 'Vue.js', 'Laravel', 'Excel'],
    status: 'active',      // 'active' | 'inactive' | 'invited'
    permissions: ['host_live_sessions', 'grade_assignments', 'upload_content', 'message_students'],
    paymentStructure: {
      structure: 'per_batch',  // 'per_batch' | 'per_student' | 'monthly' | 'custom'
      perBatchAmount: 25000,
    },
    activeBatches: 2,
    totalBatchesTaught: 6,
    totalStudentsTaught: 134,
    rating: 4.9,
    joinedAt: '2025-01-10',
    schools: ['tenant-001'], // supports multi-school instructors
  },
  {
    id: 'inst-002',
    userId: 'user-inst-002',
    name: 'Ms. Kemi Adebayo',
    email: 'kemi.adebayo@gmail.com',
    phone: '+234 812 333 4444',
    avatar: null,
    bio: 'Data scientist with 5 years of industry experience.',
    specialties: ['Python', 'Data Science', 'Machine Learning'],
    status: 'active',
    permissions: ['host_live_sessions', 'grade_assignments', 'view_analytics'],
    paymentStructure: {
      structure: 'monthly',
      monthlyAmount: 150000,
    },
    activeBatches: 1,
    totalBatchesTaught: 1,
    totalStudentsTaught: 25,
    rating: 4.9,
    joinedAt: '2025-12-01',
    schools: ['tenant-001'],
  },
  {
    id: 'inst-003',
    userId: 'user-inst-003',
    name: 'Mr. Tayo Bello',
    email: 'tayo.bello@gmail.com',
    phone: '+234 813 555 6666',
    avatar: null,
    bio: 'UI/UX designer with experience at top Nigerian tech startups.',
    specialties: ['Figma', 'Design Systems', 'User Research'],
    status: 'active',
    permissions: ['host_live_sessions', 'upload_content', 'message_students'],
    paymentStructure: {
      structure: 'per_student',
      perStudentAmount: 3000,
    },
    activeBatches: 1,
    totalBatchesTaught: 2,
    totalStudentsTaught: 41,
    rating: 4.7,
    joinedAt: '2025-07-20',
    schools: ['tenant-001'],
  },
]

// Labels
export const PAYMENT_LABELS = {
  per_batch:   'Per Batch',
  per_student: 'Per Student',
  monthly:     'Monthly Salary',
  custom:      'Custom',
}

export const PERMISSION_LABELS = {
  upload_content:       'Upload Content',
  host_live_sessions:   'Host Live Sessions',
  grade_assignments:    'Grade Assignments',
  manage_enrollments:   'Manage Enrollments',
  view_analytics:       'View Analytics',
  message_students:     'Message Students',
}

export function useInstructors() {
  const instructors = ref(MOCK_INSTRUCTORS)
  const isLoading   = ref(false)
  const search      = ref('')

  const filteredInstructors = computed(() => {
    if (!search.value.trim()) return instructors.value
    const q = search.value.toLowerCase()
    return instructors.value.filter(i =>
      i.name.toLowerCase().includes(q) ||
      i.email.toLowerCase().includes(q) ||
      i.specialties.some(s => s.toLowerCase().includes(q))
    )
  })

  /** For dropdowns — id + name only */
  const instructorOptions = computed(() =>
    instructors.value
      .filter(i => i.status === 'active')
      .map(i => ({ id: i.id, name: i.name }))
  )

  function initials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function formatPayment(paymentStructure) {
    const { structure, perBatchAmount, perStudentAmount, monthlyAmount, customAmount } = paymentStructure
    switch (structure) {
      case 'per_batch':   return `₦${(perBatchAmount ?? 0).toLocaleString('en-NG')} / batch`
      case 'per_student': return `₦${(perStudentAmount ?? 0).toLocaleString('en-NG')} / student`
      case 'monthly':     return `₦${(monthlyAmount ?? 0).toLocaleString('en-NG')} / month`
      default:            return `₦${(customAmount ?? 0).toLocaleString('en-NG')} (custom)`
    }
  }

  async function inviteInstructor(data) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 1000))
    // TODO (Laravel): router.post('/dashboard/instructors/invite', data)
    const newInstructor = {
      id: 'inst-' + Date.now(),
      userId: null,
      avatar: null,
      status: 'invited',
      activeBatches: 0,
      totalBatchesTaught: 0,
      totalStudentsTaught: 0,
      rating: null,
      joinedAt: new Date().toISOString().split('T')[0],
      schools: ['tenant-001'],
      ...data,
    }
    instructors.value.push(newInstructor)
    isLoading.value = false
    return newInstructor
  }

  async function updateInstructor(id, data) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 600))
    // TODO (Laravel): router.put(`/dashboard/instructors/${id}`, data)
    const idx = instructors.value.findIndex(i => i.id === id)
    if (idx !== -1) instructors.value[idx] = { ...instructors.value[idx], ...data }
    isLoading.value = false
  }

  function getInstructorById(id) {
    return instructors.value.find(i => i.id === id) ?? null
  }

  return {
    instructors,
    filteredInstructors,
    instructorOptions,
    isLoading,
    search,
    initials,
    formatPayment,
    inviteInstructor,
    updateInstructor,
    getInstructorById,
  }
}