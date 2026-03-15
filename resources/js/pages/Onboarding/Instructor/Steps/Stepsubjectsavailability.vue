<script setup>

import { ref, computed } from 'vue'
import { BookOpen, Clock, Plus, X, ArrowRight, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input }  from '@/components/ui/input'
import { Label }  from '@/components/ui/label'
import { Badge }  from '@/components/ui/badge'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'

const emit = defineEmits(['next', 'back'])

// ── Subjects ──────────────────────────────────────────────────────────────────
const SUGGESTED_SUBJECTS = [
  'Mathematics', 'English Language', 'Physics', 'Chemistry', 'Biology',
  'Economics', 'Accounting', 'Computer Science', 'Web Development',
  'Data Science', 'UI/UX Design', 'Python Programming', 'Civic Education',
  'Literature', 'Government', 'Geography', 'History', 'French',
]

const selectedSubjects = ref([])
const customSubject     = ref('')

function toggleSubject(subject) {
  const idx = selectedSubjects.value.indexOf(subject)
  if (idx === -1) {
    selectedSubjects.value.push(subject)
  } else {
    selectedSubjects.value.splice(idx, 1)
  }
}

function addCustomSubject() {
  const val = customSubject.value.trim()
  if (val && !selectedSubjects.value.includes(val)) {
    selectedSubjects.value.push(val)
  }
  customSubject.value = ''
}

function removeSubject(subject) {
  selectedSubjects.value = selectedSubjects.value.filter(s => s !== subject)
}

// ── Levels taught ─────────────────────────────────────────────────────────────
const LEVELS = [
  'Primary 1–3', 'Primary 4–6', 'JSS 1–3', 'SS 1–3',
  'University Level', 'Adult / Professional',
]

const selectedLevels = ref([])

function toggleLevel(level) {
  const idx = selectedLevels.value.indexOf(level)
  if (idx === -1) selectedLevels.value.push(level)
  else selectedLevels.value.splice(idx, 1)
}

// ── Availability ──────────────────────────────────────────────────────────────
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const availability = ref(
  DAYS.map(day => ({ day, enabled: false, from: '09:00', to: '17:00' }))
)

const yearsExperience = ref('')
const teachingMode    = ref('')

// ── Validation ────────────────────────────────────────────────────────────────
const isValid = computed(() =>
  selectedSubjects.value.length > 0 &&
  selectedLevels.value.length > 0 &&
  availability.value.some(d => d.enabled) &&
  yearsExperience.value &&
  teachingMode.value
)

function handleNext() {
  if (!isValid.value) return
  emit('next', {
    subjects:         selectedSubjects.value,
    levels:           selectedLevels.value,
    availability:     availability.value.filter(d => d.enabled),
    years_experience: yearsExperience.value,
    teaching_mode:    teachingMode.value,
  })
}
</script>

<template>
  <div class="space-y-7">
    <div>
      <h2 class="text-xl font-bold text-foreground">Subjects & Availability</h2>
      <p class="text-sm text-muted-foreground mt-1">
        Help schools find you by specifying what you teach and when you're available.
      </p>
    </div>

    <!-- Subjects -->
    <div class="space-y-3">
      <Label class="flex items-center gap-1.5 text-sm font-semibold">
        <BookOpen class="h-4 w-4 text-primary" />
        Subjects You Teach <span class="text-destructive">*</span>
      </Label>

      <!-- Suggested chips -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="subject in SUGGESTED_SUBJECTS" :key="subject"
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
          :class="selectedSubjects.includes(subject)
            ? 'bg-primary text-primary-foreground border-primary shadow-sm'
            : 'bg-background text-muted-foreground border-border hover:border-primary/40'"
          @click="toggleSubject(subject)"
        >
          {{ subject }}
        </button>
      </div>

      <!-- Custom subject input -->
      <div class="flex gap-2">
        <Input
          v-model="customSubject"
          placeholder="Add a custom subject..."
          class="flex-1"
          @keydown.enter.prevent="addCustomSubject"
        />
        <Button variant="outline" size="sm" class="gap-1.5 shrink-0" @click="addCustomSubject">
          <Plus class="h-4 w-4" />Add
        </Button>
      </div>

      <!-- Selected subjects -->
      <div v-if="selectedSubjects.length" class="flex flex-wrap gap-2">
        <Badge
          v-for="subject in selectedSubjects" :key="subject"
          variant="secondary"
          class="gap-1.5 pl-3 pr-2 py-1"
        >
          {{ subject }}
          <button type="button" @click="removeSubject(subject)">
            <X class="h-3 w-3 hover:text-destructive" />
          </button>
        </Badge>
      </div>
      <p v-else class="text-xs text-muted-foreground">Select at least one subject above.</p>
    </div>

    <!-- Levels taught -->
    <div class="space-y-3">
      <Label class="text-sm font-semibold">
        Academic Levels <span class="text-destructive">*</span>
      </Label>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="level in LEVELS" :key="level"
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
          :class="selectedLevels.includes(level)
            ? 'bg-secondary text-secondary-foreground border-secondary shadow-sm'
            : 'bg-background text-muted-foreground border-border hover:border-secondary/40'"
          @click="toggleLevel(level)"
        >
          {{ level }}
        </button>
      </div>
    </div>

    <!-- Experience & mode -->
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <Label>Years of Teaching Experience <span class="text-destructive">*</span></Label>
        <Select v-model="yearsExperience">
          <SelectTrigger><SelectValue placeholder="Select range" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="0-1">Less than 1 year</SelectItem>
            <SelectItem value="1-3">1–3 years</SelectItem>
            <SelectItem value="3-5">3–5 years</SelectItem>
            <SelectItem value="5-10">5–10 years</SelectItem>
            <SelectItem value="10+">10+ years</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-1.5">
        <Label>Preferred Teaching Mode <span class="text-destructive">*</span></Label>
        <Select v-model="teachingMode">
          <SelectTrigger><SelectValue placeholder="Select mode" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="online">Online only</SelectItem>
            <SelectItem value="in-person">In-person only</SelectItem>
            <SelectItem value="hybrid">Both online & in-person</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Weekly availability -->
    <div class="space-y-3">
      <Label class="flex items-center gap-1.5 text-sm font-semibold">
        <Clock class="h-4 w-4 text-primary" />
        Weekly Availability <span class="text-destructive">*</span>
      </Label>
      <p class="text-xs text-muted-foreground">Toggle the days you're available and set your hours.</p>

      <div class="space-y-2 rounded-xl border border-border overflow-hidden">
        <div
          v-for="slot in availability" :key="slot.day"
          class="flex items-center gap-3 px-4 py-3 border-b border-border/50 last:border-0 transition-colors"
          :class="slot.enabled ? 'bg-primary/5' : 'bg-background'"
        >
          <!-- Toggle -->
          <button
            type="button"
            class="relative h-5 w-9 rounded-full transition-colors shrink-0"
            :class="slot.enabled ? 'bg-primary' : 'bg-border'"
            @click="slot.enabled = !slot.enabled"
          >
            <span
              class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
              :class="slot.enabled ? 'translate-x-4' : 'translate-x-0.5'"
            />
          </button>

          <span class="text-sm font-medium text-foreground w-24 shrink-0">{{ slot.day }}</span>

          <template v-if="slot.enabled">
            <div class="flex items-center gap-2 flex-1">
              <Input v-model="slot.from" type="time" class="h-8 text-xs w-28" />
              <span class="text-xs text-muted-foreground shrink-0">to</span>
              <Input v-model="slot.to" type="time" class="h-8 text-xs w-28" />
            </div>
          </template>
          <span v-else class="text-xs text-muted-foreground">Unavailable</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-2">
      <Button variant="outline" class="gap-2" @click="$emit('back')">
        <ArrowLeft class="h-4 w-4" />Back
      </Button>
      <Button :disabled="!isValid" class="gap-2 min-w-32" @click="handleNext">
        Continue <ArrowRight class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>