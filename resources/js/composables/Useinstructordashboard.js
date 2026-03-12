
import { ref, computed } from 'vue'

function sanitizeId(id) {
  if (!id || typeof id !== 'string') return null
  const clean = id.trim().slice(0, 100)
  return /^[a-zA-Z0-9_-]+$/.test(clean) ? clean : null
}

export const MOCK_INSTRUCTOR_SCHOOLS = [
  {
    tenant_id: 'tenant-001', name: 'Bright Stars Academy',
    subdomain: 'brightstars', active_batches: 1,
    next_class: 'Friday, 6:00 PM', total_students: 24, active: true,
  },
  {
    tenant_id: 'tenant-002', name: 'Excel Academy',
    subdomain: 'excelacademy', active_batches: 1,
    next_class: 'Saturday, 10:00 AM', total_students: 18, active: false,
  },
  {
    tenant_id: 'tenant-003', name: 'Tech Academy Nigeria',
    subdomain: 'techacademy', active_batches: 1,
    next_class: 'Monday, 6:00 PM', total_students: 15, active: false,
  },
]

// ─── Mock: instructor's assigned batches at current school ────────────────────
export const MOCK_INSTRUCTOR_BATCHES = [
  {
    id: 'batch-001', course_id: 'course-001', tenant_id: 'tenant-001',
    name: 'Web Dev Batch 3', course_name: 'Full Stack Web Development',
    status: 'active', week: 9, total_weeks: 12,
    start_date: '2026-01-12', end_date: '2026-04-06',
    current_enrollment: 24, max_students: 30,
    schedule_day: 'Mon/Wed', schedule_time: '18:00',
    whatsapp_link: 'https://chat.whatsapp.com/webdev3',
    avg_grade: 74, attendance_rate: 87, completion_rate: null,
    pending_assignments: 3, pending_grading: 8,
    payment: { structure: 'per_batch', amount: 40000, status: 'pending' },
    next_session: { title: 'Week 9 – Vue 3 Composition API', date: new Date(Date.now() + 2*86400000).toISOString().split('T')[0], time: '18:00' },
  },
  {
    id: 'batch-004', course_id: 'course-004', tenant_id: 'tenant-001',
    name: 'Excel Mastery Batch 1', course_name: 'Microsoft Excel Mastery',
    status: 'completed', week: 8, total_weeks: 8,
    start_date: '2025-10-01', end_date: '2025-11-26',
    current_enrollment: 33, max_students: 35,
    schedule_day: 'Wed/Fri', schedule_time: '19:00',
    whatsapp_link: 'https://chat.whatsapp.com/excel1',
    avg_grade: 81, attendance_rate: 91, completion_rate: 87,
    pending_assignments: 0, pending_grading: 0,
    payment: { structure: 'per_batch', amount: 40000, status: 'paid' },
    next_session: null,
  },
]

// ─── Mock: students across instructor's batches ────────────────────────────────
export const MOCK_INSTRUCTOR_STUDENTS = [
  { id:'stu-001', name:'Ada Okonkwo',      email:'ada@gmail.com',    batch_id:'batch-001', batch_name:'Web Dev Batch 3',      grade:78, attendance_rate:88, status:'active', type:'adult', parent:null },
  { id:'stu-002', name:'Emeka Nwosu',      email:'emeka@yahoo.com',  batch_id:'batch-001', batch_name:'Web Dev Batch 3',      grade:91, attendance_rate:100, status:'active', type:'adult', parent:null },
  { id:'stu-003', name:'Chiamaka Eze',     email:'chiamaka@gmail.com', batch_id:'batch-001', batch_name:'Web Dev Batch 3',    grade:85, attendance_rate:95, status:'active', type:'child',
    parent:{ name:'Mrs. Blessing Eze', phone:'+234 803 999 0000', email:'blessing.eze@yahoo.com' } },
  { id:'stu-004', name:'Timi Adeleke',     email:'timi@gmail.com',   batch_id:'batch-001', batch_name:'Web Dev Batch 3',      grade:72, attendance_rate:75, status:'active', type:'child',
    parent:{ name:'Mr. Bola Adeleke', phone:'+234 804 000 1111', email:'bola.adeleke@gmail.com' } },
  { id:'stu-005', name:'Ibrahim Abdullahi',email:'ibrahim@gmail.com', batch_id:'batch-001', batch_name:'Web Dev Batch 3',     grade:45, attendance_rate:40, status:'struggling', type:'adult', parent:null },
  { id:'stu-006', name:'Ngozi Okafor',     email:'ngozi@gmail.com',  batch_id:'batch-004', batch_name:'Excel Mastery Batch 1', grade:88, attendance_rate:94, status:'active', type:'adult', parent:null },
]

// ─── Mock: pending grading assignments ────────────────────────────────────────
export const MOCK_GRADING_QUEUE = [
  {
    id:'sub-001', assignment_id:'asg-001',
    assignment_title:'Week 8 – Laravel API Project',
    batch_id:'batch-001', batch_name:'Web Dev Batch 3',
    course_name:'Full Stack Web Development',
    student_id:'stu-001', student_name:'Ada Okonkwo',
    submitted_at:'2026-03-07T20:45:00Z',
    file_url:'https://drive.google.com/ada-laravel-project',
    student_note:'I had trouble with the authentication middleware.',
    total_points:100, score:null, feedback:'', status:'submitted',
    due_date:'2026-03-08',
  },
  {
    id:'sub-002', assignment_id:'asg-001',
    assignment_title:'Week 8 – Laravel API Project',
    batch_id:'batch-001', batch_name:'Web Dev Batch 3',
    course_name:'Full Stack Web Development',
    student_id:'stu-002', student_name:'Emeka Nwosu',
    submitted_at:'2026-03-06T14:20:00Z',
    file_url:'https://drive.google.com/emeka-laravel-project',
    student_note:'',
    total_points:100, score:null, feedback:'', status:'submitted',
    due_date:'2026-03-08',
  },
  {
    id:'sub-003', assignment_id:'asg-001',
    assignment_title:'Week 8 – Laravel API Project',
    batch_id:'batch-001', batch_name:'Web Dev Batch 3',
    course_name:'Full Stack Web Development',
    student_id:'stu-003', student_name:'Chiamaka Eze',
    submitted_at:'2026-03-08T09:10:00Z',
    file_url:'https://drive.google.com/chiamaka-laravel-project',
    student_note:'I found question 7 difficult, please explain in class.',
    total_points:100, score:null, feedback:'', status:'submitted',
    due_date:'2026-03-08',
  },
  {
    id:'sub-004', assignment_id:'asg-002',
    assignment_title:'Week 7 – Vue 3 Quiz App',
    batch_id:'batch-001', batch_name:'Web Dev Batch 3',
    course_name:'Full Stack Web Development',
    student_id:'stu-004', student_name:'Timi Adeleke',
    submitted_at:'2026-03-01T11:30:00Z',
    file_url:'https://drive.google.com/timi-quiz-app',
    student_note:'',
    total_points:50, score:38, feedback:'Good work! The UI could be improved.', status:'graded',
    due_date:'2026-03-02',
  },
]

// ─── Mock: earnings across schools ────────────────────────────────────────────
export const MOCK_EARNINGS = [
  {
    school:'Bright Stars Academy', tenant_id:'tenant-001',
    payment_structure:'per_batch', amount:40000, currency:'NGN',
    batches_completed:1, batches_active:1, batches_pending_payment:1,
    total_earned:50000, // 40k + 10k bonus
    additional_terms:'Bonus ₦10,000 if completion > 85%',
    payment_history:[
      { batch:'Excel Mastery Batch 1', amount:50000, status:'paid', paid_at:'2025-12-01' },
    ],
  },
  {
    school:'Excel Academy', tenant_id:'tenant-002',
    payment_structure:'per_batch', amount:50000, currency:'NGN',
    batches_completed:0, batches_active:1, batches_pending_payment:0,
    total_earned:0,
    additional_terms:'',
    payment_history:[],
  },
  {
    school:'Tech Academy Nigeria', tenant_id:'tenant-003',
    payment_structure:'per_student', amount:1000, currency:'NGN',
    batches_completed:0, batches_active:1, batches_pending_payment:0,
    total_earned:15000,
    additional_terms:'Paid monthly based on enrollment.',
    payment_history:[
      { batch:'March 2025 Batch', amount:15000, status:'paid', paid_at:'2025-04-01' },
    ],
  },
]

// ─── Mock: job board listings ─────────────────────────────────────────────────
export const MOCK_JOB_LISTINGS = [
  {
    id:'job-001', school_name:'Future Leaders Academy', location:'Lagos',
    course:'Mathematics (JSS 1-3)', schedule:'Tuesdays & Thursdays, 5PM',
    payment_model:'per_batch', students_per_batch:25, payment_amount: '35,000',
    requirements:'B.Sc Mathematics or Education. 2+ years teaching experience.',
    posted_at: new Date(Date.now()-3*86400000).toISOString(),
    status:'open', applied:false,
  },
  {
    id:'job-002', school_name:'Greenfield Academy', location:'Abuja',
    course:'English Language (Primary 4-6)', schedule:'Saturdays, 9AM',
    payment_model:'per_batch', students_per_batch:20, payment_amount: '45,000',
    requirements:'B.Sc English or Education.',
    posted_at: new Date(Date.now()-7*86400000).toISOString(),
    status:'open', applied:false,
  },
  {
    id:'job-003', school_name:'CodeCraft Nigeria', location:'Lagos',
    course:'Web Development Bootcamp', schedule:'Mon/Wed/Fri, 6PM',
    payment_model:'per_student', students_per_batch:30, payment_amount: '15,000',
    requirements:'2+ years professional web dev. Portfolio required.',
    posted_at: new Date(Date.now()-1*86400000).toISOString(),
    status:'open', applied:true,
  },
]


export function useInstructorDashboard() {
  const currentSchool  = ref(MOCK_INSTRUCTOR_SCHOOLS.find(s => s.active) ?? MOCK_INSTRUCTOR_SCHOOLS[0])
  const schools        = ref(MOCK_INSTRUCTOR_SCHOOLS)
  const batches        = ref(MOCK_INSTRUCTOR_BATCHES)
  const students       = ref(MOCK_INSTRUCTOR_STUDENTS)
  const gradingQueue   = ref(MOCK_GRADING_QUEUE)
  const earnings       = ref(MOCK_EARNINGS)
  const jobListings    = ref(MOCK_JOB_LISTINGS)
  const isLoading      = ref(false)
  const error          = ref(null)


  const activeBatches    = computed(() => batches.value.filter(b => b.status === 'active'))
  const pendingGrading   = computed(() => gradingQueue.value.filter(s => s.status === 'submitted'))
  const strugglingStudents = computed(() => students.value.filter(s => s.grade < 60 || s.attendance_rate < 60))

  const totalEarned = computed(() =>
    earnings.value.reduce((sum, e) => sum + e.total_earned, 0)
  )

  // ── Helpers ───────────────────────────────────────────────────────────────
  function formatNaira(v) {
    if (v >= 1000000) return '₦' + (v/1000000).toFixed(1) + 'M'
    if (v >= 1000)    return '₦' + (v/1000).toFixed(0)    + 'K'
    return '₦' + (v ?? 0).toLocaleString('en-NG')
  }

  function formatTime(t) {
    if (!t) return ''
    const [h,m] = t.split(':').map(Number)
    const p = h >= 12 ? 'PM' : 'AM'
    const hr = h === 0 ? 12 : h > 12 ? h-12 : h
    return `${hr}:${String(m).padStart(2,'0')} ${p}`
  }

  function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG',{ day:'numeric', month:'short', year:'numeric' })
  }

  function timeAgo(iso) {
    const diff = Date.now() - new Date(iso).getTime()
    const h = Math.floor(diff/3600000)
    const d = Math.floor(diff/86400000)
    if (h < 1) return 'Just now'
    if (h < 24) return `${h}h ago`
    return `${d}d ago`
  }

  function initials(name) {
    return (name??'').split(' ').map(n=>n[0]).join('').toUpperCase().slice(0,2)
  }

  function gradeColor(g) {
    if (g >= 80) return 'text-emerald-600'
    if (g >= 60) return 'text-amber-600'
    return 'text-destructive'
  }

  // ── Switch school ─────────────────────────────────────────────────────────
  async function switchSchool(tenantId) {
    const safeId = sanitizeId(tenantId)
    if (!safeId) return
    /**
     * TODO (Laravel 12):
     * router.post(route('instructor.switchSchool', safeId), {}, {
     *   onSuccess: () => { window.location.reload() }, // full reload for tenant switch
     * })
     */
    schools.value.forEach(s => { s.active = s.tenant_id === safeId })
    currentSchool.value = schools.value.find(s => s.active) ?? schools.value[0]
  }

  // ── Grade submission ──────────────────────────────────────────────────────
  async function gradeSubmission(submissionId, score, feedback) {
    const safeId = sanitizeId(submissionId)
    if (!safeId) return { success: false }
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 700))
      /**
       * TODO (Laravel 12):
       * router.post(route('instructor.grading.grade', safeId), { score, feedback }, {
       *   preserveScroll: true,
       *   onSuccess: () => {},
       *   onError:   (e) => { error.value = e },
       * })
       */
      const idx = gradingQueue.value.findIndex(s => s.id === safeId)
      if (idx !== -1) {
        gradingQueue.value[idx].score    = score
        gradingQueue.value[idx].feedback = feedback
        gradingQueue.value[idx].status   = 'graded'
      }
      return { success: true }
    } catch {
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  // ── Apply to job ──────────────────────────────────────────────────────────
  async function applyToJob(jobId, message) {
    console.log(message);
    
    const safeId = sanitizeId(jobId)
    if (!safeId) return { success: false }
    await new Promise(r => setTimeout(r, 600))
    // TODO (Laravel 12): router.post(route('instructor.applications.apply', safeId), { message })
    const idx = jobListings.value.findIndex(j => j.id === safeId)
    if (idx !== -1) jobListings.value[idx].applied = true
    return { success: true }
  }

  // ── Update profile ────────────────────────────────────────────────────────
  async function updateProfile(data) {
    console.log(data);
    
    isLoading.value = true
    try {
      await new Promise(r => setTimeout(r, 700))
      /**
       * TODO (Laravel 12):
       * router.put(route('instructor.profile.update'), data, {
       *   preserveScroll: true,
       *   onSuccess: () => {},
       *   onError:   (e) => { error.value = e },
       * })
       */
      return { success: true }
    } finally {
      isLoading.value = false
    }
  }

  return {
    currentSchool, schools, batches, students, gradingQueue, earnings, jobListings,
    activeBatches, pendingGrading, strugglingStudents, totalEarned,
    isLoading, error,
    formatNaira, formatTime, fmtDate, timeAgo, initials, gradeColor,
    switchSchool, gradeSubmission, applyToJob, updateProfile,
  }
}