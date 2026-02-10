<script setup>
import { ref } from 'vue'
import { User, Calendar } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'


const props = defineProps({
  data: {
    type: Object,
    default: () => ({
      name: '',
      dateOfBirth: '',
      gender: '',
      grade: ''
    })
  }
})

const emit = defineEmits(['update'])

const formData = ref({
  name: props.data.name || '',
  dateOfBirth: props.data.dateOfBirth || '',
  gender: props.data.gender || '',
  grade: props.data.grade || ''
})

const errors = ref({})

const grades = [
  'Nursery 1',
  'Nursery 2',
  'Primary 1',
  'Primary 2',
  'Primary 3',
  'Primary 4',
  'Primary 5',
  'Primary 6',
  'JSS 1',
  'JSS 2',
  'JSS 3',
  'SSS 1',
  'SSS 2',
  'SSS 3',
]

// Validate field
const validateField = (field, value) => {
  switch (field) {
    case 'name':
      if (!value || value.length < 2) {
        return 'Name must be at least 2 characters'
      }
      break
    case 'dateOfBirth':
      if (!value) {
        return 'Date of birth is required'
      }
      // Check if under 18
      const dob = new Date(value)
      const today = new Date()
      const age = today.getFullYear() - dob.getFullYear()
      if (age >= 18) {
        return 'Child must be under 18 years old'
      }
      if (age < 2) {
        return 'Please enter a valid date of birth'
      }
      break
    case 'gender':
      if (!value) {
        return 'Please select gender'
      }
      break
    case 'grade':
      if (!value) {
        return 'Please select grade level'
      }
      break
  }
  return null
}

// Handle field change
const handleChange = (field, value) => {
  formData.value[field] = value
  
  const error = validateField(field, value)
  
  if (error) {
    errors.value[field] = error
  } else {
    delete errors.value[field]
    
    // Emit update if all required fields filled and no errors
    if (
      formData.value.name &&
      formData.value.dateOfBirth &&
      formData.value.gender &&
      formData.value.grade &&
      Object.keys(errors.value).length === 0
    ) {
      emit('update', {
        name: formData.value.name,
        dateOfBirth: formData.value.dateOfBirth,
        gender: formData.value.gender,
        grade: formData.value.grade
      })
    }
  }
}

// Handle blur for text inputs
const handleBlur = (field) => {
  handleChange(field, formData.value[field])
}
</script>
<template>
  <div class="border-t pt-6 mt-6">
    <div class="text-center mb-6">
      <h3 class="text-xl font-semibold">Child's Information</h3>
      <p class="text-muted-foreground mt-1 text-sm">
        Enter details about your child
      </p>
    </div>

    <div class="space-y-4">
      <!-- Child's Full Name -->
      <div class="space-y-2">
        <Label for="child-name">Child's Full Name</Label>
        <div class="relative">
          <User class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            id="child-name"
            v-model="formData.name"
            placeholder="Enter child's full name"
            class="pl-10"
            :class="{ 'border-destructive': errors.name }"
            @blur="handleBlur('name')"
          />
        </div>
        <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Date of Birth -->
        <div class="space-y-2">
          <Label for="child-dob">Date of Birth</Label>
          <div class="relative">
            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              id="child-dob"
              v-model="formData.dateOfBirth"
              type="date"
              class="pl-10"
              :class="{ 'border-destructive': errors.dateOfBirth }"
              @change="handleChange('dateOfBirth', $event.target.value)"
            />
          </div>
          <p v-if="errors.dateOfBirth" class="text-sm text-destructive">{{ errors.dateOfBirth }}</p>
        </div>

        <!-- Gender -->
        <div class="space-y-2">
          <Label for="child-gender">Gender</Label>
          <Select
            v-model="formData.gender"
            @update:model-value="(value) => handleChange('gender', value)"
          >
            <SelectTrigger
              id="child-gender"
              :class="{ 'border-destructive': errors.gender }"
            >
              <SelectValue placeholder="Select gender" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="male">Male</SelectItem>
              <SelectItem value="female">Female</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="errors.gender" class="text-sm text-destructive">{{ errors.gender }}</p>
        </div>
      </div>

      <!-- Current Grade Level -->
      <div class="space-y-2">
        <Label for="child-grade">Current Grade Level</Label>
        <Select
          v-model="formData.grade"
          @update:model-value="(value) => handleChange('grade', value)"
        >
          <SelectTrigger
            id="child-grade"
            :class="{ 'border-destructive': errors.grade }"
          >
            <SelectValue placeholder="Select grade level" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="grade in grades" :key="grade" :value="grade">
              {{ grade }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p v-if="errors.grade" class="text-sm text-destructive">{{ errors.grade }}</p>
      </div>
    </div>
  </div>
</template>