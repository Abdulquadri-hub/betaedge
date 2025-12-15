<template>
  <div class="min-h-screen bg-gradient-to-br from-background via-background to-primary/5">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-foreground mb-2">
          Welcome to {{ name }}
        </h1>
        <p class="text-muted-foreground">
          Let's set up your school in just a few steps
        </p>
      </div>

      <!-- Progress -->
      <div class="max-w-5xl mx-auto mb-8">
        <div class="w-full bg-secondary rounded-full h-2 mb-6">
          <div 
            class="bg-primary h-2 rounded-full transition-all duration-300"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
        
        <div class="flex justify-between">
          <div
            v-for="step in steps"
            :key="step.id"
            class="flex flex-col items-center gap-2 transition-all"
            :class="{
              'scale-105': currentStep === step.id,
              'cursor-pointer': isStepClickable(step.id)
            }"
            @click="handleStepClick(step.id)"
          >
            <div
              class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center border-2 transition-all"
              :class="getStepClasses(step.id)"
            >
              <component
                :is="isStepCompleted(step.id) && currentStep !== step.id ? CheckIcon : step.icon"
                class="w-4 h-4 md:w-5 md:h-5"
              />
            </div>
            <div class="hidden md:block text-center">
              <p
                class="text-xs font-medium"
                :class="currentStep === step.id ? 'text-primary' : 'text-muted-foreground'"
              >
                {{ step.title }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="max-w-4xl mx-auto">
        <div class="bg-card rounded-2xl border shadow-lg p-6 md:p-8">
          <div v-if="currentStep < 5" class="mb-6">
            <h2 class="text-2xl font-bold text-foreground">
              {{ steps[currentStep - 1].title }}
            </h2>
            <p class="text-muted-foreground">
              {{ steps[currentStep - 1].description }}
            </p>
          </div>

          <transition
            mode="out-in"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-5"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-5"
          >
            <component
              :is="currentStepComponent"
              :key="currentStep"
              :data="currentStepData"
              :errors="errors"
              :draft="draft"
              :plans="plans"
              :paystack-public-key="paystackPublicKey"
              @update="handleUpdate"
              @edit-step="handleEditStep"
              @complete="handleCompleteSetup"
              @retry="handleRetry"
            />
          </transition>

          <!-- Navigation -->
          <div v-if="showNavigation" class="flex justify-between mt-8 pt-6 border-t">
            <button
              @click="handlePrevious"
              :disabled="currentStep === 1"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ArrowLeftIcon class="w-4 h-4" />
              Previous
            </button>

            <button
              v-if="currentStep === 4"
              @click="handleCompleteSetup"
              :disabled="processing"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
            >
              <span v-if="processing">Processing...</span>
              <span v-else>Complete Setup</span>
              <CheckIcon class="w-4 h-4" />
            </button>
            
            <button
              v-else
              @click="handleNext"
              :disabled="processing"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90"
            >
              Next Step
              <ArrowRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
  School, 
  CreditCard, 
  ArrowRight, 
  ArrowLeft, 
  Crown, 
  ClipboardCheck, 
  Rocket,
  Check
} from 'lucide-vue-next'

// Async load step components
const SchoolProfileStep = defineAsyncComponent(() => import('./Steps/SchoolProfileStep.vue'))
const SubscriptionPlanStep = defineAsyncComponent(() => import('./Steps/SubscriptionPlanStep.vue'))
const PaymentSetupStep = defineAsyncComponent(() => import('./Steps/PaymentSetupStep.vue'))
const ReviewStep = defineAsyncComponent(() => import('./Steps/ReviewStep.vue'))
const ProcessingStep = defineAsyncComponent(() => import('./Steps/ProcessingStep.vue'))

// Icons
const CheckIcon = Check
const ArrowLeftIcon = ArrowLeft
const ArrowRightIcon = ArrowRight

const props = defineProps({
  draft: {
    type: Object,
    default: () => null
  },
  plans: {
    type: Array,
    required: true
  },
  paystackPublicKey: {
    type: String,
    required: true
  },
  name: {
    type: String,
    default: 'EduConnect Africa'
  }
})

const steps = [
  { id: 1, title: 'School Profile', icon: School, description: 'Basic information about your school' },
  { id: 2, title: 'Select Plan', icon: Crown, description: 'Choose the right plan for your school' },
  { id: 3, title: 'Payment', icon: CreditCard, description: 'Set up your payment method' },
  { id: 4, title: 'Review', icon: ClipboardCheck, description: 'Review and confirm your information' },
  { id: 5, title: 'Setup', icon: Rocket, description: 'Creating your school platform' },
]

// State
const currentStep = ref(1)
const errors = ref({})
const completedSteps = ref([])
const processing = ref(false)

// Initialize data from draft if available
const formData = ref({
  profile: props.draft?.profile || {
    school_name: '',
    owner_email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    description: '',
    logo: null,
    website: '',
    year_established: '',
    school_type: '',
  },
  plan: props.draft?.plan || {
    plan_id: '',
    billing_cycle: 'monthly',
  },
  payment: props.draft?.payment || {
    paystack_reference: '',
  },
})

// Computed
const progress = computed(() => (currentStep.value / steps.length) * 100)

const isFreeplan = computed(() => {
  const selectedPlan = props.plans.find(p => p.id === formData.value.plan.plan_id)
  return selectedPlan?.slug === 'free'
})

const showNavigation = computed(() => currentStep.value < 5)

const currentStepComponent = computed(() => {
  switch (currentStep.value) {
    case 1: return SchoolProfileStep
    case 2: return SubscriptionPlanStep
    case 3: return PaymentSetupStep
    case 4: return ReviewStep
    case 5: return ProcessingStep
    default: return null
  }
})

const currentStepData = computed(() => {
  switch (currentStep.value) {
    case 1: return formData.value.profile
    case 2: return { ...formData.value.plan, plans: props.plans }
    case 3: return { ...formData.value.payment, planInfo: formData.value.plan, plans: props.plans }
    case 4: return formData.value
    case 5: return { schoolName: formData.value.profile.school_name }
    default: return {}
  }
})

// Methods
const isStepCompleted = (stepId) => {
  return completedSteps.value.includes(stepId) || currentStep.value > stepId
}

const isStepClickable = (stepId) => {
  return completedSteps.value.includes(stepId) && stepId < currentStep.value
}

const getStepClasses = (stepId) => {
  const isCompleted = isStepCompleted(stepId)
  const isCurrent = currentStep.value === stepId
  
  if (isCompleted) {
    return 'bg-primary border-primary text-primary-foreground'
  } else if (isCurrent) {
    return 'border-primary bg-primary/10 text-primary'
  } else {
    return 'border-muted bg-muted/50 text-muted-foreground'
  }
}

const handleStepClick = (stepId) => {
  if (isStepClickable(stepId)) {
    currentStep.value = stepId
  }
}

const handleUpdate = (section, updates) => {
  if (section === 'profile' || section === 'plan' || section === 'payment') {
    formData.value[section] = { ...formData.value[section], ...updates }
    errors.value = {}
    
    // Auto-save draft
    saveDraft(section)
  }
}

const saveDraft = (step) => {
  router.post('/onboarding/save', {
    step,
    data: formData.value[step],
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['draft', 'errors'],
    onError: (errs) => {
      errors.value = errs
    }
  })
}

const handleNext = () => {
  // Save current step before moving forward
  const stepMap = { 1: 'profile', 2: 'plan', 3: 'payment' }
  const section = stepMap[currentStep.value]
  
  if (section) {
    router.post('/onboarding/save', {
      step: section,
      data: formData.value[section],
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['draft', 'errors'],
      onSuccess: () => {
        errors.value = {}
        
        // Mark current step as completed
        if (!completedSteps.value.includes(currentStep.value)) {
          completedSteps.value.push(currentStep.value)
        }

        // Skip payment step for free plan
        if (currentStep.value === 2 && isFreeplan.value) {
          formData.value.payment.paystack_reference = 'free'
          currentStep.value = 4
          return
        }

        if (currentStep.value < steps.length) {
          currentStep.value++
        }
      },
      onError: (errs) => {
        errors.value = errs
      }
    })
  } else {
    if (currentStep.value < steps.length) {
      currentStep.value++
    }
  }
}

const handlePrevious = () => {
  // From review, go back to payment (or plan selection if free)
  if (currentStep.value === 4 && isFreeplan.value) {
    currentStep.value = 2
    return
  }

  if (currentStep.value > 1) {
    currentStep.value--
    errors.value = {}
  }
}

const handleEditStep = (stepId) => {
  currentStep.value = stepId
}

const handleCompleteSetup = () => {
  processing.value = true
  
  router.post('/onboarding/submit', formData.value, {
    preserveState: false,
    onSuccess: (page) => {
      // Move to processing step
      currentStep.value = 5
      processing.value = false
    },
    onError: (errs) => {
      errors.value = errs
      processing.value = false
    }
  })
}

const handleRetry = () => {
  currentStep.value = 4
}
</script>