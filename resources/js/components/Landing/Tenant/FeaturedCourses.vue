<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  ArrowRight, 
  Clock,
} from 'lucide-vue-next'

const ArrowRightIcon = ArrowRight
const ClockIcon = Clock

defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    },
    featuredCourses: {
        type: Object,
        default: () => ({})
    }
})

const formatPrice = (price) => {
  if (!price || price === 0) return 'Free'
  return `₦${price.toLocaleString()}`
}
</script>

<template>
    <section id="courses" class="py-20 md:py-32 bg-background">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <span class="text-sm font-semibold uppercase tracking-wider mb-4 block" :style="{ color: tenant.primary_color }">
            Our Programs
          </span>
          <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
            Featured Courses
          </h2>
          <p class="text-muted-foreground max-w-2xl mx-auto">
            Explore our top-rated courses designed to help you achieve your learning goals
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="course in featuredCourses" 
            :key="course.id"
            class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-primary/30 transition-all"
          >
            <div class="relative h-48 overflow-hidden">
              <img 
                :src="course.thumbnail || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop'"
                :alt="course.title"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              />
              <div class="absolute top-4 right-4">
                <span class="px-3 py-1 rounded-full text-xs font-medium text-white" :style="{ backgroundColor: tenant.primary_color }">
                  {{ course.level }}
                </span>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-semibold text-lg text-foreground mb-2 line-clamp-2">
                {{ course.title }}
              </h3>
              <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
                {{ course.description }}
              </p>
              <div class="flex items-center justify-between pt-4 border-t border-border">
                <div class="flex items-center gap-2">
                  <ClockIcon class="w-4 h-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">
                    {{ course.duration_weeks }} weeks
                  </span>
                </div>
                <span class="text-lg font-bold" :style="{ color: tenant.primary_color }">
                  {{ formatPrice(course.price) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <button
            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg text-white font-semibold shadow-lg hover:shadow-xl transition-all"
            :style="{ backgroundColor: tenant.primary_color }"
          >
            View All Courses
            <ArrowRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </section>
</template>