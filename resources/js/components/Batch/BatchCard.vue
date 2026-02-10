<script setup>
import { computed } from 'vue'
import { Calendar, Clock, Users, Lock } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import {
  formatDate,
  formatTime,
  formatDayOfWeek,
  getBatchStatusBadge,
  getUrgencyMessage,
  canEnrollInBatch,
  getSpotsRemaining
} from '@/utils/batchHelpers'

const props = defineProps({
  batch: {
    type: Object,
    required: true,
    validator: (batch) => {
      return batch &&
        typeof batch.id === 'string' &&
        typeof batch.name === 'string' &&
        typeof batch.start_date === 'string' &&
        typeof batch.end_date === 'string'
    }
  },
  selectable: {
    type: Boolean,
    default: false
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select'])

// Computed: Status badge
const statusBadge = computed(() => {
  return getBatchStatusBadge(props.batch)
})

// Computed: Can enroll
const canEnroll = computed(() => {
  return canEnrollInBatch(props.batch)
})

// Computed: Formatted date range
const formattedDateRange = computed(() => {
  const start = formatDate(props.batch.start_date)
  const end = formatDate(props.batch.end_date)
  return `${start} - ${end}`
})

// Computed: Formatted schedule
const formattedSchedule = computed(() => {
  if (!props.batch.schedule) return ''
  
  const day = formatDayOfWeek(props.batch.schedule.day_of_week)
  const time = formatTime(props.batch.schedule.time)
  const duration = props.batch.schedule.duration_minutes
  
  return `${day}s, ${time} (${duration} min)`
})

// Computed: Enrollment text
const enrollmentText = computed(() => {
  const current = props.batch.current_enrollment || 0
  const max = props.batch.max_students || 0
  const remaining = getSpotsRemaining(props.batch)
  console.log(remaining);
  
  
  return `${current}/${max} enrolled`
})

// Computed: Spots remaining
const spotsRemaining = computed(() => {
  return getSpotsRemaining(props.batch)
})

// Computed: Urgency message
const urgencyMessage = computed(() => {
  return getUrgencyMessage(props.batch)
})

// Computed: Opens at message
const opensAtMessage = computed(() => {
  if (props.batch.status !== 'upcoming' || !props.batch.opens_at) {
    return null
  }
  
  const opensDate = formatDate(props.batch.opens_at)
  return `Opens for enrollment on ${opensDate}`
})

// Computed: Badge variant mapping
const badgeVariant = computed(() => {
  const variantMap = {
    success: 'default',
    warning: 'secondary',
    info: 'outline',
    destructive: 'destructive',
    secondary: 'secondary'
  }
  return variantMap[statusBadge.value.variant] || 'secondary'
})

// Handle click
const handleClick = () => {
  if (props.selectable && canEnroll.value) {
    emit('select', props.batch.id)
  }
}
</script>

<template>
  <div
    class="relative border-2 rounded-xl p-5 transition-all duration-200"
    :class="{
      'border-primary bg-primary/5 ring-2 ring-primary/20': isSelected,
      'border-border hover:border-primary/50 hover:shadow-md hover:-translate-y-0.5': selectable && canEnroll && !isSelected,
      'border-border bg-muted/30 opacity-60 cursor-not-allowed': !canEnroll,
      'cursor-pointer': selectable && canEnroll
    }"
    @click="handleClick"
    role="button"
    :tabindex="selectable && canEnroll ? 0 : -1"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <!-- Header: Badge + Radio -->
    <div class="flex items-start justify-between gap-3 mb-4">
      <Badge :variant="badgeVariant" class="text-xs font-semibold uppercase tracking-wide">
        {{ statusBadge.text }}
      </Badge>
      
      <!-- Radio button for selection -->
      <div v-if="selectable" class="flex-shrink-0">
        <div 
          class="h-5 w-5 rounded-full border-2 flex items-center justify-center transition-colors"
          :class="{
            'border-primary bg-primary': isSelected,
            'border-muted-foreground': !isSelected && canEnroll,
            'border-muted': !canEnroll
          }"
        >
          <div v-if="isSelected" class="h-2.5 w-2.5 rounded-full bg-primary-foreground" />
        </div>
      </div>
    </div>

    <!-- Batch Name -->
    <h3 class="text-lg font-bold text-foreground mb-4">
      {{ batch.name }}
    </h3>

    <!-- Info Grid -->
    <div class="space-y-3">
      <!-- Date Range -->
      <div class="flex items-center gap-2.5 text-sm text-muted-foreground">
        <Calendar class="h-4 w-4 flex-shrink-0" />
        <span>{{ formattedDateRange }}</span>
      </div>

      <!-- Schedule -->
      <div class="flex items-center gap-2.5 text-sm text-muted-foreground">
        <Clock class="h-4 w-4 flex-shrink-0" />
        <span>{{ formattedSchedule }}</span>
      </div>

      <!-- Enrollment -->
      <div class="flex items-center gap-2.5 text-sm text-muted-foreground">
        <Users class="h-4 w-4 flex-shrink-0" />
        <span>{{ enrollmentText }}</span>
        <span class="ml-auto text-xs font-medium" :class="spotsRemaining <= 5 ? 'text-destructive' : 'text-muted-foreground'">
          {{ spotsRemaining }} spots left
        </span>
      </div>
    </div>

    <!-- Urgency Message -->
    <div 
      v-if="urgencyMessage && canEnroll" 
      class="mt-4 px-3 py-2 rounded-lg bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800"
    >
      <p class="text-xs font-semibold text-orange-800 dark:text-orange-200 text-center">
        {{ urgencyMessage }}
      </p>
    </div>

    <!-- Opens At Message (upcoming batches) -->
    <div 
      v-if="opensAtMessage" 
      class="mt-4 px-3 py-2 rounded-lg bg-muted border border-border"
    >
      <p class="text-xs text-muted-foreground text-center flex items-center justify-center gap-1.5">
        <Lock class="h-3.5 w-3.5" />
        {{ opensAtMessage }}
      </p>
    </div>
  </div>
</template>