<script setup>
import { ref } from 'vue'
import { Eye, EyeOff, Loader2, Mail, Lock, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { toast } from 'vue-sonner'


// Laravel Inertia.js Integration:
// import { useForm, router } from '@inertiajs/vue3'
// 
// const form = useForm({
//   email: '',
//   password: '',
//   remember: false,
//   role: props.selectedRole.value
// })
// 
// const submit = () => {
//   form.post('/login', {
//     onSuccess: (response) => {
//       // Check role and redirect accordingly
//       if (form.role === 'instructor' && response.props.user.schools.length > 1) {
//         router.visit('/auth/select-school')
//       } else {
//         const dashboardRoutes = {
//           student: '/student/dashboard',
//           parent: '/parent/dashboard',
//           instructor: '/instructor/dashboard',
//           school_owner: '/school/dashboard'
//         }
//         router.visit(dashboardRoutes[form.role])
//       }
//     }
//   })
// }

const props = defineProps({
  selectedRole: {
    type: Object,
    required: true
  },
  name: {
    type: String,
    default: 'BetaEdge'
  }
})

const emit = defineEmits(['changeRole'])

const formData = ref({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)
const isLoading = ref(false)
const errors = ref({})

const validateForm = () => {
  const newErrors = {}
  
  if (!formData.value.email) {
    newErrors.email = 'Email is required'
  } else if (!/\S+@\S+\.\S+/.test(formData.value.email)) {
    newErrors.email = 'Please enter a valid email'
  }
  
  if (!formData.value.password) {
    newErrors.password = 'Password is required'
  } else if (formData.value.password.length < 6) {
    newErrors.password = 'Password must be at least 6 characters'
  }
  
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}


const handleSubmit = async () => {
  if (!validateForm()) return

  isLoading.value = true
  errors.value = {}

  try {
    // Mock API call - Replace with actual backend call
    // Laravel Inertia.js: Use form.post('/login', data) instead
    
    const loginPayload = {
      email: formData.value.email,
      password: formData.value.password,
      remember: formData.value.remember,
      role: props.selectedRole.value
    }

      toast('Welcome back!', {
    description: 'You have successfully logged in.',
  })

    console.log('Login payload:', loginPayload)

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Mock response
    const mockResponse = {
      user: {
        id: 1,
        name: 'John Doe',
        email: formData.value.email,
        role: props.selectedRole.value,
        schools: props.selectedRole.value === 'instructor' 
          ? [
              { id: 1, name: 'Elevate Academy', subdomain: 'elevate.betaedge.test' },
              { id: 2, name: 'Beta School', subdomain: 'beta.betaedge.test' }
            ]
          : []
      },
      subdomain: 'elevate.betaedge.test',
      token: 'mock-jwt-token'
    }

    // Role-based routing
    if (props.selectedRole.value === 'instructor' && mockResponse.user.schools.length > 1) {
      // Redirect to school selector for instructors with multiple schools
      console.log('Redirecting to: /auth/select-school')
      window.location.href = '/auth/select-school'
    } else {
      const dashboardRoutes = {
        student: '/student/dashboard',
        parent: '/parent/dashboard',
        instructor: '/instructor/dashboard',
        school_owner: '/school/dashboard'
      }
      console.log('Redirecting to:', dashboardRoutes[props.selectedRole.value])
      window.location.href = dashboardRoutes[props.selectedRole.value]
    }

  } catch (error) {
    console.log(error); 
    errors.value.general = 'Invalid credentials. Please try again.'
    toast.error('Error',{
      title: 'Error',
      description: 'Login failed',
      variant: 'destructive',
    })
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Change Role Button -->
    <button
      type="button"
      @click="emit('changeRole')"
      class="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1 hover:cursor-pointer"
    >
      <ArrowLeft class="w-4 h-4" /> Change role
    </button>

    <!-- Selected Role Display -->
    <div class="flex items-center gap-3 p-3 rounded-lg bg-primary/5 border border-primary/20">
      <component :is="selectedRole.icon" class="h-5 w-5 text-primary" />
      <div>
        <p class="font-medium text-sm">{{ selectedRole.label }}</p>
        <p class="text-xs text-muted-foreground">{{ selectedRole.description }}</p>
      </div>
    </div>

    <!-- General Error -->
    <div v-if="errors.general" class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
      {{ errors.general }}
    </div>

    <!-- Email Field -->
    <div class="space-y-2">
      <Label for="email">Email address</Label>
      <div class="relative">
        <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          id="email"
          type="email"
          placeholder="you@example.com"
          v-model="formData.email"
          :class="['pl-10', errors.email ? 'border-destructive' : '']"
        />
      </div>
      <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
    </div>

    <!-- Password Field -->
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <Label for="password">Password</Label>
        <a
          href="/auth/forgot-password"
          class="text-sm text-primary hover:underline"
        >
          Forgot password?
        </a>
      </div>
      <div class="relative">
        <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          id="password"
          :type="showPassword ? 'text' : 'password'"
          placeholder="••••••••"
          v-model="formData.password"
          :class="['pl-10 pr-10', errors.password ? 'border-destructive' : '']"
        />
        <button
          type="button"
          @click="showPassword = !showPassword"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
        >
          <EyeOff v-if="showPassword" class="h-4 w-4" />
          <Eye v-else class="h-4 w-4" />
        </button>
      </div>
      <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
    </div>

    <!-- Remember Me -->
    <div class="flex items-center space-x-2">
      <Checkbox
        id="remember"
        v-model:checked="formData.remember"
      />
      <Label for="remember" class="text-sm font-normal cursor-pointer">
        Remember me for 30 days
      </Label>
    </div>

    <!-- Submit Button -->
    <Button type="submit" class="w-full" size="lg" :disabled="isLoading">
      <template v-if="isLoading">
        <Loader2 class="mr-2 h-4 w-4 animate-spin" />
        Signing in...
      </template>
      <template v-else>
        Sign in
      </template>
    </Button>

    <!-- <p class="text-center text-sm text-muted-foreground">
      Don't have an account?
      <a href="/auth/register" class="text-primary hover:underline font-medium">
        Create one
      </a>
    </p> -->
  </form>
</template>