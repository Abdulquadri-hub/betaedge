import { ref, computed } from 'vue'

// ─── Security ─────────────────────────────────────────────────────────────────
function sanitizeId(id) {
  if (!id || typeof id !== 'string') return null
  const clean = id.trim().slice(0, 100)
  return /^[a-zA-Z0-9_-]+$/.test(clean) ? clean : null
}

// ─── PAYMENT STRUCTURES (V3 spec: per_batch | per_student | monthly | custom) ─
export const PAYMENT_STRUCTURES = [
  { value: 'per_batch',   label: 'Fixed per Batch',   desc: 'Paid when batch completes' },
  { value: 'per_student', label: 'Fixed per Student',  desc: 'Based on enrollment count' },
  { value: 'monthly',     label: 'Monthly Salary',     desc: 'Fixed monthly payment' },
  { value: 'custom',      label: 'Custom Arrangement', desc: 'Describe terms manually' },
]

// ─── PERMISSIONS (what actions an instructor can perform) ─────────────────────
export const INSTRUCTOR_PERMISSIONS = [
  { value: 'upload_materials', label: 'Upload Materials' },
  { value: 'create_assignments', label: 'Create Assignments' },
  { value: 'grade_assignments',  label: 'Grade Assignments' },
  { value: 'take_attendance',    label: 'Mark Attendance' },
  { value: 'message_students',   label: 'Message Students' },
  { value: 'manage_sessions',    label: 'Manage Live Sessions' },
]

// ═════════════════════════════════════════════════════════════════════════════
// MOCK DATA
// ═════════════════════════════════════════════════════════════════════════════

const MOCK_STUDENTS = [
  {
    id: 'stu-001', tenant_id: 'tenant-001',
    name: 'Ada Okonkwo', email: 'ada@gmail.com', phone: '+234 801 111 2222',
    type: 'adult',  // adult | child
    status: 'active',  // active | inactive | suspended
    enrolled_courses: ['course-001'],
    current_batches:  ['batch-001'],
    total_paid: 65000, total_sessions_attended: 8, overall_grade: 78,
    enrolled_at: '2026-02-10',
    parent: null,
  },
  {
    id: 'stu-002', tenant_id: 'tenant-001',
    name: 'Emeka Nwosu', email: 'emeka@yahoo.com', phone: '+234 802 333 4444',
    type: 'adult', status: 'active',
    enrolled_courses: ['course-001', 'course-003'],
    current_batches:  ['batch-001'],
    total_paid: 120000, total_sessions_attended: 15, overall_grade: 91,
    enrolled_at: '2026-02-11',
    parent: null,
  },
  {
    id: 'stu-003', tenant_id: 'tenant-001',
    name: 'Chiamaka Eze', email: 'chiamaka@gmail.com', phone: '+234 803 555 6666',
    type: 'child', status: 'active',
    enrolled_courses: ['course-002'],
    current_batches:  ['batch-002'],
    total_paid: 75000, total_sessions_attended: 6, overall_grade: 85,
    enrolled_at: '2026-01-20',
    parent: {
      id: 'par-001', name: 'Mrs. Blessing Eze',
      email: 'blessing.eze@yahoo.com', phone: '+234 803 999 0000',
      relationship: 'Mother',
    },
  },
  {
    id: 'stu-004', tenant_id: 'tenant-001',
    name: 'Timi Adeleke', email: 'timi@gmail.com', phone: '+234 804 777 8888',
    type: 'child', status: 'active',
    enrolled_courses: ['course-001'],
    current_batches:  ['batch-001'],
    total_paid: 65000, total_sessions_attended: 5, overall_grade: 72,
    enrolled_at: '2026-02-13',
    parent: {
      id: 'par-002', name: 'Mr. Bola Adeleke',
      email: 'bola.adeleke@gmail.com', phone: '+234 804 000 1111',
      relationship: 'Father',
    },
  },
  {
    id: 'stu-005', tenant_id: 'tenant-001',
    name: 'Ibrahim Abdullahi', email: 'ibrahim@gmail.com', phone: '+234 805 222 3333',
    type: 'adult', status: 'suspended',
    enrolled_courses: ['course-001'],
    current_batches:  [],
    total_paid: 65000, total_sessions_attended: 2, overall_grade: 45,
    enrolled_at: '2026-02-14',
    parent: null,
  },
  {
    id: 'stu-006', tenant_id: 'tenant-001',
    name: 'Ngozi Okafor', email: 'ngozi@gmail.com', phone: '+234 806 444 5555',
    type: 'adult', status: 'active',
    enrolled_courses: ['course-003'],
    current_batches:  ['batch-003'],
    total_paid: 55000, total_sessions_attended: 4, overall_grade: 88,
    enrolled_at: '2026-02-15',
    parent: null,
  },
]

const MOCK_INSTRUCTORS = [
  {
    id: 'inst-001', tenant_id: 'tenant-001', user_id: 'user-inst-001',
    name: 'Mr. Chidi Okeke', email: 'chidi@gmail.com', phone: '+234 811 111 2222',
    avatar: null, bio: 'Senior software engineer with 8 years experience. Teaches Full Stack & Excel.',
    specialties: ['Full Stack', 'Excel', 'Python'],
    status: 'active',  // active | inactive | pending
    joined_at: '2025-06-15',
    // Teaching at multiple schools (V3 spec scenario 8)
    other_schools: ['Excel Academy', 'Tech Academy Nigeria'],
    // Batches at THIS tenant only
    assigned_batches: ['batch-001', 'batch-004'],
    active_batches_count: 1,
    completed_batches_count: 1,
    avg_rating: 4.8,
    avg_completion_rate: 88,
    // Payment agreement (InstructorPaymentAgreement table)
    payment: {
      structure:    'per_batch',  // per_batch | per_student | monthly | custom
      amount:       40000,        // ₦40,000 per batch
      additional_terms: 'Payment within 7 days of batch completion. Bonus ₦10,000 if completion > 85%.',
      paid_batches: 1,
      pending_batches: 1,
      total_earned: 50000,  // ₦40k + ₦10k bonus
    },
    permissions: ['upload_materials', 'create_assignments', 'grade_assignments', 'take_attendance', 'message_students', 'manage_sessions'],
  },
  {
    id: 'inst-002', tenant_id: 'tenant-001', user_id: 'user-inst-002',
    name: 'Ms. Kemi Adebayo', email: 'kemi@gmail.com', phone: '+234 812 333 4444',
    avatar: null, bio: 'Data scientist and analytics consultant. Python, R, and business intelligence expert.',
    specialties: ['Data Science', 'Analytics', 'Python', 'Machine Learning'],
    status: 'active',
    joined_at: '2025-09-01',
    other_schools: [],
    assigned_batches: ['batch-002'],
    active_batches_count: 1,
    completed_batches_count: 0,
    avg_rating: 4.9,
    avg_completion_rate: null,
    payment: {
      structure: 'per_student',
      amount:    2500,  // ₦2,500 per student
      additional_terms: 'Paid monthly based on enrolled students.',
      paid_batches: 0,
      pending_batches: 1,
      total_earned: 0,
    },
    permissions: ['upload_materials', 'grade_assignments', 'take_attendance', 'message_students'],
  },
  {
    id: 'inst-003', tenant_id: 'tenant-001', user_id: 'user-inst-003',
    name: 'Mr. Tayo Bello', email: 'tayo@gmail.com', phone: '+234 813 555 6666',
    avatar: null, bio: 'UI/UX designer with experience at leading Nigerian startups.',
    specialties: ['UI/UX', 'Figma', 'Product Design'],
    status: 'active',
    joined_at: '2025-08-10',
    other_schools: ['Design Hub Lagos'],
    assigned_batches: ['batch-003'],
    active_batches_count: 1,
    completed_batches_count: 2,
    avg_rating: 4.7,
    avg_completion_rate: 85,
    payment: {
      structure: 'monthly',
      amount:    80000,  // ₦80,000/month
      additional_terms: '',
      paid_batches: 2,
      pending_batches: 0,
      total_earned: 240000,  // 3 months
    },
    permissions: ['upload_materials', 'create_assignments', 'grade_assignments', 'take_attendance', 'message_students'],
  },
]

const MOCK_ENROLLMENT_REQUESTS = [
  {
    id: 'enr-req-001', tenant_id: 'tenant-001',
    student_name: 'Amaka Chibuike', student_email: 'amaka@gmail.com',
    student_type: 'child',
    parent_name: 'Mrs. Grace Chibuike', parent_email: 'grace@gmail.com',
    course_id: 'course-001', course_name: 'Full Stack Web Development',
    batch_id: 'batch-001', batch_name: 'Web Dev Batch 3',
    amount: 65000, status: 'pending',
    requested_at: new Date(Date.now() - 2 * 3600000).toISOString(),
    payment_reference: 'PAY-REF-001XYZ',
    payment_status: 'paid',  // paid | pending — only show manual-approval requests
    note: '',
  },
  {
    id: 'enr-req-002', tenant_id: 'tenant-001',
    student_name: 'Damilola Fagbemi', student_email: 'dami@yahoo.com',
    student_type: 'adult',
    parent_name: null, parent_email: null,
    course_id: 'course-002', course_name: 'Data Science & Analytics',
    batch_id: 'batch-002', batch_name: 'Data Science Batch 1',
    amount: 75000, status: 'pending',
    requested_at: new Date(Date.now() - 5 * 3600000).toISOString(),
    payment_reference: 'PAY-REF-002ABC',
    payment_status: 'paid',
    note: '',
  },
  {
    id: 'enr-req-003', tenant_id: 'tenant-001',
    student_name: 'Kehinde Afolabi', student_email: 'kehinde@gmail.com',
    student_type: 'adult',
    parent_name: null, parent_email: null,
    course_id: 'course-003', course_name: 'UI/UX Design Fundamentals',
    batch_id: 'batch-003', batch_name: 'UI/UX Batch 2',
    amount: 55000, status: 'pending',
    requested_at: new Date(Date.now() - 24 * 3600000).toISOString(),
    payment_reference: 'PAY-REF-003DEF',
    payment_status: 'pending',  // has not paid yet — on hold
    note: 'Student requested instalment payment plan',
  },
  {
    id: 'enr-req-004', tenant_id: 'tenant-001',
    student_name: 'Sola Adeyemi', student_email: 'sola@gmail.com',
    student_type: 'adult',
    parent_name: null, parent_email: null,
    course_id: 'course-001', course_name: 'Full Stack Web Development',
    batch_id: 'batch-001', batch_name: 'Web Dev Batch 3',
    amount: 65000, status: 'approved',
    requested_at: new Date(Date.now() - 3 * 86400000).toISOString(),
    payment_reference: 'PAY-REF-004GHI',
    payment_status: 'paid',
    note: '',
  },
]

// ═════════════════════════════════════════════════════════════════════════════
// STUDENTS COMPOSABLE
// ═════════════════════════════════════════════════════════════════════════════
export function useStudentsManager() {
  const students   = ref(MOCK_STUDENTS)
  const isLoading  = ref(false)
  const error      = ref(null)
  const search     = ref('')
  const filterStatus = ref('all')
  const filterType   = ref('all')

  const filteredStudents = computed(() => {
    let list = students.value
    if (filterStatus.value !== 'all') list = list.filter(s => s.status === filterStatus.value)
    if (filterType.value   !== 'all') list = list.filter(s => s.type   === filterType.value)
    const q = search.value.trim().toLowerCase()
    if (q) list = list.filter(s =>
      s.name.toLowerCase().includes(q) || s.email.toLowerCase().includes(q)
    )
    return list
  })

  const counts = computed(() => ({
    all:       students.value.length,
    active:    students.value.filter(s => s.status === 'active').length,
    suspended: students.value.filter(s => s.status === 'suspended').length,
    children:  students.value.filter(s => s.type === 'child').length,
  }))

  function getStudentById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return students.value.find(s => s.id === safeId) ?? null
  }

  function formatNaira(v) {
    return '₦' + (v ?? 0).toLocaleString('en-NG')
  }

  function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  async function suspendStudent(id, reason = '') {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.patch(route('dashboard.students.suspend', safeId), { reason })
    const idx = students.value.findIndex(s => s.id === safeId)
    if (idx !== -1) students.value[idx].status = 'suspended'
    return { success: true }
  }

  async function activateStudent(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.patch(route('dashboard.students.activate', safeId))
    const idx = students.value.findIndex(s => s.id === safeId)
    if (idx !== -1) students.value[idx].status = 'active'
    return { success: true }
  }

  return {
    students, filteredStudents, isLoading, error,
    search, filterStatus, filterType, counts,
    getStudentById, formatNaira, initials,
    suspendStudent, activateStudent,
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// INSTRUCTORS COMPOSABLE
// ═════════════════════════════════════════════════════════════════════════════
export function useInstructorsManager() {
  const instructors = ref(MOCK_INSTRUCTORS)
  const isLoading   = ref(false)
  const error       = ref(null)
  const search      = ref('')

  const filteredInstructors = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return instructors.value
    return instructors.value.filter(i =>
      i.name.toLowerCase().includes(q) ||
      i.email.toLowerCase().includes(q) ||
      i.specialties.some(s => s.toLowerCase().includes(q))
    )
  })

  function getInstructorById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return instructors.value.find(i => i.id === safeId) ?? null
  }

  function paymentLabel(structure) {
    return PAYMENT_STRUCTURES.find(p => p.value === structure)?.label ?? structure
  }

  function formatNaira(v) {
    return '₦' + (v ?? 0).toLocaleString('en-NG')
  }

  function formatPaymentLine(payment) {
    if (!payment) return '—'
    const struct = payment.structure
    if (struct === 'per_batch')   return `${formatNaira(payment.amount)} / batch`
    if (struct === 'per_student') return `${formatNaira(payment.amount)} / student`
    if (struct === 'monthly')     return `${formatNaira(payment.amount)} / month`
    return payment.additional_terms?.slice(0, 60) ?? 'Custom'
  }

  function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  async function inviteInstructor(data) {
    isLoading.value = true
    error.value     = null
    try {
      await new Promise(r => setTimeout(r, 900))
      /**
       * TODO (Laravel 12):
       * router.post(route('dashboard.instructors.invite'), data, {
       *   onSuccess: () => {},
       *   onError:   (e) => { error.value = e },
       *   preserveScroll: true,
       * })
       * Backend sends invitation email + creates TenantUser record with status='pending'
       */
      const newInst = {
        id:             'inst-' + Date.now(),
        tenant_id:      'tenant-001',
        user_id:        'user-' + Date.now(),
        name:           data.name || 'Pending Instructor',
        email:          data.email,
        phone:          data.phone ?? '',
        avatar:         null, bio: '',
        specialties:    [],
        status:         'pending',
        joined_at:      new Date().toISOString().split('T')[0],
        other_schools:  [],
        assigned_batches: data.batch_ids ?? [],
        active_batches_count: 0,
        completed_batches_count: 0,
        avg_rating: null, avg_completion_rate: null,
        payment: {
          structure:        data.payment_structure,
          amount:           data.payment_amount,
          additional_terms: data.payment_terms ?? '',
          paid_batches: 0, pending_batches: 0, total_earned: 0,
        },
        permissions: data.permissions ?? ['upload_materials', 'grade_assignments', 'take_attendance'],
      }
      instructors.value.unshift(newInst)
      return { success: true }
    } catch {
      error.value = 'Failed to send invitation'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function updateInstructor(id, data) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.put(route('dashboard.instructors.update', safeId), data)
    const idx = instructors.value.findIndex(i => i.id === safeId)
    if (idx !== -1) instructors.value[idx] = { ...instructors.value[idx], ...data }
    return { success: true }
  }

  async function removeInstructor(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.delete(route('dashboard.instructors.destroy', safeId))
    instructors.value = instructors.value.filter(i => i.id !== safeId)
    return { success: true }
  }

  async function markInstructorPaid(id, batchId) {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    // TODO (Laravel 12): router.patch(route('dashboard.instructors.markPaid', safeId), { batch_id: batchId })
    const inst = instructors.value.find(i => i.id === safeId)
    if (inst) {
      inst.payment.paid_batches++
      if (inst.payment.pending_batches > 0) inst.payment.pending_batches--
      inst.payment.total_earned += inst.payment.amount
    }
    return { success: true }
  }

  return {
    instructors, filteredInstructors, isLoading, error, search,
    getInstructorById, paymentLabel, formatNaira, formatPaymentLine, initials,
    inviteInstructor, updateInstructor, removeInstructor, markInstructorPaid,
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// PARENTS MOCK DATA
// ═════════════════════════════════════════════════════════════════════════════
/**
 * DB tables used:
 *   users (user_type='parent'), parents, student_parent (pivot)
 *   enrollment_route on students: 'parent_payment' | 'adult_direct'
 *
 * Laravel 12 routes:
 *   GET  /dashboard/parents               → dashboard.parents.index
 *   GET  /dashboard/parents/{parent}      → dashboard.parents.show
 *   POST /dashboard/parents/{parent}/message → dashboard.parents.message
 *
 * Inertia props for parents.index:
 *   parents: Array<{ id, user_id, name, email, phone, relationship_to_children,
 *             children: Array<student>, joined_at }>
 */
const MOCK_PARENTS = [
  {
    id: 'par-001',
    user_id: 'user-par-001',
    tenant_id: 'tenant-001',
    name: 'Mrs. Blessing Eze',
    email: 'blessing.eze@yahoo.com',
    phone: '+234 803 999 0000',
    whatsapp: '+234 803 999 0000',
    joined_at: '2026-01-20',
    children: [
      {
        id: 'stu-003',
        name: 'Chiamaka Eze',
        relationship: 'Mother',
        dob: '2014-03-15',
        age: 11,
        email: 'chiamaka@gmail.com',
        phone: '+234 803 555 6666',
        overall_grade: 85,
        attendance_rate: 92,
        status: 'active',
        enrollments: [
          {
            course: 'Primary 4 Mathematics', batch: 'Jan 2026 Batch',
            status: 'active', paid: 75000, enrolled_at: '2026-01-18',
            start_date: '2026-01-20', end_date: '2026-04-14',
            schedule: 'Fridays 4:00 PM',
          },
        ],
        recent_grades: [
          { title: 'Week 8 Assignment', score: 87, out_of: 100, date: '2026-03-05' },
          { title: 'Week 6 Quiz',       score: 42, out_of: 50,  date: '2026-02-20' },
          { title: 'Week 5 Assignment', score: 78, out_of: 100, date: '2026-02-12' },
        ],
        recent_attendance: [
          { session: 'Week 9 Session',  date: '2026-03-07', status: 'present' },
          { session: 'Week 8 Session',  date: '2026-02-28', status: 'present' },
          { session: 'Week 7 Session',  date: '2026-02-21', status: 'absent'  },
          { session: 'Week 6 Session',  date: '2026-02-14', status: 'present' },
        ],
        certificates: [],
      },
    ],
    message_history: [
      { from: 'parent', text: 'Good evening, how is Chiamaka performing compared to her classmates?', sent_at: '2026-03-06T18:00:00Z' },
      { from: 'school', text: 'Good evening Mrs. Eze! Chiamaka is performing well — she is currently in the top 5 of her batch. Her mathematics is strong, especially algebra. Attendance has been excellent.', sent_at: '2026-03-06T18:15:00Z' },
    ],
  },
  {
    id: 'par-002',
    user_id: 'user-par-002',
    tenant_id: 'tenant-001',
    name: 'Mr. Bola Adeleke',
    email: 'bola.adeleke@gmail.com',
    phone: '+234 804 000 1111',
    whatsapp: '+234 804 000 1111',
    joined_at: '2026-02-13',
    children: [
      {
        id: 'stu-004',
        name: 'Timi Adeleke',
        relationship: 'Father',
        dob: '2013-07-22',
        age: 12,
        email: 'timi@gmail.com',
        phone: '+234 804 777 8888',
        overall_grade: 72,
        attendance_rate: 75,
        status: 'active',
        enrollments: [
          {
            course: 'Full Stack Web Development', batch: 'Web Dev Batch 3',
            status: 'active', paid: 65000, enrolled_at: '2026-02-13',
            start_date: '2026-01-12', end_date: '2026-04-06',
            schedule: 'Mon/Wed 6:00 PM',
          },
        ],
        recent_grades: [
          { title: 'Week 8 – Laravel API', score: 68, out_of: 100, date: '2026-03-08' },
          { title: 'Week 6 Quiz',          score: 31, out_of: 50,  date: '2026-02-22' },
          { title: 'Week 5 Assignment',    score: 71, out_of: 100, date: '2026-02-14' },
        ],
        recent_attendance: [
          { session: 'Week 9 Session',  date: '2026-03-10', status: 'present' },
          { session: 'Week 8 – Mon',    date: '2026-03-02', status: 'absent'  },
          { session: 'Week 8 – Wed',    date: '2026-03-04', status: 'absent'  },
          { session: 'Week 7 – Mon',    date: '2026-02-24', status: 'present' },
        ],
        certificates: [],
      },
    ],
    message_history: [],
  },
  {
    id: 'par-003',
    user_id: 'user-par-003',
    tenant_id: 'tenant-001',
    name: 'Mrs. Amaka Obi',
    email: 'amaka.obi@gmail.com',
    phone: '+234 807 222 3333',
    whatsapp: '+234 807 222 3333',
    joined_at: '2026-01-10',
    children: [
      {
        id: 'stu-007',
        name: 'Chidi Obi',
        relationship: 'Mother',
        dob: '2012-11-05',
        age: 13,
        email: 'chidi.obi@gmail.com',
        phone: '',
        overall_grade: 91,
        attendance_rate: 97,
        status: 'active',
        enrollments: [
          {
            course: 'Data Science & Analytics', batch: 'Data Science Batch 1',
            status: 'active', paid: 80000, enrolled_at: '2026-01-10',
            start_date: '2026-01-20', end_date: '2026-04-14',
            schedule: 'Tues/Thurs 5:00 PM',
          },
        ],
        recent_grades: [
          { title: 'Week 8 Project',   score: 93, out_of: 100, date: '2026-03-07' },
          { title: 'Week 6 Quiz',      score: 47, out_of: 50,  date: '2026-02-21' },
          { title: 'Week 5 Assignment',score: 89, out_of: 100, date: '2026-02-13' },
        ],
        recent_attendance: [
          { session: 'Week 9 – Tues', date: '2026-03-10', status: 'present' },
          { session: 'Week 9 – Thurs',date: '2026-03-06', status: 'present' },
          { session: 'Week 8 – Tues', date: '2026-03-03', status: 'present' },
          { session: 'Week 8 – Thurs',date: '2026-02-27', status: 'present' },
        ],
        certificates: [
          { title: 'Excel Mastery', batch: 'Excel Batch 1', grade: 91, issued_at: '2025-11-28', certificate_id: 'BSA-EXCEL-B1-007' },
        ],
      },
      {
        id: 'stu-008',
        name: 'Kemi Obi',
        relationship: 'Mother',
        dob: '2015-04-18',
        age: 10,
        email: 'kemi.obi@gmail.com',
        phone: '',
        overall_grade: 88,
        attendance_rate: 94,
        status: 'active',
        enrollments: [
          {
            course: 'Primary 4 Mathematics', batch: 'Jan 2026 Batch',
            status: 'active', paid: 75000, enrolled_at: '2026-01-10',
            start_date: '2026-01-20', end_date: '2026-04-14',
            schedule: 'Fridays 4:00 PM',
          },
        ],
        recent_grades: [
          { title: 'Week 8 Assignment', score: 90, out_of: 100, date: '2026-03-06' },
          { title: 'Week 6 Quiz',       score: 44, out_of: 50,  date: '2026-02-20' },
        ],
        recent_attendance: [
          { session: 'Week 9 Session', date: '2026-03-07', status: 'present' },
          { session: 'Week 8 Session', date: '2026-02-28', status: 'present' },
          { session: 'Week 7 Session', date: '2026-02-21', status: 'present' },
        ],
        certificates: [],
      },
    ],
    message_history: [
      { from: 'parent', text: 'Hello, can I get an update on Chidi\'s project submission?', sent_at: '2026-03-08T10:30:00Z' },
      { from: 'school', text: 'Hi Mrs. Amaka! Chidi submitted excellently — scored 93/100. His Python logic was particularly impressive.', sent_at: '2026-03-08T11:00:00Z' },
    ],
  },
]

// ═════════════════════════════════════════════════════════════════════════════
// PARENTS COMPOSABLE
// ═════════════════════════════════════════════════════════════════════════════
export function useParents() {
  const parents   = ref(MOCK_PARENTS)
  const isLoading = ref(false)
  const error     = ref(null)
  const search    = ref('')

  // ── Derived ─────────────────────────────────────────────────────────────
  const filteredParents = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return parents.value
    return parents.value.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.email.toLowerCase().includes(q) ||
      p.children.some(c => c.name.toLowerCase().includes(q))
    )
  })

  const totalChildren  = computed(() => parents.value.reduce((s, p) => s + p.children.length, 0))
  const totalParents   = computed(() => parents.value.length)

  // ── Helpers ──────────────────────────────────────────────────────────────
  function getParentById(id) {
    const safeId = sanitizeId(id)
    if (!safeId) return null
    return parents.value.find(p => p.id === safeId) ?? null
  }

  function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
  }

  function formatNaira(v) {
    return '₦' + (v ?? 0).toLocaleString('en-NG')
  }

  function gradeColor(g) {
    if (g >= 80) return 'text-emerald-600'
    if (g >= 60) return 'text-amber-600'
    return 'text-destructive'
  }

  function attendanceColor(r) {
    if (r >= 80) return 'text-emerald-600'
    if (r >= 60) return 'text-amber-600'
    return 'text-destructive'
  }

  // ── Send message to parent ────────────────────────────────────────────────
  async function sendMessage(parentId, message) {
    const safeId = sanitizeId(parentId)
    if (!safeId || !message.trim()) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 600))
      /**
       * TODO (Laravel 12):
       * router.post(route('dashboard.parents.message', safeId), { message }, {
       *   preserveScroll: true,
       * })
       * Backend: sends email to parent, stores in messages table
       */
      const parent = parents.value.find(p => p.id === safeId)
      if (parent) {
        parent.message_history.push({
          from: 'school',
          text: message,
          sent_at: new Date().toISOString(),
        })
      }
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  // ── Toggle notification alerts for a parent ───────────────────────────────
  async function updateAlertThresholds(parentId, thresholds) {
    const safeId = sanitizeId(parentId)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 500))
      // TODO: router.put(route('dashboard.parents.thresholds', safeId), thresholds)
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  return {
    parents, filteredParents, isLoading, error, search,
    totalParents, totalChildren,
    getParentById, initials, fmtDate, formatNaira, gradeColor, attendanceColor,
    sendMessage, updateAlertThresholds,
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// ENROLLMENT REQUESTS COMPOSABLE
// ═════════════════════════════════════════════════════════════════════════════
export function useEnrollmentRequests() {
  const requests   = ref(MOCK_ENROLLMENT_REQUESTS)
  const isLoading  = ref(false)
  const error      = ref(null)
  const filterStatus = ref('pending')

  const filteredRequests = computed(() => {
    if (filterStatus.value === 'all') return requests.value
    return requests.value.filter(r => r.status === filterStatus.value)
  })

  const pendingCount = computed(() => requests.value.filter(r => r.status === 'pending').length)

  function formatNaira(v) {
    return '₦' + (v ?? 0).toLocaleString('en-NG')
  }

  function timeAgo(iso) {
    const diff = Date.now() - new Date(iso).getTime()
    const h    = Math.floor(diff / 3600000)
    const d    = Math.floor(diff / 86400000)
    if (h < 1) return 'Just now'
    if (h < 24) return `${h}h ago`
    return `${d}d ago`
  }

  async function approveRequest(id, note = '') {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 600))
      /**
       * TODO (Laravel 12):
       * router.patch(route('dashboard.enrollments.approve', safeId), { note }, {
       *   preserveScroll: true,
       * })
       * Backend: updates enrollment status, sends welcome email, updates batch current_enrollment
       */
      const idx = requests.value.findIndex(r => r.id === safeId)
      if (idx !== -1) requests.value[idx].status = 'approved'
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  async function rejectRequest(id, reason = '') {
    const safeId = sanitizeId(id)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 600))
      /**
       * TODO (Laravel 12):
       * router.patch(route('dashboard.enrollments.reject', safeId), { reason }, {
       *   preserveScroll: true,
       * })
       * Backend: updates status to 'rejected', initiates Paystack refund if paid
       */
      const idx = requests.value.findIndex(r => r.id === safeId)
      if (idx !== -1) requests.value[idx].status = 'rejected'
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  return {
    requests, filteredRequests, isLoading, error,
    filterStatus, pendingCount,
    formatNaira, timeAgo,
    approveRequest, rejectRequest,
  }
}