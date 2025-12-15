<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
  Loader2, 
  Check, 
  AlertCircle, 
  Mail, 
  CheckCircle2,
  Copy,
  Info,
  ArrowRight,
  RefreshCw
} from 'lucide-vue-next'

const mainDoamin = import.meta.env.VITE_MAIN_DOMAIN || 'betaedge.com';

const Loader2Icon = Loader2
const CheckIcon = Check
const AlertCircleIcon = AlertCircle
const MailIcon = Mail
const CheckCircle2Icon = CheckCircle2
const CopyIcon = Copy
const InfoIcon = Info
const ArrowRightIcon = ArrowRight
const RefreshCwIcon = RefreshCw

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['complete', 'retry'])

const progress = ref(0)
const status = ref('processing')
const currentMessage = ref('Starting setup process...')
const subdomain = ref('')
const errorMessage = ref('')
const copied = ref(false)

const setupSteps = ref([
  { id: 'validate', label: 'Validating information', status: 'pending' },
  { id: 'account', label: 'Creating your account', status: 'pending' },
  { id: 'platform', label: 'Setting up your platform', status: 'pending' },
  { id: 'subscription', label: 'Configuring subscription', status: 'pending' },
  { id: 'email', label: 'Sending verification email', status: 'pending' },
])

const generateSubdomain = (name) => {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, '')
    .replace(/\s+/g, '-')
    .substring(0, 20)
}

const updateProgress = (newProgress, message, stepIndex) => {
  progress.value = newProgress
  currentMessage.value = message

  setupSteps.value = setupSteps.value.map((step, idx) => ({
    ...step,
    status: idx < stepIndex
      ? 'completed'
      : idx === stepIndex
      ? 'in-progress'
      : 'pending'
  }))
}


const pollJobStatus = async () => {
  
  if (!props.data.JobId) return

  const checkStatus = async () => {
    try {
      const response = await fetch(`/onboarding/status/${props.data.JobId}`)
      const data = await response.json()

      progress.value = data.progress
      currentMessage.value = data.message

      // Update step statuses based on progress
      const stepIndex = Math.floor((data.progress / 100) * setupSteps.value.length)
      setupSteps.value = setupSteps.value.map((step, idx) => ({
        ...step,
        status: idx < stepIndex
          ? 'completed'
          : idx === stepIndex
          ? 'in-progress'
          : 'pending'
      }))

      if (data.status === 'completed') {
        setupSteps.value = setupSteps.value.map(step => ({ ...step, status: 'completed' }))
        subdomain.value = generateSubdomain(props.data.schoolName) + '.' + mainDoamin
        status.value = 'success'
      } else if (data.status === 'failed') {
        errorMessage.value = data.error || data.message
        status.value = 'error'
        setupSteps.value = setupSteps.value.map((step, idx) => 
          idx === stepIndex ? { ...step, status: 'error' } : step
        )
      } else {
        // Continue polling
        setTimeout(checkStatus, 1000)
      }
    } catch (error) {
      console.error('Error polling status:', error)
      errorMessage.value = 'Failed to check setup status'
      status.value = 'error'
    }
  }

  checkStatus()
}

const copySubdomain = async () => {
  try {
    await navigator.clipboard.writeText(subdomain.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const goToEmailNotice = () => {
  router.visit('/verification/notice')
}

onMounted(() => {
  if (props.data.JobId) {
    pollJobStatus()
  } else {
    // Fallback simulation for demo
    simulateProcessing()
  }
})

// Fallback simulation
const simulateProcessing = async () => {
  const steps = [
    { progress: 10, stepIndex: 0, message: 'Preparing your workspace...' },
    { progress: 30, stepIndex: 1, message: 'Creating your account...' },
    { progress: 50, stepIndex: 2, message: 'Setting up your platform...' },
    { progress: 70, stepIndex: 3, message: 'Configuring subscription...' },
    { progress: 90, stepIndex: 4, message: 'Sending verification email...' },
    { progress: 100, stepIndex: -1, message: 'All set!' },
  ]

  for (let i = 0; i < steps.length; i++) {
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    updateProgress(steps[i].progress, steps[i].message, steps[i].stepIndex)

    if (steps[i].stepIndex === -1) {
      setupSteps.value = setupSteps.value.map(step => ({ ...step, status: 'completed' }))
    }
  }

  subdomain.value = generateSubdomain(props.data.schoolName) + mainDoamin
  status.value = 'success'
}
</script>

<template>
  <div class="flex flex-col items-center justify-center py-8">
    <!-- Animated Icon -->
    <transition mode="out-in" name="fade">
      <div v-if="status === 'processing'" key="processing" class="relative mb-8">
        <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center animate-pulse">
          <Loader2Icon class="w-12 h-12 text-primary animate-spin" />
        </div>
        <div class="absolute inset-0 rounded-full border-4 border-primary/30 animate-ping"></div>
      </div>

      <div v-else-if="status === 'success'" key="success" class="mb-8">
        <div class="w-24 h-24 rounded-full bg-primary flex items-center justify-center shadow-lg">
          <CheckIcon class="w-12 h-12 text-primary-foreground" />
        </div>
      </div>

      <div v-else-if="status === 'error'" key="error" class="mb-8">
        <div class="w-24 h-24 rounded-full bg-destructive/10 flex items-center justify-center">
          <AlertCircleIcon class="w-12 h-12 text-destructive" />
        </div>
      </div>
    </transition>

    <!-- Title -->
    <transition mode="out-in" name="fade">
      <h2 :key="currentMessage" class="text-2xl font-bold text-foreground mb-2 text-center">
        {{ status === 'success' ? 'Setup Complete!' : status === 'error' ? 'Setup Failed' : 'Setting Up...' }}
      </h2>
    </transition>

    <!-- Subtitle -->
    <p class="text-muted-foreground text-center mb-8 max-w-md">
      {{ currentMessage }}
    </p>

    <!-- Progress Bar -->
    <div v-if="status === 'processing'" class="w-full max-w-md mb-8">
      <div class="w-full bg-secondary rounded-full h-2">
        <div 
          class="bg-primary h-2 rounded-full transition-all duration-500"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
      <p class="text-sm text-muted-foreground text-center mt-2">
        {{ progress }}% complete
      </p>
    </div>

    <!-- Setup Timeline -->
    <div class="w-full max-w-sm mb-8">
      <div class="space-y-4">
        <div
          v-for="(step, index) in setupSteps"
          :key="step.id"
          class="flex items-center gap-4 transition-all duration-300"
          :style="{ transitionDelay: `${index * 100}ms` }"
        >
          <div class="flex-shrink-0">
            <div
              v-if="step.status === 'completed'"
              class="w-6 h-6 rounded-full bg-primary flex items-center justify-center"
            >
              <CheckIcon class="w-3.5 h-3.5 text-primary-foreground" />
            </div>
            <div
              v-else-if="step.status === 'in-progress'"
              class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center"
            >
              <Loader2Icon class="w-3.5 h-3.5 text-primary animate-spin" />
            </div>
            <div
              v-else-if="step.status === 'error'"
              class="w-6 h-6 rounded-full bg-destructive flex items-center justify-center"
            >
              <AlertCircleIcon class="w-3.5 h-3.5 text-destructive-foreground" />
            </div>
            <div v-else class="w-6 h-6 rounded-full bg-muted border-2 border-border"></div>
          </div>
          <span
            class="text-sm"
            :class="{
              'text-foreground': step.status === 'completed',
              'text-primary font-medium': step.status === 'in-progress',
              'text-destructive': step.status === 'error',
              'text-muted-foreground': step.status === 'pending'
            }"
          >
            {{ step.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Success State: Email Verification Notice -->
    <transition name="fade">
      <div v-if="status === 'success'" class="w-full max-w-md space-y-6">
        <!-- Email Sent Card -->
        <div class="p-6 bg-primary/5 border border-primary/20 rounded-lg">
          <div class="flex items-start gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
              <MailIcon class="w-6 h-6 text-primary" />
            </div>
            <div class="flex-1">
              <h3 class="font-semibold text-lg mb-1">Verify Your Email</h3>
              <p class="text-sm text-muted-foreground">
                We've sent a verification link to:
              </p>
              <p class="font-medium mt-1">{{ props.data.ownerEmail }}</p>
            </div>
          </div>

          <div class="space-y-2 text-sm">
            <div class="flex gap-2">
              <CheckCircle2Icon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
              <p class="text-muted-foreground">
                Check your inbox and spam folder
              </p>
            </div>
            <div class="flex gap-2">
              <CheckCircle2Icon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
              <p class="text-muted-foreground">
                Click the verification link
              </p>
            </div>
            <div class="flex gap-2">
              <CheckCircle2Icon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
              <p class="text-muted-foreground">
                Set your password and login
              </p>
            </div>
          </div>
        </div>

        <!-- School Info -->
        <div class="p-4 bg-muted/30 rounded-lg">
          <p class="text-sm text-muted-foreground mb-2">Your school platform:</p>
          <div class="flex items-center gap-2">
            <code class="text-base font-mono font-semibold text-primary flex-1">
              {{ subdomain }}
            </code>
            <button
              @click="copySubdomain"
              class="p-2 hover:bg-muted rounded-md transition-colors"
              :title="copied ? 'Copied!' : 'Copy URL'"
            >
              <CheckIcon v-if="copied" class="w-4 h-4 text-green-600" />
              <CopyIcon v-else class="w-4 h-4 text-muted-foreground" />
            </button>
          </div>
        </div>

        <!-- Note -->
        <div class="flex items-start gap-2 p-4 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-lg">
          <InfoIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
          <p class="text-sm text-blue-700 dark:text-blue-300">
            The verification link expires in 24 hours. If you don't receive it within a few minutes, check your spam folder.
          </p>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
          <button
            @click="goToEmailNotice"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
          >
            <span>Continue</span>
            <ArrowRightIcon class="w-4 h-4" />
          </button>
          
          <p class="text-xs text-center text-muted-foreground">
            You can close this page safely. We'll email you the next steps.
          </p>
        </div>
      </div>
    </transition>

    <!-- Error State: Retry Button -->
    <transition name="fade">
      <div v-if="status === 'error'" class="space-y-4 w-full max-w-md">
        <div class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
          <p class="text-sm text-destructive text-center">
            {{ errorMessage || 'Something went wrong during setup. Please try again.' }}
          </p>
        </div>
        
        <p class="text-sm text-muted-foreground text-center">
          Don't worry, your information has been saved.
        </p>
        
        <button
          @click="$emit('retry')"
          class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground"
        >
          <RefreshCwIcon class="w-4 h-4" />
          Try Again
        </button>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>