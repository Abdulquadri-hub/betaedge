<script setup>
import { ref, watch } from 'vue'
import { Upload, Edit2, Lock, CheckCircle, AlertCircle, XCircle } from 'lucide-vue-next'
import { generateSlugFromSchoolName, isSlugTruncated } from '@/utils/slugGenerator'
import { validateSlug } from '@/utils/slugValidator'

const UploadIcon = Upload
const EditIcon = Edit2
const LockIcon = Lock
const CheckIcon = CheckCircle
const WarningIcon = AlertCircle
const ErrorIcon = XCircle

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update'])

const logoPreview = ref(null)
const isEditingSlug = ref(false)
const slugValidationError = ref('')
const slugValidationChecking = ref(false)
const slugValidationState = ref('') // 'valid', 'reserved', 'taken', 'truncated'

const updateField = (field, value) => {
  emit('update', 'profile', { [field]: value })
}


const uploadLogoAndReturnUrl = async (file) => {
  try {
    const formData = new FormData()
    formData.append('logo', file)

    const response = await fetch('/api/onboarding/upload-logo', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to upload logo')
    }

    const data = await response.json()
    return data.url 
  } catch (error) {
    console.error('Logo upload error:', error)
    throw error
  }
}


const handleLogoChange = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  try {
    // Show preview immediately
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)

    const logoUrl = await uploadLogoAndReturnUrl(file)

    emit('update', 'profile', { logo: logoUrl })
  } catch (error) {
    console.error('Error handling logo:', error)
    logoPreview.value = null
  }
}

/**
 * Handle slug generation and validation
 */
const handleSlugGeneration = async () => {
  if (!props.data.school_name) {
    emit('update', 'profile', { slug: '' })
    slugValidationError.value = ''
    slugValidationState.value = ''
    return
  }

  const generatedSlug = generateSlugFromSchoolName(props.data.school_name)

  if (!generatedSlug) {
    emit('update', 'profile', { slug: '' })
    slugValidationError.value = ''
    slugValidationState.value = ''
    return
  }

  // Check if truncated
  const truncated = isSlugTruncated(props.data.school_name, generatedSlug)

  // Validate slug
  slugValidationChecking.value = true
  try {
    const result = await validateSlug(generatedSlug)

    if (result.valid) {
      emit('update', 'profile', { slug: generatedSlug })
      slugValidationError.value = ''
      slugValidationState.value = truncated ? 'truncated' : 'valid'
    } else {
      // Keep generated slug but show error
      emit('update', 'profile', { slug: generatedSlug })
      slugValidationError.value = result.errors[0] || 'Slug validation failed'
      slugValidationState.value = result.errors.some(e => e.toLowerCase().includes('reserved'))
        ? 'reserved'
        : 'taken'
    }
  } catch (error) {
    console.error('Slug validation error:', error)
    slugValidationState.value = 'valid' // Allow to proceed on error
  } finally {
    slugValidationChecking.value = false
  }
}

/**
 * Handle manual slug editing
 */
const handleSlugChange = async (newSlug) => {
  // Enforce max 10 characters
  const truncatedSlug = newSlug.substring(0, 10)
  emit('update', 'profile', { slug: truncatedSlug })

  // Validate the slug
  slugValidationChecking.value = true
  try {
    const result = await validateSlug(truncatedSlug)

    if (result.valid) {
      slugValidationError.value = ''
      slugValidationState.value = 'valid'
    } else {
      slugValidationError.value = result.errors[0] || 'Slug validation failed'
      slugValidationState.value = result.errors.some(e => e.toLowerCase().includes('reserved'))
        ? 'reserved'
        : 'taken'
    }
  } catch (error) {
    console.error('Slug validation error:', error)
    slugValidationState.value = 'valid'
  } finally {
    slugValidationChecking.value = false
  }
}

const toggleEditSlug = () => {
  isEditingSlug.value = !isEditingSlug.value
}

const getError = (field) => {
  return props.errors[`profile.${field}`] || props.errors[field]
}

// Watch for school name changes to auto-generate slug
watch(
  () => props.data.school_name,
  () => {
    if (!isEditingSlug.value) {
      handleSlugGeneration()
    }
  }
)

watch(
  () => props.data.logo,
  (logo) => {
    if (!logo) {
      logoPreview.value = null
      return
    }

    if (typeof logo === 'string') {
      logoPreview.value = logo
      return
    }

    if (logo instanceof File) {
      const reader = new FileReader()
      reader.onload = (e) => {
        logoPreview.value = e.target.result
      }
      reader.readAsDataURL(logo)
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="space-y-6">
    <!-- Logo Upload -->
    <div class="flex flex-col items-center gap-4 p-6 border-2 border-dashed rounded-xl bg-muted/30">
      <div class="w-24 h-24 rounded-full bg-muted flex items-center justify-center overflow-hidden">
        <img v-if="logoPreview" :src="logoPreview" alt="School logo" class="w-full h-full object-cover" />
        <UploadIcon v-else class="w-8 h-8 text-muted-foreground" />
      </div>
      <div class="text-center">
        <label for="logo" class="cursor-pointer text-primary hover:underline font-medium">
          Upload School Logo
        </label>
        <p class="text-sm text-muted-foreground mt-1">
          PNG, JPG up to 2MB
        </p>
        <input id="logo" type="file" @input="updateField('logo', $event.target.value)" accept="image/*"
          @change="handleLogoChange" class="hidden" />
      </div>
    </div>

    <!-- Basic Info -->
    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">School Name *</label>
        <input id="name" type="text" placeholder="e.g., Lagos International Academy" :value="data.school_name"
          @input="updateField('school_name', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('school_name') ? 'border-destructive' : '']" />
        <p v-if="getError('school_name')" class="text-sm text-destructive">
          {{ getError('school_name') }}
        </p>
      </div>

      <div class="space-y-2">
        <label for="schoolType" class="text-sm font-medium">School Type *</label>
        <select id="schoolType" :value="data.school_type" @change="updateField('school_type', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('school_type') ? 'border-destructive' : '']">
          <option value="">Select school type </option>
          <option value="primary">Primary</option>
          <option value="secondary">Secondary</option>
          <option value="university">University</option>
          <option value="bootcamp">Bootcamp</option>
          <option value="tutoring">Tutoring</option>
          <option value="marketplace">Marketplace</option>
        </select>
        <p v-if="getError('school_type')" class="text-sm text-destructive">
          {{ getError('school_type') }}
        </p>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">School Email *</label>
        <input id="email" type="email" placeholder="info@school.edu" :value="data.owner_email"
          @input="updateField('owner_email', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('owner_email') ? 'border-destructive' : '']" />
        <p v-if="getError('owner_email')" class="text-sm text-destructive">
          {{ getError('owner_email') }}
        </p>
      </div>

      <div class="space-y-2">
        <label for="established" class="text-sm font-medium">Year Established *</label>
        <input id="established" type="number" placeholder="e.g., 1990" :min="1800" :max="new Date().getFullYear()"
          :value="data.year_established" @input="updateField('year_established', $event.target.value)"
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
        <p v-if="getError('year_established')" class="text-sm text-destructive">
          {{ getError('year_established') }}
        </p>
      </div>
    </div>

    <!-- School Slug / Subdomain -->
    <div class="space-y-2">
      <label for="slug" class="text-sm font-medium">School Slug (Subdomain) *</label>
      <div class="flex gap-2 items-end">
        <div class="flex-1 space-y-1">
          <div class="flex items-center gap-2">
            <input 
              id="slug" 
              type="text" 
              placeholder="e.g., harvard" 
              maxlength="10"
              :value="data.slug"
              :disabled="!isEditingSlug"
              @input="handleSlugChange($event.target.value)"
              :class="[
                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                !isEditingSlug ? 'bg-muted cursor-not-allowed' : '',
                slugValidationState === 'valid' ? 'border-green-500' : '',
                slugValidationState === 'reserved' || slugValidationState === 'taken' ? 'border-destructive' : '',
                slugValidationState === 'truncated' ? 'border-yellow-500' : ''
              ]" 
            />
            <!-- Validation Icons -->
            <div v-if="!slugValidationChecking && data.slug" class="flex-shrink-0">
              <CheckIcon v-if="slugValidationState === 'valid'" class="w-5 h-5 text-green-500" />
              <WarningIcon v-else-if="slugValidationState === 'truncated'" class="w-5 h-5 text-yellow-500" />
              <ErrorIcon v-else-if="slugValidationState === 'reserved' || slugValidationState === 'taken'" class="w-5 h-5 text-destructive" />
            </div>
          </div>
          <!-- Slug validation errors -->
          <p v-if="slugValidationError" class="text-sm text-destructive">
            {{ slugValidationError }}
          </p>
          <p v-if="slugValidationState === 'truncated'" class="text-sm text-yellow-600">
            School name is long - slug truncated to 7 characters
          </p>
          <p v-if="!slugValidationError && slugValidationState === 'valid'" class="text-sm text-green-600">
            Slug is available
          </p>
          <p class="text-xs text-muted-foreground">
            This becomes your school's subdomain (e.g., harvard.betaedge.com)
          </p>
        </div>
      </div>
      <!-- Edit / Lock toggle -->
      <div class="flex gap-2">
        <button
          type="button"
          @click="toggleEditSlug"
          :class="[
            'inline-flex items-center gap-2 px-3 py-1 text-sm rounded-md border transition-colors cursor-pointer',
            isEditingSlug
              ? 'bg-blue-50 border-blue-300 text-blue-700 hover:bg-blue-100'
              : 'bg-primary/50 border-input text-primary-foreground hover:bg-primary/80'
          ]"
        >
          <LockIcon v-if="!isEditingSlug" class="w-4 h-4" />
          <EditIcon v-else class="w-4 h-4" />
          {{ isEditingSlug ? 'Lock Slug' : 'Edit Slug' }}
        </button>
      </div>
    </div>

    <!-- <div class="grid md:grid-cols-2 gap-4"> -->
    <!-- <div class="space-y-2">
        <label for="website" class="text-sm font-medium">Website (Optional)</label>
        <input
          id="website"
          type="url"
          placeholder="https://www.school.edu"
          :value="data.website"
          @input="updateField('website', $event.target.value)"
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        />
      </div>

      <div class="space-y-2">
        <label for="established" class="text-sm font-medium">Year Established</label>
        <input
          id="established"
          type="number"
          placeholder="e.g., 1990"
          :min="1800"
          :max="new Date().getFullYear()"
          :value="data.year_established"
          @input="updateField('year_established', $event.target.value)"
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        />
      </div> -->
    <!-- </div> -->

    <!-- Address -->
    <!-- <div class="space-y-2">
      <label for="address" class="text-sm font-medium">Street Address *</label>
      <input
        id="address"
        type="text"
        placeholder="123 Education Street"
        :value="data.address"
        @input="updateField('address', $event.target.value)"
        :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('address') ? 'border-destructive' : '']"
      />
      <p v-if="getError('address')" class="text-sm text-destructive">
        {{ getError('address') }}
      </p>
    </div> -->

    <div class="grid md:grid-cols-2 gap-4">
      <!-- <div class="space-y-2">
        <label for="city" class="text-sm font-medium">City </label>
        <input
          id="city"
          type="text"
          placeholder="Lagos"
          :value="data.city"
          @input="updateField('city', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('city') ? 'border-destructive' : '']"
        />
        <p v-if="getError('city')" class="text-sm text-destructive">
          {{ getError('city') }}
        </p>
      </div> -->


      <div class="space-y-2">
        <label for="country" class="text-sm font-medium">Country *</label>
        <select id="country" :value="data.country" @change="updateField('country', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('country') ? 'border-destructive' : '']">
          <option value="">Select country</option>
          <option value="Nigeria">Nigeria</option>
          <option value="Kenya">Kenya</option>
          <option value="Ghana">Ghana</option>
          <option value="South Africa">South Africa</option>
          <option value="Tanzania">Tanzania</option>
          <option value="Uganda">Uganda</option>
          <option value="Rwanda">Rwanda</option>
          <option value="Ethiopia">Ethiopia</option>
          <option value="Egypt">Egypt</option>
          <option value="Morocco">Morocco</option>
        </select>
        <p v-if="getError('country')" class="text-sm text-destructive">
          {{ getError('country') }}
        </p>
      </div>

      <div class="space-y-2">
        <label for="address" class="text-sm font-medium">Address *</label>
        <input id="address" type="text" placeholder="123 Education Street" :value="data.address"
          @input="updateField('address', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('address') ? 'border-destructive' : '']" />
        <p v-if="getError('address')" class="text-sm text-destructive">
          {{ getError('address') }}
        </p>
      </div>

    </div>

    <!-- Description -->
    <div class="space-y-2">
      <label for="description" class="text-sm font-medium">School Description</label>
      <textarea id="description" rows="4"
        placeholder="Tell us about your school's mission, values, and what makes it special..."
        :value="data.description" @input="updateField('description', $event.target.value)"
        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
      <p class="text-sm text-muted-foreground">
        This will be displayed on your public school profile
      </p>
    </div>
  </div>
</template>