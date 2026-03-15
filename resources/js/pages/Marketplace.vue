<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    Search, Star, Users, MapPin, GraduationCap,
    ChevronLeft, ChevronRight, ArrowLeft,
    Building2, UserCheck, SlidersHorizontal,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import SchoolModal from '@/components/Landing/SchoolModal.vue'
import TutorCard from '@/components/Marketplace/TutorCard.vue'
import TutorModal from '@/components/Marketplace/TutorModal.vue'
import Footer from '@/components/Landing/Footer.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
// TODO: replace with defineProps({ schools, tutors, appName })

defineProps({
    name: { type: String, default: 'BetaEdge' },
})

// ── Constants ─────────────────────────────────────────────────────────────────
const ITEMS_PER_PAGE = 6

const CATEGORIES = [
    'All Categories', 'Primary Education', 'Secondary Education',
    'Web Development', 'Mobile Development', 'Data Science',
    'Business & Finance', 'Languages', 'STEM', 'Arts & Design', 'Health & Wellness',
]

const LOCATIONS = [
    'All Locations', 'Lagos, Nigeria', 'Abuja, Nigeria', 'Accra, Ghana',
    'Nairobi, Kenya', 'Cape Town, SA', 'Johannesburg, SA',
]

const SUBJECTS = [
    'All Subjects', 'Mathematics', 'English', 'Physics', 'Chemistry',
    'Web Development', 'Data Science', 'UI/UX Design', 'Python',
]

// const LEVELS = [
//     'All Levels', 'Primary', 'Secondary (JSS)', 'Secondary (SS)',
//     'University', 'Adult / Professional',
// ]

// ── Mock school data (same as original Marketplace.vue) ────────────────────
const allSchools = ref([
    { name: 'Bright Stars Academy', category: 'Primary Education', location: 'Lagos, Nigeria', students: 450, rating: 4.9, courses: 24, image: 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop', instructors: 15, established: '2019' },
    { name: 'Tech Academy Nigeria', category: 'Web Development', location: 'Abuja, Nigeria', students: 1200, rating: 4.8, courses: 18, image: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=250&fit=crop', instructors: 22, established: '2018' },
    { name: 'Excel Learning Hub', category: 'Business & Finance', location: 'Accra, Ghana', students: 680, rating: 4.7, courses: 32, image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=250&fit=crop', instructors: 18, established: '2020' },
    { name: 'Lingua Africa', category: 'Languages', location: 'Nairobi, Kenya', students: 890, rating: 4.9, courses: 15, image: 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=400&h=250&fit=crop', instructors: 25, established: '2017' },
    { name: 'STEM Excellence Hub', category: 'STEM', location: 'Lagos, Nigeria', students: 560, rating: 4.8, courses: 28, image: 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400&h=250&fit=crop', instructors: 20, established: '2019' },
    { name: 'Creative Arts Academy', category: 'Arts & Design', location: 'Cape Town, SA', students: 340, rating: 4.6, courses: 21, image: 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&h=250&fit=crop', instructors: 14, established: '2021' },
    { name: 'Future Leaders Academy', category: 'Secondary Education', location: 'Lagos, Nigeria', students: 920, rating: 4.9, courses: 45, image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=400&h=250&fit=crop', instructors: 35, established: '2016' },
    { name: 'Data Insights Africa', category: 'Data Science', location: 'Johannesburg, SA', students: 520, rating: 4.8, courses: 22, image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop', instructors: 19, established: '2019' },
])

// ── Mock tutor data ────────────────────────────────────────────────────────────
const allTutors = ref([
    {
        id: 'inst-001', name: 'Adebayo Johnson', avatar: null, location: 'Lagos, Nigeria',
        subjects: ['Web Development', 'JavaScript', 'Vue.js', 'Laravel'],
        levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
        rating: 4.8, total_reviews: 24, years_experience: '5-10', teaching_mode: 'online',
        batches_completed: 7, students_taught: 142, verification_status: 'verified',
        bio: 'Experienced full-stack developer and educator. I specialise in practical, project-based learning.',
        availability: [
            { day: 'Monday', from: '18:00', to: '21:00' },
            { day: 'Wednesday', from: '18:00', to: '21:00' },
            { day: 'Saturday', from: '10:00', to: '14:00' },
        ],
    },
    {
        id: 'inst-002', name: 'Kemi Adebayo', avatar: null, location: 'Abuja, Nigeria',
        subjects: ['Data Science', 'Python', 'Machine Learning', 'Statistics'],
        levels: ['University Level', 'Adult / Professional'],
        rating: 4.9, total_reviews: 18, years_experience: '3-5', teaching_mode: 'hybrid',
        batches_completed: 4, students_taught: 68, verification_status: 'verified',
        bio: 'Data scientist with a passion for teaching. I make data concepts accessible to everyone.',
        availability: [
            { day: 'Tuesday', from: '17:00', to: '20:00' },
            { day: 'Thursday', from: '17:00', to: '20:00' },
            { day: 'Saturday', from: '09:00', to: '13:00' },
        ],
    },
    {
        id: 'inst-003', name: 'Tayo Bello', avatar: null, location: 'Lagos, Nigeria',
        subjects: ['UI/UX Design', 'Figma', 'Product Design', 'Adobe XD'],
        levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
        rating: 4.7, total_reviews: 31, years_experience: '5-10', teaching_mode: 'online',
        batches_completed: 9, students_taught: 198, verification_status: 'verified',
        bio: 'Senior UX designer with agency experience. I focus on design thinking and real-world projects.',
        availability: [
            { day: 'Monday', from: '19:00', to: '21:00' },
            { day: 'Friday', from: '17:00', to: '21:00' },
            { day: 'Sunday', from: '14:00', to: '18:00' },
        ],
    },
    {
        id: 'inst-004', name: 'Ada Johnson', avatar: null, location: 'Port Harcourt, Nigeria',
        subjects: ['Mathematics', 'Further Mathematics', 'Physics'],
        levels: ['JSS 1–3', 'SS 1–3'],
        rating: 4.9, total_reviews: 42, years_experience: '10+', teaching_mode: 'online',
        batches_completed: 14, students_taught: 320, verification_status: 'verified',
        bio: 'Mathematics teacher with over 10 years experience. WAEC and JAMB specialist.',
        availability: [
            { day: 'Monday', from: '15:00', to: '18:00' },
            { day: 'Wednesday', from: '15:00', to: '18:00' },
            { day: 'Friday', from: '15:00', to: '18:00' },
        ],
    },
    {
        id: 'inst-005', name: 'Emeka Okafor', avatar: null, location: 'Enugu, Nigeria',
        subjects: ['English Language', 'Literature', 'Creative Writing'],
        levels: ['Primary 4–6', 'JSS 1–3', 'SS 1–3'],
        rating: 4.6, total_reviews: 15, years_experience: '3-5', teaching_mode: 'online',
        batches_completed: 5, students_taught: 89, verification_status: 'verified',
        bio: 'English teacher and published author. I bring literature to life for every student.',
        availability: [
            { day: 'Tuesday', from: '16:00', to: '19:00' },
            { day: 'Saturday', from: '10:00', to: '14:00' },
        ],
    },
    {
        id: 'inst-006', name: 'Ngozi Eze', avatar: null, location: 'Lagos, Nigeria',
        subjects: ['Accounting', 'Economics', 'Business Studies', 'Financial Literacy'],
        levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
        rating: 4.8, total_reviews: 27, years_experience: '5-10', teaching_mode: 'hybrid',
        batches_completed: 8, students_taught: 156, verification_status: 'verified',
        bio: 'Chartered accountant turned educator. Practical financial skills for modern learners.',
        availability: [
            { day: 'Wednesday', from: '18:00', to: '21:00' },
            { day: 'Friday', from: '18:00', to: '21:00' },
            { day: 'Saturday', from: '09:00', to: '12:00' },
        ],
    },
])

// ── Active tab ────────────────────────────────────────────────────────────────
const activeTab = ref('schools') // 'schools' | 'tutors'

// ── Shared search/filter state ────────────────────────────────────────────────
const searchQuery = ref('')
const locationFilter = ref('All Locations')
const sortBy = ref('rating')
const currentPage = ref(1)

// School-specific filters
const categoryFilter = ref('All Categories')

// Tutor-specific filters
const subjectFilter = ref('All Subjects')
const levelFilter = ref('All Levels')

// ── Selected items for modals ──────────────────────────────────────────────────
const selectedSchool = ref(null)
const selectedTutor = ref(null)

// ── School filtering ──────────────────────────────────────────────────────────
const filteredSchools = computed(() => {
    let result = [...allSchools.value]
    const q = searchQuery.value.trim().toLowerCase()
    if (q) result = result.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.category.toLowerCase().includes(q) ||
        s.location.toLowerCase().includes(q)
    )
    if (categoryFilter.value !== 'All Categories') {
        result = result.filter(s => s.category === categoryFilter.value)
    }
    if (locationFilter.value !== 'All Locations') {
        result = result.filter(s => s.location === locationFilter.value)
    }
    switch (sortBy.value) {
        case 'rating': result.sort((a, b) => b.rating - a.rating); break
        case 'students': result.sort((a, b) => b.students - a.students); break
        case 'courses': result.sort((a, b) => b.courses - a.courses); break
        case 'name': result.sort((a, b) => a.name.localeCompare(b.name)); break
    }
    return result
})

// ── Tutor filtering ───────────────────────────────────────────────────────────
const filteredTutors = computed(() => {
    let result = [...allTutors.value]
    const q = searchQuery.value.trim().toLowerCase()
    if (q) result = result.filter(t =>
        t.name.toLowerCase().includes(q) ||
        t.subjects.some(s => s.toLowerCase().includes(q)) ||
        t.location.toLowerCase().includes(q)
    )
    if (locationFilter.value !== 'All Locations') {
        result = result.filter(t => t.location === locationFilter.value)
    }
    if (subjectFilter.value !== 'All Subjects') {
        result = result.filter(t => t.subjects.some(s => s.includes(subjectFilter.value)))
    }
    switch (sortBy.value) {
        case 'rating': result.sort((a, b) => b.rating - a.rating); break
        case 'students': result.sort((a, b) => b.students_taught - a.students_taught); break
        case 'name': result.sort((a, b) => a.name.localeCompare(b.name)); break
    }
    return result
})

// ── Pagination ────────────────────────────────────────────────────────────────
const activeList = computed(() =>
    activeTab.value === 'schools' ? filteredSchools.value : filteredTutors.value
)

const totalPages = computed(() =>
    Math.ceil(activeList.value.length / ITEMS_PER_PAGE)
)

const paginatedItems = computed(() =>
    activeList.value.slice(
        (currentPage.value - 1) * ITEMS_PER_PAGE,
        currentPage.value * ITEMS_PER_PAGE,
    )
)

// ── Handlers ──────────────────────────────────────────────────────────────────
function switchTab(tab) {
    
    activeTab.value = tab
    currentPage.value = 1
    searchQuery.value = ''
}

function handlePageChange(page) {
    currentPage.value = page
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function resetFilters() {
    searchQuery.value = ''
    categoryFilter.value = 'All Categories'
    subjectFilter.value = 'All Subjects'
    levelFilter.value = 'All Levels'
    locationFilter.value = 'All Locations'
    sortBy.value = 'rating'
    currentPage.value = 1
}
</script>

<template>
    <div>

        <Head :title="`Marketplace — ${name}`" />

        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-lg border-b border-border">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between h-16 md:h-20">
                    <Link href="/" class="flex items-center gap-2 group">
                        <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shadow-md">
                            <GraduationCap class="w-6 h-6 text-primary-foreground" />
                        </div>
                        <span class="font-bold text-foreground text-lg">{{ name }}</span>
                    </Link>
                    <Link href="/">
                        <Button variant="ghost" size="sm" class="gap-2">
                            <ArrowLeft class="w-4 h-4" />Back to Home
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16 min-h-screen bg-background">
            <div class="container mx-auto px-4">

                <!-- Page header -->
                <div class="mb-8">
                    <h1 class="font-bold text-3xl md:text-4xl lg:text-5xl text-foreground mb-3">
                        Explore the
                        <span class="text-secondary">Marketplace</span>
                    </h1>
                    <p class="text-lg text-muted-foreground max-w-2xl">
                        Discover verified schools and expert tutors across Africa. All listings are reviewed and
                        approved by the Teach team.
                    </p>
                </div>

                <!-- Tab switcher -->
                <div class="flex items-center gap-2 mb-6">
                    <button class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all"
                        :class="activeTab === 'schools'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'bg-card border border-border text-muted-foreground hover:text-foreground hover:border-primary/30'"
                        @click="switchTab('schools')">
                        <Building2 class="h-4 w-4" />
                        Schools
                        <Badge variant="secondary" class="text-[10px] h-4 px-1.5 ml-1">{{ allSchools.length }}</Badge>
                    </button>
                    <button class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all"
                        :class="activeTab === 'tutors'
                            ? 'bg-secondary text-secondary-foreground shadow-sm'
                            : 'bg-card border border-border text-muted-foreground hover:text-foreground hover:border-secondary/30'"
                        @click="switchTab('tutors')">
                        <UserCheck class="h-4 w-4" />
                        Tutors
                        <Badge variant="secondary" class="text-[10px] h-4 px-1.5 ml-1">{{ allTutors.length }}</Badge>
                    </button>
                </div>

                <!-- Filters & Search -->
                <div class="bg-card border border-border rounded-2xl p-4 md:p-6 mb-8">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                            <input v-model="searchQuery" type="text" :placeholder="activeTab === 'schools'
                                ? 'Search schools, categories, locations...'
                                : 'Search tutors, subjects, locations...'"
                                class="w-full pl-10 h-12 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                @input="currentPage = 1" />
                        </div>

                        <!-- Dynamic filters -->
                        <div class="flex flex-wrap gap-3">
                            <!-- School filters -->
                            <template v-if="activeTab === 'schools'">
                                <select v-model="categoryFilter"
                                    class="h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary"
                                    @change="currentPage = 1">
                                    <option v-for="cat in CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </template>

                            <!-- Tutor filters -->
                            <template v-else>
                                <select v-model="subjectFilter"
                                    class="h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-secondary"
                                    @change="currentPage = 1">
                                    <option v-for="s in SUBJECTS" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </template>

                            <!-- Shared filters -->
                            <select v-model="locationFilter"
                                class="h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary"
                                @change="currentPage = 1">
                                <option v-for="loc in LOCATIONS" :key="loc" :value="loc">{{ loc }}</option>
                            </select>

                            <select v-model="sortBy"
                                class="h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="rating">Highest Rated</option>
                                <option value="students">Most Students</option>
                                <option v-if="activeTab === 'schools'" value="courses">Most Courses</option>
                                <option value="name">Name (A–Z)</option>
                            </select>

                            <button
                                class="h-12 px-5 rounded-lg border-2 border-primary text-primary bg-background hover:bg-primary hover:text-primary-foreground transition-colors font-medium text-sm"
                                @click="resetFilters">
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Showing
                            <span class="font-medium text-foreground">{{ paginatedItems.length }}</span>
                            of
                            <span class="font-medium text-foreground">{{ activeList.length }}</span>
                            {{ activeTab === 'schools' ? 'schools' : 'tutors' }}
                        </p>
                        <Badge variant="outline" class="text-xs gap-1">
                            <SlidersHorizontal class="h-3 w-3" />All verified
                        </Badge>
                    </div>
                </div>

                <!-- ── SCHOOLS GRID ── -->
                <div v-if="activeTab === 'schools'">
                    <div v-if="paginatedItems.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                        <div v-for="school in paginatedItems" :key="school.name"
                            class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-primary/30 transition-all duration-300 cursor-pointer"
                            @click="selectedSchool = school">
                            <div class="relative h-48 overflow-hidden">
                                <img :src="school.image" :alt="school.name"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                                <div class="absolute bottom-4 left-4 right-4">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full bg-secondary text-secondary-foreground text-xs font-medium">
                                        {{ school.category }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3
                                    class="font-bold text-xl text-foreground mb-2 group-hover:text-primary transition-colors">
                                    {{
                                    school.name }}</h3>
                                <div class="flex items-center gap-4 text-sm text-muted-foreground mb-4">
                                    <div class="flex items-center gap-1">
                                        <MapPin class="w-4 h-4" />{{ school.location }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Star class="w-4 h-4 text-amber-400 fill-amber-400" />{{ school.rating }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t border-border">
                                    <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <Users class="w-4 h-4" />{{ school.students.toLocaleString() }} students
                                    </div>
                                    <span class="text-sm font-medium text-foreground">{{ school.courses }}
                                        courses</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TUTORS GRID ── -->
                <div v-else>
                    <div v-if="paginatedItems.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                        <TutorCard v-for="tutor in paginatedItems" :key="tutor.id" :tutor="tutor"
                            @click="selectedTutor = $event" />
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="paginatedItems.length === 0"
                    class="text-center py-20 bg-card border border-border rounded-2xl">
                    <component :is="activeTab === 'schools' ? Building2 : UserCheck"
                        class="h-12 w-12 text-muted-foreground/30 mx-auto mb-3" />
                    <p class="text-xl text-muted-foreground mb-4">
                        No {{ activeTab === 'schools' ? 'schools' : 'tutors' }} found
                    </p>
                    <button
                        class="px-6 py-3 rounded-lg bg-primary text-primary-foreground font-semibold hover:opacity-90 transition-opacity"
                        @click="resetFilters">
                        Clear Filters
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-4">
                    <button :disabled="currentPage === 1" class="p-2 rounded-lg border transition-colors" :class="currentPage === 1
                        ? 'border-border bg-muted text-muted-foreground cursor-not-allowed'
                        : 'border-border bg-background hover:bg-muted'" @click="handlePageChange(currentPage - 1)">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button v-for="page in totalPages" :key="page"
                        class="w-10 h-10 rounded-lg font-medium transition-all" :class="currentPage === page
                            ? 'bg-primary text-primary-foreground'
                            : 'border border-border bg-background hover:bg-muted'" @click="handlePageChange(page)">
                        {{ page }}
                    </button>
                    <button :disabled="currentPage === totalPages" class="p-2 rounded-lg border transition-colors"
                        :class="currentPage === totalPages
                            ? 'border-border bg-muted text-muted-foreground cursor-not-allowed'
                            : 'border-border bg-background hover:bg-muted'" @click="handlePageChange(currentPage + 1)">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

            </div>
        </main>

        <Footer />

        <!-- Modals -->
        <SchoolModal :is-open="!!selectedSchool" :school="selectedSchool" @close="selectedSchool = null" />
        <TutorModal :is-open="!!selectedTutor" :tutor="selectedTutor" @close="selectedTutor = null" />
    </div>
</template>

<style scoped></style>