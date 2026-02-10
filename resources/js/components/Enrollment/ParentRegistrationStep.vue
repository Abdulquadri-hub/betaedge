<script setup>
import { ref, watch } from 'vue'
import { Eye, EyeOff, Mail, Phone, Lock, User } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

/**
 * Props
 */
const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            name: '',
            email: '',
            phone: '',
            password: ''
        })
    }
})

/**
 * Emit update enrollment  event with the data
 */
const emit = defineEmits(['update'])


/**
 *  ref
 */
const formData = ref({
    name: props.data.nam || '',
    email: props.data.email || '',
    phone: props.data.phone || '',
    password: props.data.password || '',
    confirmPassword: ''
})
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const errors = ref({})


/**
 * Validate Form field
 */
const validateField = (field, value) => {
  switch (field) {
    case 'name':
      if (!value || value.length < 2) {
        return 'Name must be at least 2 characters'
      }
      break
    case 'email':
      if (!value) {
        return 'Email is required'
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return 'Please enter a valid email address'
      }
      break
    case 'phone':
      if (!value) {
        return 'Phone number is required'
      }
      if (!/^(\+234|0)[0-9]{10}$/.test(value.replace(/\s/g, ''))) {
        return 'Please enter a valid Nigerian phone number'
      }
      break
    case 'password':
      if (!value) {
        return 'Password is required'
      }
      if (value.length < 8) {
        return 'Password must be at least 8 characters'
      }
      if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
        return 'Password must contain uppercase, lowercase, and number'
      }
      break
    case 'confirmPassword':
      if (value !== formData.value.password) {
        return 'Passwords do not match'
      }
      break
  }
  return null
}

/**
 * HandleBlur -> This handle filed blur for auto save
 */
const handleBlur = (field) => {
  const error = validateField(field, formData.value[field])

  if (error) {
    errors.value[field] = error
  } else {
    delete errors.value[field]
    
    // Emit update if no errors
    if (Object.keys(errors.value).length === 0) {
      emit('update', {
        name: formData.value.name,
        email: formData.value.email,
        phone: formData.value.phone,
        password: formData.value.password
      })
    }
  }
}


/**
 * Watch for changes and validate
 */
watch(() => formData.value.password, () => {
  // Revalidate confirm password when password changes
  if (formData.value.confirmPassword) {
    const error = validateField('confirmPassword', formData.value.confirmPassword)
    if (error) {
      errors.value.confirmPassword = error
    } else {
      delete errors.value.confirmPassword
    }
  }
})

</script>

<template>
  <div>
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold">Parent/Guardian Information</h2>
      <p class="text-muted-foreground mt-2">
        Create your account to manage your child's enrollment
      </p>
    </div>

    <div class="space-y-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Full Name -->
      <div class="space-y-2 lg:col-span-2">
        <Label for="parent-name">Full Name</Label>
        <div class="relative">
          <User class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="parent-name"
            v-model="formData.name"
            placeholder="Enter your full name"
            class="pl-10"
            :class="{ 'border-destructive': errors.name }"
            @blur="handleBlur('name')"
          />
        </div>
        <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
      </div>

      <!-- Email -->
      <div class="space-y-2">
        <Label for="parent-email">Email Address</Label>
        <div class="relative">
          <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="parent-email"
            v-model="formData.email"
            type="email"
            placeholder="parent@example.com"
            class="pl-10"
            :class="{ 'border-destructive': errors.email }"
            @blur="handleBlur('email')"
          />
        </div>
        <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
      </div>

      <!-- Phone -->
      <div class="space-y-2">
        <Label for="parent-phone">Phone Number</Label>
        <div class="relative">
          <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="parent-phone"
            v-model="formData.phone"
            type="tel"
            placeholder="+234 801 234 5678"
            class="pl-10"
            :class="{ 'border-destructive': errors.phone }"
            @blur="handleBlur('phone')"
          />
        </div>
        <p v-if="errors.phone" class="text-sm text-destructive">{{ errors.phone }}</p>
      </div>

      <!-- Password -->
      <div class="space-y-2">
        <Label for="parent-password">Password</Label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="parent-password"
            v-model="formData.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Create a password"
            class="pl-10 pr-10"
            :class="{ 'border-destructive': errors.password }"
            @blur="handleBlur('password')"
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
        <p v-else class="text-xs text-muted-foreground">
          Must be at least 8 characters with uppercase, lowercase, and number
        </p>
      </div>

      <!-- Confirm Password -->
      <div class="space-y-2">
        <Label for="parent-confirm-password">Confirm Password</Label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="parent-confirm-password"
            v-model="formData.confirmPassword"
            :type="showConfirmPassword ? 'text' : 'password'"
            placeholder="Confirm your password"
            class="pl-10 pr-10"
            :class="{ 'border-destructive': errors.confirmPassword }"
            @blur="handleBlur('confirmPassword')"
          />
          <button
            type="button"
            @click="showConfirmPassword = !showConfirmPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
          >
            <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
          </button>
        </div>
        <p v-if="errors.confirmPassword" class="text-sm text-destructive">{{ errors.confirmPassword }}</p>
      </div>
    </div>
  </div>
</template>