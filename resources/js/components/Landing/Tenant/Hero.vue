<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  ArrowRight, 
  Users, 
  BookOpen, 
  Award,
} from 'lucide-vue-next'

const ArrowRightIcon = ArrowRight
const UsersIcon = Users
const BookOpenIcon = BookOpen
const AwardIcon = Award

defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    tenant: {
        type: Object,
        default: () => ({})
    },
    pageContent: {
        type: Object,
        default: () => ({})
    }
})

const formatNumber = (num) => {
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'k'
  }
  return num.toString()
}
</script>

<template>
      <section class="relative pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden">
      <!-- Background -->
      <div 
        class="absolute inset-0 opacity-10"
        :style="{ background: `linear-gradient(135deg, ${tenant.primary_color} 0%, ${tenant.secondary_color} 100%)` }"
      />
      
      <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
          <!-- School Logo (Large) -->
          <div v-if="tenant.logo" class="mb-8">
            <img 
              :src="tenant.logo" 
              :alt="tenant.name"
              class="w-24 h-24 rounded-2xl object-cover mx-auto shadow-lg"
            />
          </div>

          <!-- Headline -->
          <h1 class="font-display text-4xl md:text-5xl lg:text-6xl font-bold text-foreground leading-tight mb-6">
            {{ pageContent.hero?.heading || `Welcome to ${tenant.name}` }}
          </h1>

          <!-- Subheadline -->
          <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto mb-10">
            {{ pageContent.hero?.subheading || tenant.description }}
          </p>

          <!-- CTAs -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-12">
            <Link 
              :href="route('tenant.register.student', { tenant: tenant.slug || tenant.id})"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-lg text-white font-semibold text-lg shadow-lg hover:shadow-xl transition-all"
              :style="{ backgroundColor: tenant.primary_color }"
            >
              <span>Enroll as Student</span>
              <ArrowRightIcon class="w-5 h-5" />
            </Link>
            <Link 
              :href="route('tenant.register.parent', { tenant: tenant.slug || tenant.id})"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-lg border-2 font-semibold text-lg hover:bg-accent transition-all"
              :style="{ borderColor: tenant.primary_color, color: tenant.primary_color }"
            >
              Register as Parent
            </Link>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-3 gap-4 md:gap-8 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
              <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl" :style="{ backgroundColor: `${tenant.primary_color}20` }">
                <UsersIcon class="w-6 h-6" :style="{ color: tenant.primary_color }" />
              </div>
              <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                {{ formatNumber(stats.students) }}+
              </div>
              <div class="text-sm text-muted-foreground">
                Students
              </div>
            </div>
            <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
              <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl" :style="{ backgroundColor: `${tenant.secondary_color}20` }">
                <BookOpenIcon class="w-6 h-6" :style="{ color: tenant.secondary_color }" />
              </div>
              <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                {{ formatNumber(stats.courses) }}+
              </div>
              <div class="text-sm text-muted-foreground">
                Courses
              </div>
            </div>
            <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
              <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl" :style="{ backgroundColor: `${tenant.primary_color}20` }">
                <AwardIcon class="w-6 h-6" :style="{ color: tenant.primary_color }" />
              </div>
              <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                {{ formatNumber(stats.instructors) }}+
              </div>
              <div class="text-sm text-muted-foreground">
                Instructors
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

</template>