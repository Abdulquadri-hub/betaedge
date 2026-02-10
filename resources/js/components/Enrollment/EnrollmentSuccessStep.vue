<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { CheckCircle, Mail, BookOpen, Calendar, MessageSquare } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { getCourseById } from '@/data/mockCourses'
import { getBatchById } from '@/data/mockBatches'
import { formatDate, formatTime, formatDayOfWeek } from '@/utils/batchHelpers'

/**
 * EnrollmentSuccessStep - V3 with Batch Details
 * 
 * Shows:
 * - Batch-specific success messaging
 * - First class date/time
 * - WhatsApp group access
 * - Next steps for students
 */

const props = defineProps({
  enrollmentData: {
    type: Object,
    required: true
  },
  schoolSlug: {
    type: String,
    required: true
  }
})


// Computed: Student name
const studentName = computed(() => {
  return props.enrollmentData.enrollmentType === 'parent'
    ? props.enrollmentData.child.name
    : props.enrollmentData.adult.name
})

// Computed: Contact email
const contactEmail = computed(() => {
  return props.enrollmentData.enrollmentType === 'parent'
    ? props.enrollmentData.parent.email
    : props.enrollmentData.adult.email
})

// Computed: Enrollment details with batch info
const enrollmentDetails = computed(() => {
  return props.enrollmentData.selectedCourses.map(courseId => {
    const course = getCourseById(courseId)
    const batchId = props.enrollmentData.selectedBatches[courseId]
    const batch = getBatchById(batchId)
    
    return {
      courseId,
      courseName: course?.title || 'Unknown Course',
      batchName: batch?.name || 'Unknown Batch',
      startDate: batch ? formatDate(batch.start_date) : 'TBD',
      schedule: batch ? {
        day: formatDayOfWeek(batch.schedule.day_of_week),
        time: formatTime(batch.schedule.time),
        duration: batch.schedule.duration_minutes
      } : null,
      // First class is typically a week after start date (adjust logic as needed)
      firstClassDate: batch ? new Date(new Date(batch.start_date).getTime() + 7 * 24 * 60 * 60 * 1000) : null
    }
  })
})

// Computed: Next class (earliest first class)
const nextClass = computed(() => {
  const classes = enrollmentDetails.value
    .filter(item => item.firstClassDate)
    .sort((a, b) => a.firstClassDate - b.firstClassDate)
  
  return classes.length > 0 ? classes[0] : null
})

// Navigate to dashboard
const goToDashboard = () => {
  const dashboardRoute = props.enrollmentData.enrollmentType === 'parent'
    ? '/parent/dashboard'
    : '/student/dashboard'
  
  router.push(dashboardRoute)
}
</script>

<template>
  <div class="text-center space-y-6">
    <!-- Success Icon -->
    <div class="flex justify-center">
      <div class="h-20 w-20 rounded-full bg-green-100 dark:bg-green-950 flex items-center justify-center">
        <CheckCircle class="h-10 w-10 text-green-600 dark:text-green-400" />
      </div>
    </div>

    <!-- Success Message -->
    <div>
      <h2 class="text-3xl font-bold text-green-600 dark:text-green-400">
        Enrollment Successful!
      </h2>
      <p class="text-muted-foreground mt-2 text-lg">
        {{ studentName }} has been successfully enrolled in {{ enrollmentData.selectedCourses.length }} course(s)
      </p>
    </div>

    <!-- Enrollment Details -->
    <Card>
      <CardContent class="pt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="flex flex-col items-center gap-2">
            <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
              <Mail class="h-6 w-6 text-primary" />
            </div>
            <h3 class="font-medium">Check Your Email</h3>
            <p class="text-sm text-muted-foreground text-center">
              We've sent login details and course access instructions to{' '}
              <span class="font-medium">{{ contactEmail }}</span>
            </p>
          </div>

          <div class="flex flex-col items-center gap-2">
            <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
              <BookOpen class="h-6 w-6 text-primary" />
            </div>
            <h3 class="font-medium">Access Your Courses</h3>
            <p class="text-sm text-muted-foreground text-center">
              Log in to your dashboard to access course materials and prepare for live classes
            </p>
          </div>

          <div class="flex flex-col items-center gap-2">
            <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
              <MessageSquare class="h-6 w-6 text-primary" />
            </div>
            <h3 class="font-medium">Join WhatsApp Groups</h3>
            <p class="text-sm text-muted-foreground text-center">
              Links to batch WhatsApp groups are available in your dashboard
            </p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Next Class Info -->
    <Card v-if="nextClass" class="bg-primary/5 border-primary/20">
      <CardContent class="pt-6">
        <div class="flex items-center justify-center gap-3 mb-4">
          <Calendar class="h-6 w-6 text-primary" />
          <h3 class="text-lg font-semibold">Your First Live Class</h3>
        </div>
        
        <div class="space-y-2">
          <div class="text-2xl font-bold">
            {{ nextClass.courseName }}
          </div>
          <div class="text-muted-foreground">
            {{ nextClass.batchName }}
          </div>
          <div class="text-lg font-semibold text-primary mt-3">
            {{ nextClass.schedule.day }}s at {{ nextClass.schedule.time }}
          </div>
          <div class="text-sm text-muted-foreground">
            Starting {{ nextClass.startDate }}
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Enrolled Courses List -->
    <Card>
      <CardContent class="pt-6">
        <h3 class="font-medium mb-4">Your Enrolled Courses & Batches</h3>
        <div class="space-y-3 text-left">
          <div 
            v-for="item in enrollmentDetails" 
            :key="item.courseId"
            class="p-3 rounded-lg bg-muted/50 border"
          >
            <div class="font-medium">{{ item.courseName }}</div>
            <div class="text-sm text-muted-foreground">{{ item.batchName }}</div>
            <div class="text-xs text-muted-foreground mt-1">
              Starts {{ item.startDate }} • {{ item.schedule?.day }}s at {{ item.schedule?.time }}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Quick Reference -->
    <Card class="bg-muted/50">
      <CardContent class="pt-6">
        <h3 class="font-medium mb-4">Quick Reference</h3>
        <div class="grid grid-cols-2 gap-4 text-left text-sm">
          <div>
            <span class="text-muted-foreground">Student Portal:</span>
            <div class="font-medium">{{ enrollmentData.enrollmentType === 'parent' ? 'parent' : 'student' }}.teach.com</div>
          </div>
          <div>
            <span class="text-muted-foreground">Support Email:</span>
            <div class="font-medium">support@teach.com</div>
          </div>
          <div>
            <span class="text-muted-foreground">Enrollment ID:</span>
            <div class="font-medium">ENR-{{ Date.now().toString(36).toUpperCase() }}</div>
          </div>
          <div>
            <span class="text-muted-foreground">Payment Status:</span>
            <div class="font-medium text-green-600">Confirmed ✓</div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
      <Button size="lg" @click="goToDashboard">
        Go to Dashboard
      </Button>
      <Button variant="outline" size="lg" @click="router.push(`/school/${schoolSlug}`)">
        Back to School Page
      </Button>
    </div>

    <!-- Help Note -->
    <p class="text-sm text-muted-foreground">
      Need help? Contact us at{' '}
      <a href="mailto:support@teach.com" class="text-primary hover:underline">
        support@teach.com
      </a>{' '}
      or call <span class="font-medium">+234 801 234 5678</span>
    </p>
  </div>
</template>