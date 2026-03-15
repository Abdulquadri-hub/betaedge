<script setup>
/**
 * FeaturedMarketplace.vue
 * Landing page section — Featured Schools & Tutors
 *
 * Two tabs:
 *   Schools — category-filtered grid of verified schools
 *   Tutors  — subject-filtered grid of verified tutors
 *
 * Both open their respective detail modals on click.
 *
 * Laravel 12: pass featured_schools and featured_tutors as Inertia shared props
 * from the PlatformController@landing method.
 */
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  ArrowRight, MapPin, Star, Users,
  Building2, UserCheck,
} from 'lucide-vue-next'
// import { Badge } from '@/components/ui/badge'
// import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import SchoolModal from '@/components/Landing/SchoolModal.vue'
import TutorCard from '../Marketplace/TutorCard.vue'
import TutorModal from '../Marketplace/TutorModal.vue'
// import VerificationBadge from '../Verification/Verificationbadge.vue'

// ── Props ─────────────────────────────────────────────────────────────────────
// TODO: replace mock data with defineProps({ featuredSchools, featuredTutors })

// ── Tab state ─────────────────────────────────────────────────────────────────
const activeTab = ref('schools')

// ── School data & filters ─────────────────────────────────────────────────────
const SCHOOL_CATEGORIES = [
  'All', 'Primary Education', 'Web Development',
  'Business & Finance', 'Languages', 'STEM', 'Arts & Design',
]

const schools = ref([
  {
    name: 'Bright Stars Academy', category: 'Primary Education',
    location: 'Lagos, Nigeria', students: 450, rating: 4.9, courses: 24,
    image: 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop',
    description: 'Bright Stars Academy is a premier primary education institution dedicated to nurturing young minds through innovative teaching methods and a supportive learning environment.',
    instructors: 15, established: '2019',
  },
  {
    name: 'Tech Academy Nigeria', category: 'Web Development',
    location: 'Abuja, Nigeria', students: 1200, rating: 4.8, courses: 18,
    image: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=250&fit=crop',
    description: 'Tech Academy Nigeria is the leading coding bootcamp in West Africa, transforming beginners into job-ready developers with hands-on project-based learning.',
    instructors: 22, established: '2018',
  },
  {
    name: 'Excel Learning Hub', category: 'Business & Finance',
    location: 'Accra, Ghana', students: 680, rating: 4.7, courses: 32,
    image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=250&fit=crop',
    description: 'Excel Learning Hub empowers entrepreneurs and professionals with practical business skills, from financial literacy to advanced management strategies.',
    instructors: 18, established: '2020',
  },
  {
    name: 'Lingua Africa', category: 'Languages',
    location: 'Nairobi, Kenya', students: 890, rating: 4.9, courses: 15,
    image: 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=400&h=250&fit=crop',
    description: 'Lingua Africa offers immersive language courses in African and international languages, connecting cultures through communication.',
    instructors: 25, established: '2017',
  },
  {
    name: 'STEM Excellence Hub', category: 'STEM',
    location: 'Lagos, Nigeria', students: 560, rating: 4.8, courses: 28,
    image: 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400&h=250&fit=crop',
    description: 'STEM Excellence Hub prepares the next generation of African scientists and engineers with cutting-edge curriculum and hands-on laboratory experiences.',
    instructors: 20, established: '2019',
  },
  {
    name: 'Creative Arts Academy', category: 'Arts & Design',
    location: 'Cape Town, SA', students: 340, rating: 4.6, courses: 21,
    image: 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&h=250&fit=crop',
    description: 'Creative Arts Academy nurtures artistic talent across graphic design, digital art, photography, and traditional fine arts with industry-experienced instructors.',
    instructors: 14, established: '2021',
  },
])

const schoolCategoryFilter = ref('All')

const filteredSchools = computed(() =>
  schoolCategoryFilter.value === 'All'
    ? schools.value
    : schools.value.filter(s => s.category === schoolCategoryFilter.value)
)

// ── Tutor data & filters ──────────────────────────────────────────────────────
const TUTOR_SUBJECTS = [
  'All', 'Web Development', 'Mathematics', 'Data Science',
  'UI/UX Design', 'English', 'Accounting',
]

const tutors = ref([
  {
    id: 'inst-001', name: 'Adebayo Johnson', avatar: null,
    location: 'Lagos, Nigeria',
    subjects: ['Web Development', 'JavaScript', 'Vue.js', 'Laravel'],
    levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
    rating: 4.8, total_reviews: 24, years_experience: '5-10',
    teaching_mode: 'online', batches_completed: 7, students_taught: 142,
    verification_status: 'verified',
    bio: 'Experienced full-stack developer and educator. I specialise in practical, project-based learning.',
    availability: [
      { day: 'Monday', from: '18:00', to: '21:00' },
      { day: 'Wednesday', from: '18:00', to: '21:00' },
      { day: 'Saturday', from: '10:00', to: '14:00' },
    ],
  },
  {
    id: 'inst-002', name: 'Kemi Adebayo', avatar: null,
    location: 'Abuja, Nigeria',
    subjects: ['Data Science', 'Python', 'Machine Learning', 'Statistics'],
    levels: ['University Level', 'Adult / Professional'],
    rating: 4.9, total_reviews: 18, years_experience: '3-5',
    teaching_mode: 'hybrid', batches_completed: 4, students_taught: 68,
    verification_status: 'verified',
    bio: 'Data scientist with a passion for teaching. I make data concepts accessible to everyone.',
    availability: [
      { day: 'Tuesday', from: '17:00', to: '20:00' },
      { day: 'Saturday', from: '09:00', to: '13:00' },
    ],
  },
  {
    id: 'inst-003', name: 'Tayo Bello', avatar: null,
    location: 'Lagos, Nigeria',
    subjects: ['UI/UX Design', 'Figma', 'Product Design'],
    levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
    rating: 4.7, total_reviews: 31, years_experience: '5-10',
    teaching_mode: 'online', batches_completed: 9, students_taught: 198,
    verification_status: 'verified',
    bio: 'Senior UX designer with agency experience. I focus on design thinking and real-world projects.',
    availability: [
      { day: 'Friday', from: '17:00', to: '21:00' },
      { day: 'Sunday', from: '14:00', to: '18:00' },
    ],
  },
  {
    id: 'inst-004', name: 'Ada Johnson', avatar: null,
    location: 'Port Harcourt, Nigeria',
    subjects: ['Mathematics', 'Further Mathematics', 'Physics'],
    levels: ['JSS 1–3', 'SS 1–3'],
    rating: 4.9, total_reviews: 42, years_experience: '10+',
    teaching_mode: 'online', batches_completed: 14, students_taught: 320,
    verification_status: 'verified',
    bio: 'Mathematics teacher with over 10 years experience. WAEC and JAMB specialist.',
    availability: [
      { day: 'Monday', from: '15:00', to: '18:00' },
      { day: 'Wednesday', from: '15:00', to: '18:00' },
    ],
  },
  {
    id: 'inst-005', name: 'Emeka Okafor', avatar: null,
    location: 'Enugu, Nigeria',
    subjects: ['English Language', 'Literature', 'Creative Writing'],
    levels: ['Primary 4–6', 'JSS 1–3', 'SS 1–3'],
    rating: 4.6, total_reviews: 15, years_experience: '3-5',
    teaching_mode: 'online', batches_completed: 5, students_taught: 89,
    verification_status: 'verified',
    bio: 'English teacher and published author. I bring literature to life for every student.',
    availability: [
      { day: 'Tuesday', from: '16:00', to: '19:00' },
      { day: 'Saturday', from: '10:00', to: '14:00' },
    ],
  },
  {
    id: 'inst-006', name: 'Ngozi Eze', avatar: null,
    location: 'Lagos, Nigeria',
    subjects: ['Accounting', 'Economics', 'Business Studies'],
    levels: ['SS 1–3', 'University Level', 'Adult / Professional'],
    rating: 4.8, total_reviews: 27, years_experience: '5-10',
    teaching_mode: 'hybrid', batches_completed: 8, students_taught: 156,
    verification_status: 'verified',
    bio: 'Chartered accountant turned educator. Practical financial skills for modern learners.',
    availability: [
      { day: 'Wednesday', from: '18:00', to: '21:00' },
      { day: 'Saturday', from: '09:00', to: '12:00' },
    ],
  },
])

const tutorSubjectFilter = ref('All')

const filteredTutors = computed(() =>
  tutorSubjectFilter.value === 'All'
    ? tutors.value
    : tutors.value.filter(t =>
        t.subjects.some(s => s.includes(tutorSubjectFilter.value))
      )
)

// ── Selected items for modals ─────────────────────────────────────────────────
const selectedSchool = ref(null)
const selectedTutor  = ref(null)

// ── Tab switch resets filter ──────────────────────────────────────────────────
function switchTab(tab) {
  activeTab.value          = tab
  schoolCategoryFilter.value = 'All'
  tutorSubjectFilter.value   = 'All'
}

// ── Helpers ───────────────────────────────────────────────────────────────────
// function initials(name) {
//   return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
// }
</script>

<template>
  <section class="py-20 md:py-32 bg-background">
    <div class="container mx-auto px-4">

      <!-- ── Section header ── -->
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
        <div class="max-w-2xl">
          <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
            Discover on Teach
          </span>
          <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-4">
            Explore Our
            <span class="text-secondary">Marketplace</span>
          </h2>
          <p class="text-lg text-muted-foreground">
            Browse verified schools and expert tutors across Africa.
            Every listing is reviewed and approved by the Teach team.
          </p>
        </div>
        <Link :href="activeTab === 'schools' ? '/marketplace' : '/marketplace?tab=tutors'">
          <button class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors font-medium text-sm whitespace-nowrap">
            Browse All {{ activeTab === 'schools' ? 'Schools' : 'Tutors' }}
            <ArrowRight class="w-4 h-4" />
          </button>
        </Link>
      </div>

      <!-- ── Tab switcher ── -->
      <div class="flex items-center gap-2 mb-8">
        <button
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all"
          :class="activeTab === 'schools'
            ? 'bg-primary text-primary-foreground shadow-sm'
            : 'bg-muted text-muted-foreground hover:text-foreground hover:bg-muted/80'"
          @click="switchTab('schools')"
        >
          <Building2 class="h-4 w-4" />
          Schools
          <span class="text-[10px] font-bold opacity-70 ml-0.5">{{ schools.length }}</span>
        </button>
        <button
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-all"
          :class="activeTab === 'tutors'
            ? 'bg-secondary text-secondary-foreground shadow-sm'
            : 'bg-muted text-muted-foreground hover:text-foreground hover:bg-muted/80'"
          @click="switchTab('tutors')"
        >
          <UserCheck class="h-4 w-4" />
          Tutors
          <span class="text-[10px] font-bold opacity-70 ml-0.5">{{ tutors.length }}</span>
        </button>
      </div>

      <!-- ══════════════════════════════════════════════════════ -->
      <!--  SCHOOLS TAB                                          -->
      <!-- ══════════════════════════════════════════════════════ -->
      <template v-if="activeTab === 'schools'">

        <!-- Category filters -->
        <div class="flex flex-wrap gap-2 mb-8">
          <button
            v-for="cat in SCHOOL_CATEGORIES"
            :key="cat"
            class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
            :class="schoolCategoryFilter === cat
              ? 'bg-primary text-primary-foreground shadow-md'
              : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'"
            @click="schoolCategoryFilter = cat"
          >
            {{ cat }}
          </button>
        </div>

        <!-- School grid -->
        <div
          v-if="filteredSchools.length > 0"
          class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"
        >
          <div
            v-for="school in filteredSchools"
            :key="school.name"
            class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-primary/30 transition-all duration-300 cursor-pointer"
            @click="selectedSchool = school"
          >
            <!-- Image -->
            <div class="relative h-48 overflow-hidden">
              <img
                :src="school.image"
                :alt="school.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
              <div class="absolute bottom-4 left-4 right-4">
                <span class="inline-block px-3 py-1 rounded-full bg-secondary text-secondary-foreground text-xs font-medium">
                  {{ school.category }}
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="p-6">
              <h3 class="font-display font-bold text-xl text-foreground mb-2 group-hover:text-primary transition-colors">
                {{ school.name }}
              </h3>
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
                <span class="text-sm font-medium text-foreground">{{ school.courses }} courses</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Schools empty state -->
        <div v-else class="text-center py-16 rounded-2xl border border-dashed border-border">
          <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
            <Building2 class="w-7 h-7 text-muted-foreground" />
          </div>
          <p class="text-muted-foreground text-base mb-4">No schools in this category yet</p>
          <button
            class="px-5 py-2 rounded-xl bg-primary text-primary-foreground text-sm font-medium hover:opacity-90 transition-opacity"
            @click="schoolCategoryFilter = 'All'"
          >
            Show all schools
          </button>
        </div>

      </template>

      <!-- ══════════════════════════════════════════════════════ -->
      <!--  TUTORS TAB                                           -->
      <!-- ══════════════════════════════════════════════════════ -->
      <template v-else>

        <!-- Subject filters -->
        <div class="flex flex-wrap gap-2 mb-8">
          <button
            v-for="subject in TUTOR_SUBJECTS"
            :key="subject"
            class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
            :class="tutorSubjectFilter === subject
              ? 'bg-secondary text-secondary-foreground shadow-md'
              : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'"
            @click="tutorSubjectFilter = subject"
          >
            {{ subject }}
          </button>
        </div>

        <!-- Tutor grid — reuses TutorCard component -->
        <div
          v-if="filteredTutors.length > 0"
          class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8"
        >
          <TutorCard
            v-for="tutor in filteredTutors"
            :key="tutor.id"
            :tutor="tutor"
            @click="selectedTutor = $event"
          />
        </div>

        <!-- Tutors empty state -->
        <div v-else class="text-center py-16 rounded-2xl border border-dashed border-border">
          <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
            <UserCheck class="w-7 h-7 text-muted-foreground" />
          </div>
          <p class="text-muted-foreground text-base mb-4">No tutors found for this subject</p>
          <button
            class="px-5 py-2 rounded-xl bg-secondary text-secondary-foreground text-sm font-medium hover:opacity-90 transition-opacity"
            @click="tutorSubjectFilter = 'All'"
          >
            Show all tutors
          </button>
        </div>

      </template>

      <!-- ── Browse all CTA strip ── -->
      <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-3 pt-4 border-t border-border/50">
        <p class="text-sm text-muted-foreground">
          Showing {{ activeTab === 'schools' ? filteredSchools.length : filteredTutors.length }} featured
          {{ activeTab === 'schools' ? 'schools' : 'tutors' }} —
          many more in the full marketplace.
        </p>
        <Link :href="activeTab === 'schools' ? '/marketplace' : '/marketplace?tab=tutors'">
          <button class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
            View all {{ activeTab === 'schools' ? 'schools' : 'tutors' }}
            <ArrowRight class="w-4 h-4" />
          </button>
        </Link>
      </div>

    </div>

    <!-- Modals -->
    <SchoolModal
      :is-open="!!selectedSchool"
      :school="selectedSchool"
      @close="selectedSchool = null"
    />
    <TutorModal
      :is-open="!!selectedTutor"
      :tutor="selectedTutor"
      @close="selectedTutor = null"
    />
  </section>
</template>

<style scoped></style>