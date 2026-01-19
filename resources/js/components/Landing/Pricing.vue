<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Check, Star } from 'lucide-vue-next'

const CheckIcon = Check
const StarIcon = Star

defineProps({
  plans: {
    type: Array,
    default: () => []
  }
})

const formatPrice = (plan) => {
  if (plan.price_monthly === 0) return '₦0'
  return `₦${plan.price_monthly.toLocaleString()}`
}

const getDisplayFeatures = (plan) => {
  const features = []
  if (plan.has_custom_domain) features.push('Custom domain')
  if (plan.has_analytics) features.push('Advanced analytics')
  if (plan.has_api_access) features.push('API access')
  return features.slice(0, 3)
}
</script>

<template>
  <section id="pricing" class="py-20 md:py-32 gradient-surface">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
          Simple Pricing
        </span>
        <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-6">
          Choose Your
          <span class="text-gradient-primary">Growth Path</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Start free, upgrade as you grow. All plans include our core features 
          with no hidden fees.
        </p>
      </div>

      <!-- Pricing Cards -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
        <div
          v-for="plan in plans"
          :key="plan.id"
          :class="['relative p-6 rounded-2xl bg-card border transition-all duration-300 hover:shadow-lg', plan.is_popular ? 'border-primary shadow-lg scale-105 lg:scale-110' : 'border-border hover:border-primary/30']"
        >
          <div v-if="plan.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
            <div class="flex items-center gap-1 px-3 py-1 rounded-full gradient-hero text-primary-foreground text-xs font-semibold">
              <StarIcon class="w-3 h-3" />
              Most Popular
            </div>
          </div>

          <div class="text-center mb-6">
            <h3 class="font-display font-bold text-xl text-foreground mb-2">
              {{ plan.name }}
            </h3>
            <p class="text-sm text-muted-foreground mb-4">
              {{ plan.description }}
            </p>
            <div class="flex items-baseline justify-center gap-1">
              <span class="font-display text-4xl font-bold text-foreground">
                {{ formatPrice(plan) }}
              </span>
              <span class="text-sm text-muted-foreground">
                /{{ plan.price_monthly === 0 ? 'forever' : 'month' }}
              </span>
            </div>
          </div>

          <ul class="space-y-3 mb-6">
            <li class="flex items-start gap-2">
              <CheckIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <span class="text-sm text-muted-foreground">
                Up to {{ plan.max_students === -1 ? 'unlimited' : plan.max_students }} students
              </span>
            </li>
            <li class="flex items-start gap-2">
              <CheckIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <span class="text-sm text-muted-foreground">
                {{ plan.max_courses === -1 ? 'Unlimited' : plan.max_courses }} courses
              </span>
            </li>
            <li class="flex items-start gap-2">
              <CheckIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <span class="text-sm text-muted-foreground">
                {{ plan.max_instructors === -1 ? 'Unlimited' : `Up to ${plan.max_instructors}` }} instructors
              </span>
            </li>
            <li v-for="feature in getDisplayFeatures(plan)" :key="feature" class="flex items-start gap-2">
              <CheckIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <span class="text-sm text-muted-foreground">
                {{ feature }}
              </span>
            </li>
          </ul>

          <Link href="/onboarding">
            <button :class="['w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-10 px-4 py-2', plan.is_popular ? 'gradient-hero text-primary-foreground' : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground']">
              {{ plan.price_monthly === 0 ? 'Get Started' : 'Start Free Trial' }}
            </button>
          </Link>
        </div>
      </div>
    </div>
  </section>
</template>