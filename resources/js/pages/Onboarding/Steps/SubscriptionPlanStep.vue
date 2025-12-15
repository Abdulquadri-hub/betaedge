<script setup lang="ts">
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

const percentage = '17%'

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
    'growth': Building2,
    'professional': Rocket
  }
  return icons[slug] || Building2
}

const isSelected = (planId) => {
  return props.data.plan_id === planId
}

// const selectPlan = (planId) => {
//   updateField('plan_id', planId)
//     console.log(props.data.billing_cycle);
//   console.log(props.data.plan_id);
// }

const selectPlan = (planId) => { 
  emit('update', 'plan', { 
    plan_id: planId, 
    billing_cycle: props.data.billing_cycle || 'monthly' 
  }) 
}


const toggleBillingCycle = () => {
  const newCycle = isYearly.value ? 'monthly' : 'yearly'
  updateField('billing_cycle', newCycle)
}

const updateField = (field, value) => {
  emit('update', 'plan', { 
    ...props.data,  
    [field]: value 
  })
}

const formatPrice = (plan) => {
  if (!plan) return '₦0'
  
  const price = isYearly.value ? plan.price_yearly : plan.price_monthly
  

  const formattedPrice = Math.floor(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  
  return `₦${formattedPrice}`
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
          Save {{ percentage}}%
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
            <span class="text-muted-foreground text-sm"></span>
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