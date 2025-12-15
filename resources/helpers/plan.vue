<!-- SubscriptionPlanStep.vue -->
<template>
  <div class="space-y-6">
    <!-- Billing Toggle -->
    <div class="flex items-center justify-center gap-4 p-4 bg-muted/30 rounded-lg">
      <label
        :class="['cursor-pointer text-sm font-medium', !isYearly ? 'text-foreground' : 'text-muted-foreground']"
        @click="updateField('billing_cycle', 'monthly')"
      >
        Monthly
      </label>
      <button
        @click="toggleBillingCycle"
        :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', isYearly ? 'bg-primary' : 'bg-muted']"
      >
        <span
          :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', isYearly ? 'translate-x-6' : 'translate-x-1']"
        />
      </button>
      <div class="flex items-center gap-2">
        <label
          :class="['cursor-pointer text-sm font-medium', isYearly ? 'text-foreground' : 'text-muted-foreground']"
          @click="updateField('billing_cycle', 'yearly')"
        >
          Yearly
        </label>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
          Save 17%
        </span>
      </div>
    </div>

    <!-- Plan Cards -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="plan in plans"
        :key="plan.id"
        @click="selectPlan(plan.id)"
        :class="['relative p-5 rounded-xl border-2 cursor-pointer transition-all hover:scale-[1.02]', isSelected(plan.id) ? 'border-primary bg-primary/5 shadow-lg' : 'border-border hover:border-primary/50', plan.is_popular ? 'ring-2 ring-primary/20' : '']"
      >
        <span v-if="plan.is_popular" class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary text-primary-foreground">
          Most Popular
        </span>

        <div class="text-center mb-4">
          <div :class="['w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-3', isSelected(plan.id) ? 'bg-primary text-primary-foreground' : 'bg-muted']">
            <component :is="getPlanIcon(plan.slug)" class="w-5 h-5" />
          </div>
          <h3 class="text-lg font-bold">{{ plan.name }}</h3>
          <p class="text-xs text-muted-foreground mt-1">{{ plan.description }}</p>
        </div>

        <div class="text-center mb-4">
          <template v-if="plan.price_monthly === 0">
            <span class="text-3xl font-bold">Free</span>
          </template>
          <template v-else>
            <span class="text-3xl font-bold">{{ formatPrice(plan) }}</span>
            <span class="text-muted-foreground text-sm">{{ getPeriodText }}</span>
          </template>
        </div>

        <ul class="space-y-2">
          <li v-for="(feature, index) in getDisplayFeatures(plan)" :key="index" class="flex items-start gap-2 text-xs">
            <CheckIcon class="w-3.5 h-3.5 text-primary mt-0.5 flex-shrink-0" />
            <span>{{ feature }}</span>
          </li>
          <li v-if="plan.features && plan.features.length > 4" class="text-xs text-muted-foreground text-center">
            +{{ plan.features.length - 4 }} more features
          </li>
        </ul>

        <div v-if="isSelected(plan.id)" class="absolute top-3 right-3">
          <div class="w-5 h-5 rounded-full bg-primary flex items-center justify-center">
            <CheckIcon class="w-3 h-3 text-primary-foreground" />
          </div>
        </div>
      </div>
    </div>

    <p v-if="errors.plan_id" class="text-sm text-destructive text-center">
      {{ errors.plan_id }}
    </p>

    <!-- Selected Plan Summary -->
    <div v-if="data.plan_id" class="p-4 bg-primary/5 border border-primary/20 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <p class="font-medium">
            Selected: {{ getSelectedPlan?.name }} Plan
          </p>
          <p class="text-sm text-muted-foreground">
            Billed {{ isYearly ? 'annually' : 'monthly' }}
          </p>
        </div>
        <div class="text-right">
          <template v-if="getSelectedPlan?.price_monthly === 0">
            <p class="text-2xl font-bold">Free</p>
          </template>
          <template v-else>
            <p class="text-2xl font-bold">{{ formatPrice(getSelectedPlan) }}</p>
            <p class="text-sm text-muted-foreground">
              {{ getPeriodText }}
            </p>
          </template>
        </div>
      </div>
    </div>

    <!-- Trial Note -->
    <div class="text-center text-sm text-muted-foreground">
      <p>All paid plans include a 14-day free trial. No credit card required to start.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Check, Gift, Sparkles, Building2, Rocket } from 'lucide-vue-next'

const CheckIcon = Check

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  plans: {
    type: Array,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update'])

const isYearly = computed(() => props.data.billing_cycle === 'yearly')

const getSelectedPlan = computed(() => {
  return props.plans.find(p => p.id === props.data.plan_id)
})

const getPeriodText = computed(() => {
  return isYearly.value ? 'per year' : 'per month'
})

const getPlanIcon = (slug) => {
  const icons = {
    'free': Gift,
    'starter': Sparkles,
    'professional': Building2,
    'enterprise': Rocket
  }
  return icons[slug] || Building2
}

const isSelected = (planId) => {
  return props.data.plan_id === planId
}

const selectPlan = (planId) => {
  updateField('plan_id', planId)
}

const toggleBillingCycle = () => {
  const newCycle = isYearly.value ? 'monthly' : 'yearly'
  updateField('billing_cycle', newCycle)
}

const updateField = (field, value) => {
  emit('update', 'plan', { [field]: value })
}

const formatPrice = (plan) => {
  if (!plan) return '$0'
  const price = isYearly.value ? plan.price_yearly : plan.price_monthly
  return `$${price}`
}

const getDisplayFeatures = (plan) => {
  const features = []
  
  if (plan.max_students) {
    features.push(plan.max_students === -1 ? 'Unlimited students' : `Up to ${plan.max_students} students`)
  }
  
  if (plan.max_instructors) {
    features.push(plan.max_instructors === -1 ? 'Unlimited instructors' : `Up to ${plan.max_instructors} instructors`)
  }
  
  if (plan.features && Array.isArray(plan.features)) {
    features.push(...plan.features.slice(0, 4))
  }
  
  return features.slice(0, 4)
}
</script>

<!-- PaymentSetupStep.vue -->
<template>
  <div class="space-y-6">
    <!-- Plan Summary Card -->
    <div class="p-4 bg-primary/5 border border-primary/20 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <p class="font-semibold">{{ selectedPlan?.name }} Plan</p>
          <p class="text-sm text-muted-foreground">
            {{ isYearly ? 'Billed annually' : 'Billed monthly' }}
          </p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold">${{ price }}</p>
          <p class="text-sm text-muted-foreground">
            {{ isYearly ? 'per year' : 'per month' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Free Plan - No Payment Required -->
    <div v-if="isFree" class="flex flex-col items-center justify-center py-12 text-center">
      <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
        <CheckIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
      </div>
      <h3 class="text-xl font-semibold text-foreground mb-2">
        No Payment Required
      </h3>
      <p class="text-muted-foreground max-w-md">
        You've selected the Free plan. No credit card or payment information is needed.
        You can upgrade to a paid plan anytime from your dashboard.
      </p>
    </div>

    <!-- Paid Plans - Paystack Integration -->
    <div v-else class="space-y-6">
      <div class="text-center">
        <h3 class="text-lg font-semibold mb-2">Secure Payment via Paystack</h3>
        <p class="text-sm text-muted-foreground">
          Click the button below to complete your payment securely
        </p>
      </div>

      <button
        @click="initializePayment"
        :disabled="processing"
        class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 text-lg font-medium"
      >
        <CreditCardIcon class="w-5 h-5" />
        <span v-if="processing">Processing...</span>
        <span v-else>Pay ${{ price }} with Paystack</span>
      </button>

      <!-- Payment completed indicator -->
      <div v-if="paymentCompleted" class="p-4 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900 rounded-lg">
        <div class="flex items-center gap-3">
          <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
          <div>
            <p class="font-medium text-green-700 dark:text-green-300">Payment Successful</p>
            <p class="text-sm text-green-600 dark:text-green-400">
              Reference: {{ data.paystack_reference }}
            </p>
          </div>
        </div>
      </div>

      <!-- Security Note -->
      <div class="flex items-center gap-2 p-4 bg-muted/50 rounded-lg">
        <LockIcon class="w-4 h-4 text-muted-foreground flex-shrink-0" />
        <p class="text-sm text-muted-foreground">
          Your payment information is encrypted and securely processed by Paystack.
          We never store your card details.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Check, CreditCard, Lock, CheckCircle } from 'lucide-vue-next'

const CheckIcon = Check
const CreditCardIcon = CreditCard
const LockIcon = Lock
const CheckCircleIcon = CheckCircle

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  planInfo: {
    type: Object,
    required: true
  },
  plans: {
    type: Array,
    required: true
  },
  paystackPublicKey: {
    type: String,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update'])

const processing = ref(false)
const paymentCompleted = ref(false)

const selectedPlan = computed(() => {
  return props.plans.find(p => p.id === props.planInfo.plan_id)
})

const isYearly = computed(() => props.planInfo.billing_cycle === 'yearly')

const price = computed(() => {
  return isYearly.value ? selectedPlan.value?.price_yearly : selectedPlan.value?.price_monthly
})

const isFree = computed(() => selectedPlan.value?.slug === 'free')

const initializePayment = () => {
  if (processing.value || !window.PaystackPop) return

  processing.value = true

  const handler = window.PaystackPop.setup({
    key: props.paystackPublicKey,
    email: 'owner@school.com', // This should come from profile data
    amount: price.value * 100, // Convert to kobo/cents
    currency: 'NGN',
    ref: 'TXN_' + Math.floor((Math.random() * 1000000000) + 1),
    metadata: {
      plan_id: props.planInfo.plan_id,
      billing_cycle: props.planInfo.billing_cycle,
    },
    callback: function(response) {
      paymentCompleted.value = true
      processing.value = false
      
      emit('update', 'payment', {
        paystack_reference: response.reference
      })
    },
    onClose: function() {
      processing.value = false
    }
  })

  handler.openIframe()
}

onMounted(() => {
  // Load Paystack inline script
  if (!window.PaystackPop) {
    const script = document.createElement('script')
    script.src = 'https://js.paystack.co/v1/inline.js'
    document.head.appendChild(script)
  }
})
</script>

<!-- ReviewStep.vue -->
<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <h3 class="text-xl font-semibold text-foreground mb-2">
        Review Your Information
      </h3>
      <p class="text-muted-foreground">
        Please verify all details before completing setup
      </p>
    </div>

    <!-- School Profile Card -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
            <SchoolIcon class="w-5 h-5 text-primary" />
          </div>
          <h4 class="font-semibold text-lg">School Profile</h4>
        </div>
        <button
          @click="$emit('edit-step', 1)"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm border border-input bg-background hover:bg-accent hover:text-accent-foreground"
        >
          <Edit2Icon class="w-4 h-4" />
          Edit
        </button>
      </div>
      <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div class="flex items-start gap-2">
          <BuildingIcon class="w-4 h-4 text-muted-foreground mt-0.5" />
          <div>
            <p class="text-muted-foreground">School Name</p>
            <p class="font-medium">{{ data.profile.school_name || '—' }}</p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <MailIcon class="w-4 h-4 text-muted-foreground mt-0.5" />
          <div>
            <p class="text-muted-foreground">Email</p>
            <p class="font-medium">{{ data.profile.owner_email || '—' }}</p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <PhoneIcon class="w-4 h-4 text-muted-foreground mt-0.5" />
          <div>
            <p class="text-muted-foreground">Phone</p>
            <p class="font-medium">{{ data.profile.phone || '—' }}</p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <MapPinIcon class="w-4 h-4 text-muted-foreground mt-0.5" />
          <div>
            <p class="text-muted-foreground">Location</p>
            <p class="font-medium">
              {{ data.profile.city && data.profile.country ? `${data.profile.city}, ${data.profile.country}` : '—' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Plan Card -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
            <CrownIcon class="w-5 h-5 text-primary" />
          </div>
          <h4 class="font-semibold text-lg">Selected Plan</h4>
        </div>
        <button
          @click="$emit('edit-step', 2)"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm border border-input bg-background hover:bg-accent hover:text-accent-foreground"
        >
          <Edit2Icon class="w-4 h-4" />
          Edit
        </button>
      </div>
      <div class="flex items-center justify-between p-4 bg-primary/5 rounded-lg">
        <div>
          <div class="flex items-center gap-2">
            <p class="font-semibold text-lg">{{ selectedPlan?.name }} Plan</p>
            <span v-if="selectedPlan?.is_popular" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-primary text-primary-foreground">
              Popular
            </span>
          </div>
          <p class="text-sm text-muted-foreground">
            Billed {{ isYearly ? 'annually' : 'monthly' }}
          </p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold">${{ price }}</p>
          <p class="text-sm text-muted-foreground">
            {{ isFree ? 'forever' : isYearly ? 'per year' : 'per month' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Payment Info Card -->
    <div class="bg-card border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
            <CreditCardIcon class="w-5 h-5 text-primary" />
          </div>
          <h4 class="font-semibold text-lg">Payment Information</h4>
        </div>
        <button
          v-if="!isFree"
          @click="$emit('edit-step', 3)"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm border border-input bg-background hover:bg-accent hover:text-accent-foreground"
        >
          <Edit2Icon class="w-4 h-4" />
          Edit
        </button>
      </div>
      
      <div v-if="isFree" class="p-4 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-200 dark:border-green-900">
        <p class="text-green-700 dark:text-green-400 font-medium">
          No payment required for the Free plan
        </p>
      </div>
      <div v-else-if="data.payment.paystack_reference" class="p-4 bg-primary/5 rounded-lg">
        <div class="flex items-center gap-3">
          <CheckCircleIcon class="w-5 h-5 text-primary" />
          <div>
            <p class="font-medium">Payment Completed</p>
            <p class="text-sm text-muted-foreground">
              Reference: {{ data.payment.paystack_reference }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Terms Note -->
    <p class="text-xs text-center text-muted-foreground">
      By completing setup, you agree to our Terms of Service and Privacy Policy.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { School, Crown, CreditCard, Edit2, MapPin, Mail, Phone, Building, CheckCircle } from 'lucide-vue-next'

const SchoolIcon = School
const CrownIcon = Crown
const CreditCardIcon = CreditCard
const Edit2Icon = Edit2
const MapPinIcon = MapPin
const MailIcon = Mail
const PhoneIcon = Phone
const BuildingIcon = Building
const CheckCircleIcon = CheckCircle

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  plans: {
    type: Array,
    default: () => []
  }
})

defineEmits(['edit-step'])

const selectedPlan = computed(() => {
  return props.plans?.find(p => p.id === props.data.plan?.plan_id)
})

const isYearly = computed(() => props.data.plan?.billing_cycle === 'yearly')

const price = computed(() => {
  if (!selectedPlan.value) return 0
  return isYearly.value ? selectedPlan.value.price_yearly : selectedPlan.value.price_monthly
})

const isFree = computed(() => selectedPlan.value?.slug === 'free')
</script>

//processing
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
              <p class="font-medium mt-1">{{ ownerEmail }}</p>
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

<script setup>
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
  schoolName: {
    type: String,
    required: true
  },
  ownerEmail: {
    type: String,
    default: ''
  },
  jobId: {
    type: String,
    default: null
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
  if (!props.jobId) return

  const checkStatus = async () => {
    try {
      const response = await fetch(`/onboarding/status/${props.jobId}`)
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
        subdomain.value = generateSubdomain(props.schoolName) + '.educonnect.africa'
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
  if (props.jobId) {
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

  subdomain.value = generateSubdomain(props.schoolName) + '.educonnect.africa'
  status.value = 'success'
}
</script>

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