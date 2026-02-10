<script setup >
import { ref, computed } from 'vue'
import { Clock, Users, Calendar } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { mockCourses } from '@/data/mockCourses'
import { getNextAvailableBatch } from '@/data/mockBatches'
import { formatDate } from '@/utils/batchHelpers'

/**
 * Props
 */
const props = defineProps({
  selectedCourses: {
    type: Array,
    default: () => []
  },
  preselectedCourseId: {
    type: String,
    default: null
  }
})

/**
 * Emit events update, next, and back
 */
const emit = defineEmits(['update', 'next', 'back'])

/**
 * local Selected course ref
 */
const localSelected = ref([...props.selectedCourses])

/**
 * Course mock data
 */
const courses = mockCourses

/**
 * toggle course selection
 */
const toggleCourse = (courseId) => {
  const index = localSelected.value.indexOf(courseId)
  if (index > -1) {
    localSelected.value.splice(index, 1)
  } else {
    localSelected.value.push(courseId)
  }

  emit('update', localSelected.value)
}

/**
 * Check if course selected
 */
const isSelected = (courseId) => {
  return localSelected.value.includes(courseId)
}

/**
 * Get next batch info for the course
 */
const getNextBatchPreview = (courseId) => {
  const batch = getNextAvailableBatch(courseId)
  if (!batch) return null

  return {
    startDate: formatDate(batch.start_date),
    spotsLeft: batch.max_students - batch.current_enrollment
  }
}

/**
 * total price
 */
const totalPrice = computed(() => {
  return localSelected.value.reduce((sum, courseId) => {
    const course = courses.find(c => c.id === courseId)
    return sum + course?.price
  }, 0)
})

/**
 * handle continue
 */
const handleContinue = () => {
  if (localSelected.value.length > 0) {
    emit('next')
  }
}

</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <h2 class="text-3xl font-bold">Select Courses</h2>
      <p class="text-muted-foregeound mt-2">
        Choose the courses you want to enroll in
      </p>
    </div>

    <!-- Course Grid -->
    <div class="space-y-4">
      <Card
        v-for="course in courses"
        :key="course.id"
        class="cursor-pointer transition-all"
        :class="{
          'border-2 border-primary ring-2 ring-primary/20 bg-primary/5': isSelected(course.id),
          'hover:border-primary/50 hover:shadow-md': !isSelected(course.id)
        }"
        @click="toggleCourse(course.id)"
      >
        <CardContent class="p-4">
          <div class="flex gap-4">
            <!-- checkBox -->
            <div class="flex items-start pt-1">
              <Checkbox
                :checked="isSelected(course.id)"
                @update:checked="toggleCourse(course.id)"
              />
            </div>
            
            <!-- Course Image -->
            <img 
              :src="course.image" 
              :alt="course.title" 
              class="h-24 w-32 rounded-lg object-cover flex-shrink-0" 
            />

            <!-- Course Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex-1">
                  <h3 class="font-semibold text-lg mb-1">{{ course.title }}</h3>
                  <p class="text-sm text-muted-foreground line-clamp-2">
                    {{ course.description }}
                  </p>
                </div>
                <div class="text-right flex-shrink-0">
                  <div class="font-bold text-lg">₦{{ course.price.toLocaleString() }}</div>
                </div>
              </div>

              <!-- Meta Info -->
              <div class="flex flex-wrap items-center gap-4 mt-3">
                <Badge variant="outline" class="text-xs">{{ course.level }}</Badge>
                <span class="text-xs text-muted-foreground flex items-center gap-1">
                  <Clock class="h-3.5 w-3.5" />
                  {{ course.duration }}
                </span>
                <span class="text-xs text-muted-foreground flex items-center gap-1">
                  <Users class="h-3.5 w-3.5" />
                  {{ course.enrolledCount }} enrolled
                </span>
              </div>

              <!-- Next Batch Preview -->
              <div v-if="getNextBatchPreview(course.id)" class="mt-3 flex items-center gap-2 text-xs">
                <Calendar class="h-3.5 w-3.5 text-muted-foreground" />
                <span class="text-muted-foreground">
                  Next batch: {{ getNextBatchPreview(course.id).startDate }}
                </span>
                <Badge 
                  v-if="getNextBatchPreview(course.id).spotsLeft <= 5"
                  variant="destructive" 
                  class="text-xs"
                >
                  {{ getNextBatchPreview(course.id).spotsLeft }} spots left
                </Badge>
              </div>
              
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Summary -->
    <Card class="bg-muted/50">
      <CardContent class="p-4">
        <div class="flex justify-between items-center">
          <div>
            <span class="text-sm text-muted-foreground">
              {{ localSelected.length }} course(s) selected
            </span>
          </div>
          <div class="text-right">
            <div class="text-sm text-muted-foreground">Total</div>
            <div class="text-2xl font-bold">₦{{ totalPrice.toLocaleString() }}</div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Navigation -->
    <div class="flex justify-between">
      <Button variant="outline" class="cursor-pointer" @click="$emit('back')">
        Back
      </Button>
      <Button 
        @click="handleContinue" 
        :disabled="localSelected.length === 0"
        class="cursor-pointer"
      >
        Continue to Batch Selection
      </Button>
    </div>

  </div>
</template>