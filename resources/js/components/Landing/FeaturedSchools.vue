<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight, MapPin, Star, Users, GraduationCap } from 'lucide-vue-next'
import SchoolModal from './SchoolModal.vue'
import { computed, ref } from 'vue'

const ArrowRightIcon = ArrowRight
const MapPinIcon = MapPin
const StarIcon = Star
const UsersIcon = Users
const GraduationCapIcon = GraduationCap

// defineProps({
//   schools: {
//     type: Array,
//     default: () => []
//   }
// })

const categories = [
  "All",
  "Primary Education",
  "Web Development",
  "Business & Finance",
  "Languages",
  "STEM",
  "Arts & Design",
];

const schools = [
  {
    name: "Bright Stars Academy",
    category: "Primary Education",
    location: "Lagos, Nigeria",
    students: 450,
    rating: 4.9,
    courses: 24,
    image: "https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop",
    description: "Bright Stars Academy is a premier primary education institution dedicated to nurturing young minds through innovative teaching methods and a supportive learning environment.",
    instructors: 15,
    established: "2019",
  },
  {
    name: "Tech Academy Nigeria",
    category: "Web Development",
    location: "Abuja, Nigeria",
    students: 1200,
    rating: 4.8,
    courses: 18,
    image: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=250&fit=crop",
    description: "Tech Academy Nigeria is the leading coding bootcamp in West Africa, transforming beginners into job-ready developers with hands-on project-based learning.",
    instructors: 22,
    established: "2018",
  },
  {
    name: "Excel Learning Hub",
    category: "Business & Finance",
    location: "Accra, Ghana",
    students: 680,
    rating: 4.7,
    courses: 32,
    image: "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=250&fit=crop",
    description: "Excel Learning Hub empowers entrepreneurs and professionals with practical business skills, from financial literacy to advanced management strategies.",
    instructors: 18,
    established: "2020",
  },
  {
    name: "Lingua Africa",
    category: "Languages",
    location: "Nairobi, Kenya",
    students: 890,
    rating: 4.9,
    courses: 15,
    image: "https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=400&h=250&fit=crop",
    description: "Lingua Africa offers immersive language courses in African and international languages, connecting cultures through communication.",
    instructors: 25,
    established: "2017",
  },
  {
    name: "STEM Excellence Hub",
    category: "STEM",
    location: "Lagos, Nigeria",
    students: 560,
    rating: 4.8,
    courses: 28,
    image: "https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400&h=250&fit=crop",
    description: "STEM Excellence Hub prepares the next generation of African scientists and engineers with cutting-edge curriculum and hands-on laboratory experiences.",
    instructors: 20,
    established: "2019",
  },
  {
    name: "Creative Arts Academy",
    category: "Arts & Design",
    location: "Cape Town, SA",
    students: 340,
    rating: 4.6,
    courses: 21,
    image: "https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&h=250&fit=crop",
    description: "Creative Arts Academy nurtures artistic talent across graphic design, digital art, photography, and traditional fine arts with industry-experienced instructors.",
    instructors: 14,
    established: "2021",
  },
];

const activeFilter = ref("All")
const selectedSchool = ref(null)

const filteredSchools = computed(() => {
  return activeFilter.value === "All" 
      ? schools 
      : schools.filter(school => school.category === activeFilter.value)
})

</script>

<template>
  <section class="py-20 md:py-32 gradient-surface bg-background">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-8">
        <div class="max-w-2xl">
          <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
            Discover Schools
          </span>
          <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-4">
            Explore Our 
            <span class="text-secondary">Marketplace</span>
          </h2>
          <p class="text-lg text-muted-foreground">
            Browse thousands of schools and courses. Find the perfect learning 
            experience for you or your children.
          </p>
        </div>
        <Link href="/marketplace">
          <button class="inline-flex items-center gap-2 px-4 py-4 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground hover:cursor-pointer">
            Browse All Schools
            <ArrowRightIcon class="w-4 h-4" />
          </button>
        </Link>
      </div>

      <!-- category Filters -->
      <div class="flex flex-wrap gap-2 mb-10">
        <button
          v-for="category in categories"
          :key="category"
          @click="activeFilter = category"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 hover:cursor-pointer',
            activeFilter === category
              ? 'bg-primary text-primary-foreground shadow-md'
              : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
          ]"
        >
          {{ category }}
        </button>
      </div>

      <!-- Schools Grid -->
      <div v-if="schools.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div
          v-for="school in filteredSchools"
          :key="school.name"
          @click="selectedSchool = school"
          class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-secondary/30 transition-all duration-300 cursor-pointer"
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
              <span class="inline-block px-3 py-1 rounded-full bg-secondary text-white text-xs font-medium">
                {{ school.category }}
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6">
            <h3 class="font-display font-bold text-xl text-foreground mb-2 group-hover:text-secondary transition-colors">
              {{ school.name }}
            </h3>
            
            <div class="flex items-center gap-4 text-sm text-muted-foreground mb-4">
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-4 h-4"/>
                {{ school.location }}
              </div>
              <div class="flex items-center gap-1">
                <StarIcon class="w-4 h-4 text-yellow-500 fill-yellow-500" viewBox="0 0 24 24"/>
                {{ school.rating }}
              </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-border">
              <div class="flex items-center gap-1 text-sm text-muted-foreground">
                <UsersIcon class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" />
                {{ school.students.toLocaleString() }} students
              </div>
              <span class="text-sm font-medium text-foreground">
                {{ school.courses }} courses
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
          <GraduationCapIcon class="w-8 h-8 text-muted-foreground" />
        </div>
        <p class="text-muted-foreground text-lg mb-4">
          No featured schools available at the moment
        </p>
        <Link href="/marketplace">
          <button class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90">
            Explore All Schools
            <ArrowRightIcon class="w-4 h-4" />
          </button>
        </Link>
      </div>
      
    </div>

    <SchoolModal
      :is-open="!!selectedSchool"
      :school="selectedSchool"
      @close="selectedSchool = null"
    />
  </section>
</template>