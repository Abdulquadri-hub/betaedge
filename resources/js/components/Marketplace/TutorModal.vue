<script setup>
/**
 * TutorModal.vue
 * Marketplace detail dialog for a tutor — mirrors SchoolModal.vue pattern exactly.
 *
 * Props:
 *   isOpen — boolean
 *   tutor  — tutor object (same shape as TutorCard)
 *
 * Emits:
 *   close()
 */
import { computed } from 'vue'
import {
  Dialog, DialogContent, DialogClose, DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import { VisuallyHidden } from 'reka-ui'
import { Button } from '@/components/ui/button'
import { Badge  } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  X, Star, Users, MapPin, BookOpen, GraduationCap,
  Clock, Send, CheckCircle,
} from 'lucide-vue-next'
import VerificationBadge from '../Verification/Verificationbadge.vue'

 defineProps({
  isOpen: Boolean,
  tutor:  Object,
})

defineEmits(['close'])

function initials(name) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function formatTime(t) {
  const [h, m] = t.split(':').map(Number)
  const p  = h >= 12 ? 'PM' : 'AM'
  const hr = h === 0 ? 12 : h > 12 ? h - 12 : h
  return `${hr}:${String(m).padStart(2, '0')} ${p}`
}

const teachingModeLabel = {
  'online':    'Online only',
  'in-person': 'In-person only',
  'hybrid':    'Online & In-person',
}

const experienceLabel = {
  '0-1':  'Less than 1 year',
  '1-3':  '1–3 years',
  '3-5':  '3–5 years',
  '5-10': '5–10 years',
  '10+':  '10+ years',
}

const defaultHighlights = computed(() => [
  'Verified by Teach platform',
  'Live interactive classes',
  'Structured batch-based teaching',
  'Assignment grading & feedback',
  'Student progress tracking',
  'Available for multiple schools',
])
</script>

<template>
  <Dialog v-if="tutor" :open="isOpen" @update:open="val => !val && $emit('close')">
    <DialogContent class="max-w-2xl p-0 bg-card border-border overflow-hidden max-h-[90vh] overflow-y-auto">
      <VisuallyHidden>
        <DialogTitle>Tutor Profile</DialogTitle>
        <DialogDescription>
          View this tutor's subjects, availability, and teaching background.
        </DialogDescription>
      </VisuallyHidden>

      <DialogClose class="absolute right-4 top-4 z-10 rounded-full bg-background/80 p-2 hover:bg-background transition-colors">
        <X class="h-5 w-5 text-foreground" />
        <span class="sr-only">Close</span>
      </DialogClose>

      <!-- Header gradient -->
      <div class="relative h-36 bg-gradient-to-br from-secondary/30 via-primary/15 to-background flex items-end px-6 pb-0">
        <Avatar class="h-20 w-20 ring-4 ring-card translate-y-10 shrink-0">
          <AvatarImage v-if="tutor.avatar" :src="tutor.avatar" :alt="tutor.name" />
          <AvatarFallback class="text-2xl font-black bg-secondary/15 text-secondary">
            {{ initials(tutor.name) }}
          </AvatarFallback>
        </Avatar>
      </div>

      <!-- Content -->
      <div class="p-6 pt-14 space-y-6">

        <!-- Name + verification -->
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <h2 class="text-2xl font-black text-foreground">{{ tutor.name }}</h2>
            <div class="flex items-center gap-1.5 text-sm text-muted-foreground mt-1">
              <MapPin class="h-3.5 w-3.5" />{{ tutor.location }}
            </div>
          </div>
          <VerificationBadge :status="tutor.verification_status" size="md" />
        </div>

        <!-- Stats grid (mirrors SchoolModal) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-amber-500 mb-1">
              <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
              <span class="font-bold text-lg">{{ tutor.rating }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Rating</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-secondary mb-1">
              <Users class="h-4 w-4" />
              <span class="font-bold text-lg">{{ tutor.students_taught }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Students</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-primary mb-1">
              <GraduationCap class="h-4 w-4" />
              <span class="font-bold text-lg">{{ tutor.batches_completed }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Batches</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-foreground mb-1">
              <BookOpen class="h-4 w-4" />
              <span class="font-bold text-lg">{{ tutor.subjects.length }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Subjects</span>
          </div>
        </div>

        <!-- Location + experience -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
          <span class="flex items-center gap-1.5"><Clock class="h-4 w-4 text-primary" />{{ experienceLabel[tutor.years_experience] }} experience</span>
          <span class="mx-1">·</span>
          <span>{{ teachingModeLabel[tutor.teaching_mode] }}</span>
        </div>

        <!-- Bio -->
        <div>
          <h3 class="font-semibold text-foreground mb-2">About</h3>
          <p class="text-sm text-muted-foreground leading-relaxed">{{ tutor.bio }}</p>
        </div>

        <!-- Subjects -->
        <div>
          <h3 class="font-semibold text-foreground mb-2">Subjects</h3>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="subject in tutor.subjects" :key="subject" variant="secondary" class="text-xs">
              {{ subject }}
            </Badge>
          </div>
        </div>

        <!-- Levels -->
        <div>
          <h3 class="font-semibold text-foreground mb-2">Academic Levels</h3>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="level in tutor.levels" :key="level" variant="outline" class="text-xs">
              {{ level }}
            </Badge>
          </div>
        </div>

        <!-- Availability -->
        <div v-if="tutor.availability?.length">
          <h3 class="font-semibold text-foreground mb-3">Availability</h3>
          <div class="grid grid-cols-2 gap-2">
            <div
              v-for="slot in tutor.availability"
              :key="slot.day"
              class="flex items-center justify-between rounded-lg border border-border bg-muted/30 px-3 py-2 text-xs"
            >
              <span class="font-medium text-foreground">{{ slot.day }}</span>
              <span class="text-muted-foreground">{{ formatTime(slot.from) }} – {{ formatTime(slot.to) }}</span>
            </div>
          </div>
        </div>

        <!-- What the tutor offers -->
        <div>
          <h3 class="font-semibold text-foreground mb-3">What You Get</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div
              v-for="(item, i) in (tutor.highlights || defaultHighlights)"
              :key="i"
              class="flex items-center gap-2 text-sm text-muted-foreground"
            >
              <CheckCircle class="h-4 w-4 text-secondary shrink-0" />
              {{ item }}
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="pt-4 border-t border-border flex flex-col sm:flex-row gap-3">
          <Button size="lg" class="flex-1 gap-2" @click="$emit('close')">
            <Send class="h-4 w-4" />Send Invite
          </Button>
          <Button variant="outline" size="lg" class="flex-1" as-child>
            <a :href="`/tutors/${tutor.id}`" target="_blank">View Full Profile</a>
          </Button>
        </div>

      </div>
    </DialogContent>
  </Dialog>
</template>