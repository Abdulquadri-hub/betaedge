<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { useEnrollmentParams, useEnrollmentTracking } from '@/composables/useEnrollmentParams'
import AccountTypeStep from '@/components/Enrollment/AccountTypeStep.vue'
import ParentRegistrationStep from '@/components/Enrollment/ParentRegistrationStep.vue'
import ChildRegistrationStep from '@/components/Enrollment/ChildRegistrationStep.vue'
import AdultRegistrationStep from '@/components/Enrollment/AdultRegistrationStep.vue'
import CourseSelectionStep from '@/components/Enrollment/CourseSelectionStep.vue'
import BatchSelectionStep from '@/components/Enrollment/BatchSelectionStep.vue'
import PaymentStep from '@/components/Enrollment/PaymentStep.vue'
import EnrollmentSuccessStep from '@/components/Enrollment/EnrollmentSuccessStep.vue'

/**
 * Composables
 */
const { 
  preselectedCourse, 
  preselectedBatch, 
  hasFullPreselection,
  updateParams 
} = useEnrollmentParams()

const { trackStep, trackConversion } = useEnrollmentTracking()

/**
 * School mock data
 */
const school = ref({
    slug: 'elevate',
    name: 'Elevate'
})

/**
 * Steps config
 */
const steps = [
    { id: 'type', label: 'Account Type' },
    { id: 'registration', label: 'Registration' },
    { id: 'courses', label: 'Select Courses' },
    { id: 'batches', label: 'Select Batches' },
    { id: 'payment', label: 'Payment' },
    { id: 'success', label: 'Complete' }
]

const currentStep = ref(0)

/**
 * Current step in the steps config
 */
const currentStepConfig = computed(() => {
    return steps[currentStep.value]
})

/**
 * Progress percentage
 */
const progressPercentage = computed(() => {
    const totalSteps = steps.length - 1
    return (currentStep.value / totalSteps * 100)
})

/**
 * Enrollment data
 */
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

/**
 * Handle account type selection
 */
const handleAccountTypeSelect = (type) => {
    enrollmentData.enrollmentType = type
    nextStep()
}

/**
 * Next step
 */
const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++
        trackStep(currentStepConfig.value.id, {
            step_number: currentStep.value
        })
        window.scrollTo({top: 0, behavior: 'smooth'})
    }
}

/**
 * Prev
 */
const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--
        window.scrollTo({ top: 0, behavior: 'smooth'})
    }
}

/**
 * handle registration completion
 */
const handleRegistrationComplete = () => {
    nextStep()
}

/**
 * Check if user can proceed to the next step
 */
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0: // Account Type
      return enrollmentData.enrollmentType !== null
    
    case 1: // Registration
      if (enrollmentData.enrollmentType === 'parent') {
        console.log(enrollmentData);
        
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
        console.log(enrollmentData);
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

/**
 * Handle course selection
 */
const handleCourseSelection = (courses) => {
    enrollmentData.selectedCourses = courses

    //update the URL params
    // if (courses.length > 0) {
    //     updateParams({course: courses[0]})
    // }
}

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

/**
 * Initialize the preselected course ad btach on mounted
 */
onMounted( () => {
    if (preselectedCourse.value) {
        enrollmentData.selectedCourses = [preselectedCourse.value]
    }

    if (preselectedBatch.value && preselectedCourse.value) {
        enrollmentData.selectedBatches = {
            [preselectedCourse.value]: preselectedBatch.value
        }
    }

    trackStep('enrollment_started')
})

/**
 * Watch for step changes to track abandonment
 */
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
                <Link>
                    <button
                        class="inline-flex items-center text-muted-foreground text-sm hover:text-foreground transition-colors cursor-pointer">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to {{ school.name }}
                    </button>
                </Link>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <!-- Progress (hide on success step) -->
            <div v-if="currentStep < steps.length - 1" class="mb-8">
                <div class="flex justify-between mb-3">
                    <div 
                       v-for="(step, index) in steps.slice(0, -1)" :key="step.id" 
                       class="flex items-center gap-2 text-sm transition-colors"
                       :class="index <= currentStep ? 'text-primary' : 'text-muted-foreground'"
                    >
                        <span 
                            class="flex items-center justify-center h-7 w-7 rounded-full text-xs font-semibold transition-all"
                            :class="index < currentStep ? 'bg-primary text-primary-foreground' : index === currentStep ?  'border-2 border-primary text-primary' : 'border border-muted-foreground'"
                        >
                            <CheckCircle v-if="index < currentStep" class="w-4 h-4" />
                            <span class="" v-else>{{ index + 1 }}</span>
                        </span>
                        <span class="hidden sm:inline">{{ step.label }}</span>
                    </div>
                </div>
                <Progress :model-value="progressPercentage" class="h-2" />
            </div>

            <!-- Step content -->
            <Card>
                <CardContent class="p-6 md:p-8">
                    <AccountTypeStep 
                       v-if="currentStep === 0"
                       :selected-type="enrollmentData.enrollmentType"
                       @select="handleAccountTypeSelect"
                    />

                    <div v-else-if="currentStep === 1">
                        <template v-if="enrollmentData.enrollmentType === 'parent'">
                           <ParentRegistrationStep 
                               :data="enrollmentData.parent"
                               @update="(data) => Object.assign(enrollmentData.parent, data)"
                           />
                           <ChildRegistrationStep 
                              :data="enrollmentData.child"
                              @update="(data) => Object.assign(enrollmentData.child, data)"
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
                                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                            >
                                <ArrowLeft class="w-4 h-4"/> Back
                            </button>
                            <button 
                               @click="handleRegistrationComplete"
                               :disabled="!canProceed"
                               class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-primary/90 transition-colors cursor-pointer"
                            >
                                Continue
                            </button>
                        </div>
                    </div>

                    <CourseSelectionStep v-else-if="currentStep === 2"
                        :selected-courses="enrollmentData.selectedCourses" :preselected-course-id="preselectedCourse"
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
            :school-slug="school.slug"
          />

                </CardContent>
            </Card>
        </div>


    </div>
</template>