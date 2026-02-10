<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { AlertTriangle, CheckCircle2, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import BatchList from '../Batch/BatchList.vue'
import { useBatches, useBatchSelection } from '@/composables/useBatches'
import { getCourseById } from '@/data/mockCourses'

/**
 * BatchSelectionStep Component
 * 
 * User Flow:
 * 1. Show all selected courses
 * 2. For each course, display available batches
 * 3. User must select ONE batch per course
 * 4. Detect schedule conflicts (warn but allow)
 * 5. Validate before proceeding to payment
 * 
 * Laravel Inertia Integration:
 * - Receive selectedCourses from props
 * - Batches fetched via useBatches composable
 * - On submit: router.post('/enrollment/batches', selectedBatches)
 */

const props = defineProps({
  selectedCourses: {
    type: Array,
    required: true,
    default: () => []
  },
  preselectedBatches: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update', 'next', 'back'])

// Batch selection state
const {
  selectedBatches,
  conflicts,
  allCoursesHaveBatches,
  hasConflicts,
  selectionCount,
  selectBatch,
  resetSelections
} = useBatchSelection(props.selectedCourses)

// Batches data per course
const courseBatches = ref({})
const loadingCourses = ref(new Set())

// Load batches for each selected course
const loadBatchesForCourses = async () => {
  for (const courseId of props.selectedCourses) {
    loadingCourses.value.add(courseId)
    
    // Use composable to fetch batches
    const { batches, fetchBatches } = useBatches()
    await fetchBatches(courseId)
    
    courseBatches.value[courseId] = batches.value
    loadingCourses.value.delete(courseId)
  }
}

// Get course details
const getCourseDetails = (courseId) => {
  return getCourseById(courseId)
}

// Check if course batches are loading
const isCourseLoading = (courseId) => {
  return loadingCourses.value.has(courseId)
}

// Handle batch selection for a course
const handleBatchSelect = (courseId, batchId) => {
  selectBatch(courseId, batchId)
  
  // Emit update to parent
  emit('update', { selectedBatches: selectedBatches.value })
}

// Handle continue to payment
const handleContinue = () => {
  if (!allCoursesHaveBatches.value) {
    return
  }
  
  emit('next')
}

// Handle back
const handleBack = () => {
  emit('back')
}

// Initialize pre-selected batches from URL or previous step
onMounted(async () => {
  await loadBatchesForCourses()
  
  if (Object.keys(props.preselectedBatches).length > 0) {
    resetSelections(props.preselectedBatches)
  }
})

// Watch for course changes (if user goes back and modifies)
watch(() => props.selectedCourses, async (newCourses) => {
  await loadBatchesForCourses()
}, { deep: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <h2 class="text-2xl font-bold">Select Your Batches</h2>
      <p class="text-muted-foreground mt-2">
        Choose when you'd like to start each course
      </p>
    </div>

    <!-- Progress Indicator -->
    <Card class="bg-muted/50">
      <CardContent class="pt-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-muted-foreground">Batches Selected</p>
            <p class="text-2xl font-bold">
              {{ selectionCount }} / {{ selectedCourses.length }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <CheckCircle2 
              v-if="allCoursesHaveBatches" 
              class="h-10 w-10 text-green-600"
            />
            <div 
              v-else 
              class="h-10 w-10 rounded-full border-4 border-muted flex items-center justify-center"
            >
              <span class="text-sm font-semibold text-muted-foreground">
                {{ selectionCount }}
              </span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Schedule Conflict Warning -->
    <Alert v-if="hasConflicts" variant="destructive">
      <AlertTriangle class="h-4 w-4" />
      <AlertTitle>Schedule Conflict Detected</AlertTitle>
      <AlertDescription>
        <ul class="list-disc list-inside mt-2 space-y-1">
          <li v-for="(conflict, index) in conflicts" :key="index" class="text-sm">
            {{ conflict.batch1 }} and {{ conflict.batch2 }}: {{ conflict.conflict }}
          </li>
        </ul>
        <p class="mt-2 text-sm">
          You can still proceed, but please ensure you can manage both schedules.
        </p>
      </AlertDescription>
    </Alert>

    <!-- Course Batch Selection -->
    <div class="space-y-8">
      <div 
        v-for="courseId in selectedCourses" 
        :key="courseId"
        class="space-y-4"
      >
        <Card>
          <CardHeader>
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <CardTitle class="text-xl">
                  {{ getCourseDetails(courseId)?.title }}
                </CardTitle>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ getCourseDetails(courseId)?.duration }} • 
                  {{ getCourseDetails(courseId)?.level }}
                </p>
              </div>
              
              <!-- Selected Badge -->
              <div v-if="selectedBatches[courseId]" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 dark:bg-green-950 border border-green-300 dark:border-green-800">
                <CheckCircle2 class="h-4 w-4 text-green-700 dark:text-green-300" />
                <span class="text-xs font-semibold text-green-700 dark:text-green-300">
                  Batch Selected
                </span>
              </div>
            </div>
          </CardHeader>

          <Separator />

          <CardContent class="pt-6">
            <!-- Loading State -->
            <div v-if="isCourseLoading(courseId)" class="flex items-center justify-center py-12">
              <div class="text-center space-y-3">
                <Loader2 class="h-8 w-8 animate-spin mx-auto text-primary" />
                <p class="text-sm text-muted-foreground">Loading available batches...</p>
              </div>
            </div>

            <!-- Batch List -->
            <BatchList
              v-else
              :batches="courseBatches[courseId] || []"
              :selected-batch-id="selectedBatches[courseId]"
              :selectable="true"
              :show-unavailable="true"
              @select="(batchId) => handleBatchSelect(courseId, batchId)"
            />
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between items-center pt-6">
      <Button 
        variant="outline" 
        @click="handleBack"
        size="lg"
      >
        Back to Courses
      </Button>
      
      <Button 
        @click="handleContinue"
        :disabled="!allCoursesHaveBatches"
        size="lg"
        class="min-w-[180px]"
      >
        <template v-if="allCoursesHaveBatches">
          Continue to Payment
        </template>
        <template v-else>
          Select All Batches ({{ selectionCount }}/{{ selectedCourses.length }})
        </template>
      </Button>
    </div>

    <!-- Help Text -->
    <p class="text-center text-sm text-muted-foreground">
      Need help choosing? 
      <a href="#" class="text-primary hover:underline">Contact support</a>
    </p>
  </div>
</template>