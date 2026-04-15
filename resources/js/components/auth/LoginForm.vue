<script setup>
import { ref } from 'vue'
import { Eye, EyeOff, Loader2, Mail, Lock } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { toast } from 'vue-sonner'
import { useForm } from '@inertiajs/vue3'

defineProps({
  name: {
    type: String,
    default: 'BetaEdge'
  }
})

// Form state using Inertia
const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const handleSubmit = () => {
  form.post(route('login.initiate'), {
    onSuccess: (response) => {
      console.log(response);
      toast.success('Welcome back!', {
        description: 'You have successfully logged in.'
      })
      // Backend handles redirect based on tenant count
      // Either to tenant subdomain or school selector
    },
    onError: () => {
      toast.error('Error', {
        description: form.errors.email || form.errors.password || 'Login failed. Please try again.'
      })
    }
  })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Email Field -->
    <div class="space-y-2">
      <Label for="email">Email address</Label>
      <div class="relative">
        <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          id="email"
          type="email"
          placeholder="you@example.com"
          v-model="form.email"
          :class="['pl-10', form.errors.email ? 'border-destructive' : '']"
        />
      </div>
      <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
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
          v-model="form.password"
          :class="['pl-10 pr-10', form.errors.password ? 'border-destructive' : '']"
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
      <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
    </div>

    <!-- Remember Me -->
    <div class="flex items-center space-x-2">
      <Checkbox
        id="remember"
        v-model:checked="form.remember"
      />
      <Label for="remember" class="text-sm font-normal cursor-pointer">
        Remember me for 30 days
      </Label>
    </div>

    <!-- Submit Button -->
    <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
      <template v-if="form.processing">
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