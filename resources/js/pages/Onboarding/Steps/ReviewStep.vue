<script setup lang="ts">
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