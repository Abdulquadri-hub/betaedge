<script setup>
import { computed } from 'vue'
import { AlertCircle } from 'lucide-vue-next'
import BatchCard from './BatchCard.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'

const props = defineProps({
  batches: {
    type: Array,
    required: true,
    default: () => []
  },
  selectedBatchId: {
    type: String,
    default: null
  },
  selectable: {
    type: Boolean,
    default: true
  },
  showUnavailable: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['select'])

// Computed: Available batches (can enroll)
const availableBatches = computed(() => {
  return props.batches.filter(batch => 
    batch.status === 'open' && batch.current_enrollment < batch.max_students
  )
})

// Computed: Unavailable batches (full or upcoming)
const unavailableBatches = computed(() => {
  return props.batches.filter(batch => 
    batch.status !== 'open' || batch.current_enrollment >= batch.max_students
  )
})

// Computed: Has available batches
const hasAvailableBatches = computed(() => {
  return availableBatches.value.length > 0
})

// Handle batch selection
const handleSelect = (batchId) => {
  emit('select', batchId)
}
</script>

<template>
  <div class="space-y-6">
    <!-- No Available Batches Warning -->
    <Alert v-if="!hasAvailableBatches" variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>
        No batches are currently available for enrollment. 
        <span v-if="unavailableBatches.length > 0">
          Check upcoming batches below or contact the school for availability.
        </span>
      </AlertDescription>
    </Alert>

    <!-- Available Batches -->
    <div v-if="availableBatches.length > 0" class="space-y-3">
      <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
        Available Batches
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BatchCard
          v-for="batch in availableBatches"
          :key="batch.id"
          :batch="batch"
          :selectable="selectable"
          :is-selected="batch.id === selectedBatchId"
          @select="handleSelect"
        />
      </div>
    </div>

    <!-- Unavailable Batches (if showing) -->
    <div v-if="showUnavailable && unavailableBatches.length > 0" class="space-y-3">
      <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
        Upcoming & Full Batches
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BatchCard
          v-for="batch in unavailableBatches"
          :key="batch.id"
          :batch="batch"
          :selectable="false"
          :is-selected="false"
        />
      </div>
    </div>
  </div>
</template>