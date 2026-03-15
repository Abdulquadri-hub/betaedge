<script setup>
/**
 * TutorCard.vue
 * Single tutor card displayed in the marketplace tutor grid.
 * Mirrors the school card pattern in the existing Marketplace.vue.
 *
 * Props:
 *   tutor — {
 *     id, name, avatar, location, subjects: string[],
 *     levels: string[], rating, total_reviews,
 *     years_experience, teaching_mode,
 *     batches_completed, students_taught,
 *     verification_status: 'verified'
 *   }
 *
 * Emits:
 *   click(tutor) — parent opens profile or detail view
 */
import { computed } from 'vue'
import { MapPin, Star, Users, BookOpen, GraduationCap } from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import VerificationBadge from '../Verification/Verificationbadge.vue'

const props = defineProps({
  tutor: {
    type: Object,
    required: true,
  },
})

defineEmits(['click'])

function initials(name) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const teachingModeLabel = {
  'online':    'Online',
  'in-person': 'In-person',
  'hybrid':    'Online & In-person',
}

const filledStars = computed(() => Math.round(props.tutor.rating))
</script>

<template>
  <div
    class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-secondary/30 transition-all duration-300 cursor-pointer"
    @click="$emit('click', tutor)"
  >
    <!-- Top strip with avatar -->
    <div class="relative h-20 bg-gradient-to-br from-secondary/20 via-primary/10 to-background flex items-end px-5 pb-0">
      <Avatar class="h-16 w-16 ring-4 ring-card translate-y-8 shrink-0">
        <AvatarImage v-if="tutor.avatar" :src="tutor.avatar" :alt="tutor.name" />
        <AvatarFallback class="text-lg font-black bg-secondary/15 text-secondary">
          {{ initials(tutor.name) }}
        </AvatarFallback>
      </Avatar>
    </div>

    <!-- Content -->
    <div class="pt-10 px-5 pb-5 space-y-3">

      <!-- Name + badge -->
      <div class="space-y-1">
        <div class="flex items-start justify-between gap-2">
          <h3 class="font-bold text-lg text-foreground group-hover:text-secondary transition-colors leading-tight">
            {{ tutor.name }}
          </h3>
          <VerificationBadge :status="tutor.verification_status" size="sm" :show-label="false" />
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
          <MapPin class="h-3 w-3 shrink-0" />{{ tutor.location }}
          <span class="mx-1">·</span>
          <span>{{ teachingModeLabel[tutor.teaching_mode] }}</span>
        </div>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-1.5">
        <div class="flex items-center gap-0.5">
          <Star
            v-for="i in 5" :key="i"
            class="h-3.5 w-3.5"
            :class="i <= filledStars
              ? 'text-amber-400 fill-amber-400'
              : 'text-muted-foreground/20'"
          />
        </div>
        <span class="text-xs font-bold text-foreground">{{ tutor.rating }}</span>
        <span class="text-xs text-muted-foreground">({{ tutor.total_reviews }})</span>
      </div>

      <!-- Subjects (truncated) -->
      <div class="flex flex-wrap gap-1.5">
        <Badge
          v-for="subject in tutor.subjects.slice(0, 3)"
          :key="subject"
          variant="secondary"
          class="text-[10px] px-2 py-0.5"
        >
          {{ subject }}
        </Badge>
        <Badge
          v-if="tutor.subjects.length > 3"
          variant="outline"
          class="text-[10px] px-2 py-0.5 text-muted-foreground"
        >
          +{{ tutor.subjects.length - 3 }} more
        </Badge>
      </div>

      <!-- Stats row -->
      <div class="flex items-center justify-between pt-2 border-t border-border text-xs text-muted-foreground">
        <div class="flex items-center gap-1">
          <GraduationCap class="h-3.5 w-3.5" />
          {{ tutor.batches_completed }} batches
        </div>
        <div class="flex items-center gap-1">
          <Users class="h-3.5 w-3.5" />
          {{ tutor.students_taught }} students
        </div>
        <div class="flex items-center gap-1">
          <BookOpen class="h-3.5 w-3.5" />
          {{ tutor.levels.length }} level{{ tutor.levels.length !== 1 ? 's' : '' }}
        </div>
      </div>

    </div>
  </div>
</template>