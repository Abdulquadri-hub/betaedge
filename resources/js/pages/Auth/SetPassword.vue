<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { School, CheckCircle, Eye, EyeOff, ArrowRight } from 'lucide-vue-next'

const SchoolIcon = School
const CheckCircleIcon = CheckCircle
const EyeIcon = Eye
const EyeOffIcon = EyeOff
const ArrowRightIcon = ArrowRight

const props = defineProps({
  tenant: {
    type: Object,
    required: true
  },
  token: {
    type: String,
    required: true
  }
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const form = useForm({
  token: props.token,
  password: '',
  password_confirmation: ''
})

const passwordStrength = computed(() => {
  const password = form.password
  if (!password) return 0
  
  let strength = 0
  if (password.length >= 8) strength++
  if (password.length >= 12) strength++
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++
  if (/\d/.test(password)) strength++
  if (/[^a-zA-Z0-9]/.test(password)) strength++
  
  return Math.min(strength, 4)
})

const getStrengthColor = () => {
  const colors = {
    1: 'bg-red-500',
    2: 'bg-orange-500',
    3: 'bg-yellow-500',
    4: 'bg-green-500'
  }
  return colors[passwordStrength.value] || 'bg-muted'
}

const getStrengthText = () => {
  const texts = {
    1: 'Weak password',
    2: 'Fair password',
    3: 'Good password',
    4: 'Strong password'
  }
  return texts[passwordStrength.value] || ''
}

const getStrengthTextColor = () => {
  const colors = {
    1: 'text-red-500',
    2: 'text-orange-500',
    3: 'text-yellow-500',
    4: 'text-green-500'
  }
  return colors[passwordStrength.value] || 'text-muted-foreground'
}

const submitPassword = () => {
  form.post('/verification/set-password', {
    onSuccess: () => {
      // User will be redirected to their tenant dashboard
    }
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-primary/5 px-4">
    <div class="max-w-md w-full">
      <div class="bg-card rounded-2xl border shadow-lg p-8">
        <!-- Success Icon -->
        <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-6">
          <CheckCircleIcon class="w-10 h-10 text-green-600 dark:text-green-400" />
        </div>

        <!-- Title -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-foreground mb-2">
            Email Verified!
          </h1>
          <p class="text-muted-foreground">
            Welcome to {{ tenant.name }}
          </p>
        </div>

        <!-- School Info -->
        <div class="p-4 bg-primary/5 border border-primary/20 rounded-lg mb-6">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center">
              <SchoolIcon class="w-6 h-6 text-primary" />
            </div>
            <div>
              <p class="font-medium">{{ tenant.name }}</p>
              <p class="text-sm text-muted-foreground">{{ tenant.subdomain }}</p>
            </div>
          </div>
        </div>

        <!-- Set Password Form -->
        <form @submit.prevent="submitPassword" class="space-y-4">
          <div class="space-y-2">
            <label for="password" class="text-sm font-medium">
              Create Password *
            </label>
            <div class="relative">
              <input
                id="password"
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                placeholder="Enter your password"
                required
                minlength="8"
                :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm pr-10', form.errors.password ? 'border-destructive' : '']"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeOffIcon v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-sm text-destructive">
              {{ form.errors.password }}
            </p>
            <p class="text-xs text-muted-foreground">
              Must be at least 8 characters long
            </p>
          </div>

          <div class="space-y-2">
            <label for="password_confirmation" class="text-sm font-medium">
              Confirm Password *
            </label>
            <div class="relative">
              <input
                id="password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="form.password_confirmation"
                placeholder="Confirm your password"
                required
                :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm pr-10', form.errors.password_confirmation ? 'border-destructive' : '']"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <EyeIcon v-if="!showConfirmPassword" class="w-4 h-4" />
                <EyeOffIcon v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <!-- Password Strength Indicator -->
          <div v-if="form.password" class="space-y-2">
            <div class="flex gap-1">
              <div
                v-for="i in 4"
                :key="i"
                class="h-1 flex-1 rounded-full"
                :class="i <= passwordStrength ? getStrengthColor() : 'bg-muted'"
              ></div>
            </div>
            <p class="text-xs" :class="getStrengthTextColor()">
              {{ getStrengthText() }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
          >
            <span v-if="form.processing">Setting up...</span>
            <span v-else>Complete Setup & Login</span>
            <ArrowRightIcon class="w-4 h-4" />
          </button>
        </form>

        <!-- Info -->
        <p class="text-xs text-center text-muted-foreground mt-6">
          By continuing, you agree to our Terms of Service and Privacy Policy
        </p>
      </div>
    </div>
  </div>
</template>