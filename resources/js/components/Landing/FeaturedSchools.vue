<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ArrowRight, MapPin, Star, Users, GraduationCap } from 'lucide-vue-next'

const ArrowRightIcon = ArrowRight
const MapPinIcon = MapPin
const StarIcon = Star
const UsersIcon = Users
const GraduationCapIcon = GraduationCap

defineProps({
  schools: {
    type: Array,
    default: () => []
  }
})

const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const visitSchool = (school) => {
  // Visit the school's landing page
  // Assuming school has a subdomain or slug
  if (school.tenant?.subdomain) {
    window.location.href = `https://${school.tenant.subdomain}`
  } else {
    router.visit(`/marketplace/${school.id}`)
  }
}
</script>

<template>
  <section class="py-20 md:py-32 gradient-surface">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-8">
        <div class="max-w-2xl">
          <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
            Discover Schools
          </span>
          <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-4">
            Featured
            <span class="text-gradient-secondary">Schools</span>
          </h2>
          <p class="text-lg text-muted-foreground">
            Browse our top-rated schools and courses across Africa
          </p>
        </div>
        <Link href="/marketplace">
          <button class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground">
            Browse All Schools
            <ArrowRightIcon class="w-4 h-4" />
          </button>
        </Link>
      </div>

      <!-- Schools Grid -->
      <div v-if="schools.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div
          v-for="school in schools"
          :key="school.id"
          class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-secondary/30 transition-all duration-300 cursor-pointer"
          @click="visitSchool(school)"
        >
          <!-- Image -->
          <div class="relative h-48 overflow-hidden">
            <img
              :src="school.logo || school.banner_image || '/images/school-placeholder.jpg'"
              :alt="school.title"
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-foreground/60 to-transparent" />
            <div class="absolute bottom-4 left-4 right-4">
              <span class="inline-block px-3 py-1 rounded-full bg-secondary/90 text-secondary-foreground text-xs font-medium capitalize">
                {{ school.category }}
              </span>
            </div>
            <div v-if="school.is_featured" class="absolute top-4 right-4">
              <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary text-primary-foreground text-xs font-medium">
                <StarIcon class="w-3 h-3" />
                Featured
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6">
            <h3 class="font-display font-bold text-xl text-foreground mb-2 group-hover:text-secondary transition-colors">
              {{ school.title }}
            </h3>
            
            <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
              {{ school.description }}
            </p>

            <div class="flex items-center gap-4 text-sm text-muted-foreground mb-4">
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-4 h-4" />
                {{ school.location }}, {{ school.country }}
              </div>
              <div v-if="school.rating" class="flex items-center gap-1">
                <StarIcon class="w-4 h-4 text-primary fill-primary" />
                {{ school.rating }}
              </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-border">
              <div class="flex items-center gap-1 text-sm text-muted-foreground">
                <UsersIcon class="w-4 h-4" />
                {{ formatNumber(school.total_students) }} students
              </div>
              <span v-if="school.featured_courses && school.featured_courses.length" class="text-sm font-medium text-foreground">
                {{ school.featured_courses.length }} courses
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
  </section>
</template>