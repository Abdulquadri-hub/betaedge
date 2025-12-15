<script setup lang="ts">
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
  // planInfo: {
  //   type: Object,
  //   required: true
  // },
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
  return props.plans.find(p => p.id === props.data.planInfo.plan_id)
})

const isYearly = computed(() => props.data.planInfo.billing_cycle === 'yearly')

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
      plan_id: props.data.planInfo.plan_id,
      billing_cycle: props.data.planInfo.billing_cycle,
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
          <p class="text-2xl font-bold">₦{{ price }}</p>
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
        <span v-else>Pay ₦{{ price }} with Paystack</span>
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