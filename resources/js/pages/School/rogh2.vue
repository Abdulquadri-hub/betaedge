<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, CheckCircle } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import AccountTypeStep from '@/components/enrollment/AccountTypeStep.vue'
import ParentRegistrationStep from '@/components/enrollment/ParentRegistrationStep.vue'
import ChildRegistrationStep from '@/components/enrollment/ChildRegistrationStep.vue'
import AdultRegistrationStep from '@/components/enrollment/AdultRegistrationStep.vue'
import CourseSelectionStep from '@/components/enrollment/CourseSelectionStep.vue'
import BatchSelectionStep from '@/components/enrollment/BatchSelectionStep.vue'
import PaymentStep from '@/components/enrollment/PaymentStep.vue'
import EnrollmentSuccessStep from '@/components/enrollment/EnrollmentSuccessStep.vue'
import { useEnrollmentParams, useEnrollmentTracking } from '@/composables/useEnrollmentParams'

/**
 * EnrollmentPage - V3 Complete Wizard with Batch System
 * 
 * Flow:
 * 0. Account Type Selection (adult vs parent/child)
 * 1. Registration (user details)
 * 2. Course Selection (multiple courses)
 * 3. Batch Selection (one batch per course) ⭐ NEW
 * 4. Payment (process enrollment)
 * 5. Success (confirmation)
 * 
 * Laravel Inertia Integration:
 * import { router } from '@inertiajs/vue3'
 * 
 * router.post('/enrollment', enrollmentData, {
 *   onSuccess: () => setCurrentStep(5),
 *   onError: (errors) => handleErrors(errors)
 * })
 * 
 * Security:
 * - All user inputs validated
 * - CSRF token (Laravel handles)
 * - XSS prevention (sanitize inputs)
 * - Rate limiting (backend)
 */

const props = defineProps({
  slug: {
    type: String,
    required: true,
    validator: (value) => /^[a-z0-9-]+$/.test(value)
  }
})

const router = useRouter()
const { 
  preselectedCourse, 
  preselectedBatch, 
  hasFullPreselection,
  updateParams 
} = useEnrollmentParams()

const { trackStep, trackConversion } = useEnrollmentTracking()

// Steps configuration
const steps = [
  { id: 'type', label: 'Account Type' },
  { id: 'registration', label: 'Registration' },
  { id: 'courses', label: 'Select Courses' },
  { id: 'batches', label: 'Select Batches' },
  { id: 'payment', label: 'Payment' },
  { id: 'success', label: 'Complete' }
]

// Current step index
const currentStep = ref(0)

// Enrollment data state
const enrollmentData = reactive({
  enrollmentType: null, // 'adult' | 'parent'
  parent: {
    name: '',
    email: '',
    phone: '',
    password: ''
  },
  child: {
    name: '',
    dateOfBirth: '',
    gender: '',
    grade: ''
  },
  adult: {
    name: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    password: ''
  },
  selectedCourses: [],
  selectedBatches: {}, // { courseId: batchId }
  paymentMethod: '',
  paymentReference: ''
})

// Mock school data
const school = ref({
  slug: 'brightstars',
  name: 'Bright Stars Academy'
})

// Computed: Progress percentage
const progressPercentage = computed(() => {
  // Exclude success step from progress
  const totalSteps = steps.length - 1
  return ((currentStep.value) / totalSteps) * 100
})

// Computed: Current step config
const currentStepConfig = computed(() => {
  return steps[currentStep.value]
})

// Computed: Can proceed to next step
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0: // Account Type
      return enrollmentData.enrollmentType !== null
    
    case 1: // Registration
      if (enrollmentData.enrollmentType === 'parent') {
        return (
          enrollmentData.parent.name &&
          enrollmentData.parent.email &&
          enrollmentData.parent.phone &&
          enrollmentData.parent.password &&
          enrollmentData.child.name &&
          enrollmentData.child.dateOfBirth &&
          enrollmentData.child.gender &&
          enrollmentData.child.grade
        )
      } else {
        return (
          enrollmentData.adult.name &&
          enrollmentData.adult.email &&
          enrollmentData.adult.phone &&
          enrollmentData.adult.dateOfBirth &&
          enrollmentData.adult.password
        )
      }
    
    case 2: // Course Selection
      return enrollmentData.selectedCourses.length > 0
    
    case 3: // Batch Selection
      return Object.keys(enrollmentData.selectedBatches).length === enrollmentData.selectedCourses.length
    
    case 4: // Payment
      return enrollmentData.paymentMethod !== ''
    
    default:
      return true
  }
})

// Navigate to next step
const nextStep = () => {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++
    trackStep(currentStepConfig.value.id, {
      step_number: currentStep.value
    })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Navigate to previous step
const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Update enrollment data
const updateData = (data) => {
  Object.assign(enrollmentData, data)
}

// Handle account type selection
const handleAccountTypeSelect = (type) => {
  enrollmentData.value.enrollmentType = type
  nextStep()
}

// Handle registration completion
const handleRegistrationComplete = () => {
  nextStep()
}

// Handle course selection
const handleCourseSelection = (courses) => {
  enrollmentData.selectedCourses = courses
  
  // Update URL params
  if (courses.length > 0) {
    updateParams({ course: courses[0] })
  }
}

// Handle batch selection
const handleBatchSelection = (batches) => {
  enrollmentData.value.selectedBatches = batches
  
  // Update URL with first batch
  const firstBatch = Object.values(batches)[0]
  if (firstBatch) {
    updateParams({ batch: firstBatch })
  }
}

// Handle payment submission
const handlePaymentSubmit = async (paymentData) => {
  try {
    // Update payment data
    Object.assign(enrollmentData, paymentData)
    
    // Laravel Inertia Integration:
    // import { router } from '@inertiajs/vue3'
    // 
    // router.post('/enrollment', enrollmentData, {
    //   onSuccess: (page) => {
    //     trackConversion({
    //       courses: enrollmentData.selectedCourses,
    //       batches: enrollmentData.selectedBatches,
    //       amount: page.props.totalAmount
    //     })
    //     nextStep()
    //   },
    //   onError: (errors) => {
    //     console.error('Enrollment failed:', errors)
    //   }
    // })
    
    // Mock submission
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Track conversion
    trackConversion({
      courses: enrollmentData.selectedCourses,
      batches: enrollmentData.selectedBatches
    })
    
    nextStep()
  } catch (error) {
    console.error('Payment error:', error)
  }
}

// Initialize with URL params
onMounted(() => {
  if (preselectedCourse.value) {
    enrollmentData.selectedCourses = [preselectedCourse.value]
  }
  
  if (preselectedBatch.value && preselectedCourse.value) {
    enrollmentData.selectedBatches = {
      [preselectedCourse.value]: preselectedBatch.value
    }
  }
  
  // Track page view
  trackStep('enrollment_started')
})

// Watch for step changes to track abandonment
let abandonmentTimer = null
watch(currentStep, (newStep) => {
  // Clear previous timer
  if (abandonmentTimer) {
    clearTimeout(abandonmentTimer)
  }
  
  // Set new abandonment timer (5 minutes)
  abandonmentTimer = setTimeout(() => {
    if (newStep < steps.length - 1) {
      const { trackAbandonment } = useEnrollmentTracking()
      trackAbandonment(steps[newStep].id)
    }
  }, 5 * 60 * 1000)
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Header -->
    <div class="bg-muted/50 border-b">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <button
          @click="router.push(`/school/${slug}`)"
          class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft class="h-4 w-4 mr-2" />
          Back to {{ school.name }}
        </button>
      </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Progress (hide on success step) -->
      <div v-if="currentStep < steps.length - 1" class="mb-8">
        <div class="flex justify-between mb-3">
          <div
            v-for="(step, index) in steps.slice(0, -1)"
            :key="step.id"
            class="flex items-center gap-2 text-sm transition-colors"
            :class="index <= currentStep ? 'text-primary' : 'text-muted-foreground'"
          >
            <span
              class="flex items-center justify-center h-7 w-7 rounded-full text-xs font-semibold transition-all"
              :class="
                index < currentStep
                  ? 'bg-primary text-primary-foreground'
                  : index === currentStep
                  ? 'border-2 border-primary text-primary'
                  : 'border border-muted-foreground'
              "
            >
              <CheckCircle v-if="index < currentStep" class="h-4 w-4" />
              <span v-else>{{ index + 1 }}</span>
            </span>
            <span class="hidden sm:inline">{{ step.label }}</span>
          </div>
        </div>
        <Progress :model-value="progressPercentage" class="h-2" />
      </div>

      <!-- Step Content -->
      <Card>
        <CardContent class="p-6 md:p-8">
          <!-- Step 0: Account Type Selection -->
          <AccountTypeStep
            v-if="currentStep === 0"
            :selected-type="enrollmentData.enrollmentType"
            @select="handleAccountTypeSelect"
          />

          <!-- Step 1: Registration -->
          <div v-else-if="currentStep === 1">
            <template v-if="enrollmentData.enrollmentType === 'parent'">
              <ParentRegistrationStep
                :data="enrollmentData.parent"
                @update="(data) => Object.assign(enrollmentData.parent, data)"
              />
              <ChildRegistrationStep
                :data="enrollmentData.child"
                @update="(data) => Object.assign(enrollmentData.child, data)"
                class="mt-6"
              />
            </template>
            <template v-else>
              <AdultRegistrationStep
                :data="enrollmentData.adult"
                @update="(data) => Object.assign(enrollmentData.adult, data)"
              />
            </template>

            <div class="flex justify-between mt-6">
              <button
                @click="prevStep"
                class="text-sm text-muted-foreground hover:text-foreground"
              >
                ← Back
              </button>
              <button
                @click="handleRegistrationComplete"
                :disabled="!canProceed"
                class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-primary/90 transition-colors"
              >
                Continue
              </button>
            </div>
          </div>

          <!-- Step 2: Course Selection -->
          <CourseSelectionStep
            v-else-if="currentStep === 2"
            :selected-courses="enrollmentData.selectedCourses"
            :preselected-course-id="preselectedCourse"
            @update="handleCourseSelection"
            @next="nextStep"
            @back="prevStep"
          />

          <!-- Step 3: Batch Selection -->
          <BatchSelectionStep
            v-else-if="currentStep === 3"
            :selected-courses="enrollmentData.selectedCourses"
            :preselected-batches="enrollmentData.selectedBatches"
            @update="(data) => handleBatchSelection(data.selectedBatches)"
            @next="nextStep"
            @back="prevStep"
          />

          <!-- Step 4: Payment -->
          <PaymentStep
            v-else-if="currentStep === 4"
            :enrollment-data="enrollmentData"
            @submit="handlePaymentSubmit"
            @back="prevStep"
          />

          <!-- Step 5: Success -->
          <EnrollmentSuccessStep
            v-else-if="currentStep === 5"
            :enrollment-data="enrollmentData"
            :school-slug="slug"
          />
        </CardContent>
      </Card>
    </div>
  </div>
</template>