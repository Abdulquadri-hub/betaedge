import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage, router } from '@inertiajs/vue3'

/**
 * useCourses
 * ─────────────────────────────────────────────────────────────
 * Manages course catalog for the school.
 *
 * Laravel Integration:
 *   const { courses } = usePage().props
 *   router.post('/dashboard/courses', data)
 */

const MOCK_COURSES = [
  {
    id: 'course-001',
    title: 'Full Stack Web Development',
    slug: 'full-stack-web-dev',
    description: 'Master HTML, CSS, JavaScript, Vue 3, and Laravel from scratch.',
    thumbnail: null,
    price: 65000,        // in Naira
    category: 'Technology',
    level: 'beginner',   // 'beginner' | 'intermediate' | 'advanced'
    status: 'published', // 'draft' | 'published' | 'archived'
    totalBatches: 3,
    activeBatches: 1,
    totalStudents: 87,
    rating: 4.8,
    duration: '3 months',
    createdAt: '2025-06-01',
    updatedAt: '2026-01-10',
  },
  {
    id: 'course-002',
    title: 'Data Science & Analytics',
    slug: 'data-science-analytics',
    description: 'Python, Pandas, NumPy, data visualization and machine learning basics.',
    thumbnail: null,
    price: 75000,
    category: 'Technology',
    level: 'intermediate',
    status: 'published',
    totalBatches: 1,
    activeBatches: 1,
    totalStudents: 25,
    rating: 4.9,
    duration: '3 months',
    createdAt: '2025-09-01',
    updatedAt: '2026-01-05',
  },
  {
    id: 'course-003',
    title: 'UI/UX Design',
    slug: 'ui-ux-design',
    description: 'Figma, design systems, user research, and prototyping.',
    thumbnail: null,
    price: 55000,
    category: 'Design',
    level: 'beginner',
    status: 'published',
    totalBatches: 2,
    activeBatches: 1,
    totalStudents: 41,
    rating: 4.7,
    duration: '2 months',
    createdAt: '2025-08-01',
    updatedAt: '2025-12-20',
  },
  {
    id: 'course-004',
    title: 'Microsoft Excel Mastery',
    slug: 'excel-mastery',
    description: 'From basics to advanced formulas, pivot tables, and dashboards.',
    thumbnail: null,
    price: 35000,
    category: 'Productivity',
    level: 'beginner',
    status: 'published',
    totalBatches: 1,
    activeBatches: 0,
    totalStudents: 33,
    rating: 4.6,
    duration: '2 months',
    createdAt: '2025-07-15',
    updatedAt: '2025-12-01',
  },
  {
    id: 'course-005',
    title: 'Digital Marketing Pro',
    slug: 'digital-marketing-pro',
    description: 'SEO, social media, email campaigns and analytics.',
    thumbnail: null,
    price: 45000,
    category: 'Marketing',
    level: 'intermediate',
    status: 'draft',
    totalBatches: 0,
    activeBatches: 0,
    totalStudents: 0,
    rating: null,
    duration: '6 weeks',
    createdAt: '2026-02-01',
    updatedAt: '2026-02-25',
  },
]

export function useCourses() {
  const courses   = ref(MOCK_COURSES)
  const isLoading = ref(false)
  const search    = ref('')
  const filterStatus = ref('all')

  const filteredCourses = computed(() => {
    let list = courses.value
    if (filterStatus.value !== 'all') {
      list = list.filter(c => c.status === filterStatus.value)
    }
    if (search.value.trim()) {
      const q = search.value.toLowerCase()
      list = list.filter(c =>
        c.title.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q)
      )
    }
    return list
  })

  const publishedCourses = computed(() =>
    courses.value.filter(c => c.status === 'published')
  )

  /** Format price as ₦65,000 */
  function formatPrice(price) {
    return '₦' + price.toLocaleString('en-NG')
  }

  async function createCourse(data) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 800))
    // TODO (Laravel): router.post('/dashboard/courses', data)
    const course = {
      id: 'course-' + Date.now(),
      totalBatches: 0, activeBatches: 0, totalStudents: 0, rating: null,
      status: 'draft',
      createdAt: new Date().toISOString().split('T')[0],
      updatedAt: new Date().toISOString().split('T')[0],
      ...data,
    }
    courses.value.unshift(course)
    isLoading.value = false
    return course
  }

  async function updateCourse(id, data) {
    isLoading.value = true
    await new Promise(r => setTimeout(r, 600))
    // TODO (Laravel): router.put(`/dashboard/courses/${id}`, data)
    const idx = courses.value.findIndex(c => c.id === id)
    if (idx !== -1) courses.value[idx] = { ...courses.value[idx], ...data, updatedAt: new Date().toISOString().split('T')[0] }
    isLoading.value = false
  }

  function getCourseById(id) {
    return courses.value.find(c => c.id === id) ?? null
  }

  return {
    courses,
    filteredCourses,
    publishedCourses,
    isLoading,
    search,
    filterStatus,
    formatPrice,
    createCourse,
    updateCourse,
    getCourseById,
  }
}