<script setup>
import { ref, watch } from 'vue';
import { Eye, EyeOff, Mail, Phone, Lock, User, Calendar } from 'lucide-vue-next'
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
            dateOfBirth: '',
            password: ''
        })
    }
})

/**
 * ref
 */
const formData = ref({
    name: props.data.name || '',
    email: props.data.email || '',
    phone: props.data.phone || '',
    dateOfBirth: props.data.dateOfBirth || '',
    password: props.data.password || '',
    confirmPassword: ''
})
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const errors = ref({})

/**
 *  emit the update enrollment data for adult
 */
const emit = defineEmits(['update'])

/**
 * Validation
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
    case 'dateOfBirth':
      if (!value) {
        return 'Date of birth is required'
      }
      // Check if 18 or older
      const dob = new Date(value)
      const today = new Date()
      const age = today.getFullYear() - dob.getFullYear()
      const monthDiff = today.getMonth() - dob.getMonth()
      
      if (age < 18 || (age === 18 && monthDiff < 0)) {
        return 'You must be 18 years or older to register as an adult student'
      }
      if (age > 120) {
        return 'Please enter a valid date of birth'
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
 * HandleBlur
 */
const handleBlur = (field) => {
    const error = validateField(field, formData.value[field])

    if (error) {
        errors.value[field] = error
    } else {
        delete errors.value[field]

        /**
         * Emit update if no errors and all requrired fields filled
         */
        if (
            Object.keys(errors.value).length === 0 &&
            formData.value.email &&
            formData.value.email &&
            formData.value.phone &&
            formData.value.password &&
            formData.value.dateOfBirth &&
            formData.value.confirmPassword
        ) {
            emit('update', {
                name: formData.value.name,
                email: formData.value.email,
                phone: formData.value.phone,
                dateOfBirth: formData.value.dateOfBirth,
                password: formData.value.password
            })
        }
    }
}

/**
 * Handle date change (DateOfBirth)
 */
const handleDateChange = (event) => {
    formData.value.dateOfBirth = event.target.value
    handleBlur('dateOfBirth')
}

/**
 * watch for password change to revalidate confirm password
*/
watch(() => formData.value.password, () => {
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
      <h2 class="text-2xl font-bold">Your Information</h2>
      <p class="text-muted-foreground mt-2">
        Create your student account
      </p>
    </div>

    <div class="space-y-4">
      <!-- Full Name -->
      <div class="space-y-2">
        <Label for="adult-name">Full Name</Label>
        <div class="relative">
          <User class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="adult-name"
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
        <Label for="adult-email">Email Address</Label>
        <div class="relative">
          <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="adult-email"
            v-model="formData.email"
            type="email"
            placeholder="student@example.com"
            class="pl-10"
            :class="{ 'border-destructive': errors.email }"
            @blur="handleBlur('email')"
          />
        </div>
        <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Phone -->
        <div class="space-y-2">
          <Label for="adult-phone">Phone Number</Label>
          <div class="relative">
            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              id="adult-phone"
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

        <!-- Date of Birth -->
        <div class="space-y-2">
          <Label for="adult-dob">Date of Birth</Label>
          <div class="relative">
            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              id="adult-dob"
              v-model="formData.dateOfBirth"
              type="date"
              class="pl-10"
              :class="{ 'border-destructive': errors.dateOfBirth }"
              @change="handleDateChange"
            />
          </div>
          <p v-if="errors.dateOfBirth" class="text-sm text-destructive">{{ errors.dateOfBirth }}</p>
        </div>
      </div>

      <!-- Password -->
      <div class="space-y-2">
        <Label for="adult-password">Password</Label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="adult-password"
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
        <Label for="adult-confirm-password">Confirm Password</Label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="adult-confirm-password"
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