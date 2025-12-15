<template>
  <div class="space-y-6">
    <!-- Logo Upload -->
    <div class="flex flex-col items-center gap-4 p-6 border-2 border-dashed rounded-xl bg-muted/30">
      <div class="w-24 h-24 rounded-full bg-muted flex items-center justify-center overflow-hidden">
        <img
          v-if="logoPreview"
          :src="logoPreview"
          alt="School logo"
          class="w-full h-full object-cover"
        />
        <UploadIcon v-else class="w-8 h-8 text-muted-foreground" />
      </div>
      <div class="text-center">
        <label
          for="logo"
          class="cursor-pointer text-primary hover:underline font-medium"
        >
          Upload School Logo
        </label>
        <p class="text-sm text-muted-foreground mt-1">
          PNG, JPG up to 2MB
        </p>
        <input
          id="logo"
          type="file"
          accept="image/*"
          @change="handleLogoChange"
          class="hidden"
        />
      </div>
    </div>

    <!-- Basic Info -->
    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">School Name *</label>
        <input
          id="name"
          type="text"
          placeholder="e.g., Lagos International Academy"
          :value="data.school_name"
          @input="updateField('school_name', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('school_name') ? 'border-destructive' : '']"
        />
        <p v-if="getError('school_name')" class="text-sm text-destructive">
          {{ getError('school_name') }}
        </p>
      </div>

      <div class="space-y-2">
        <label for="schoolType" class="text-sm font-medium">School Type *</label>
        <select
          id="schoolType"
          :value="data.school_type"
          @change="updateField('school_type', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('school_type') ? 'border-destructive' : '']"
        >
          <option value="">Select school type</option>
          <option value="primary">Primary School</option>
          <option value="secondary">Secondary School</option>
          <option value="combined">Combined (Primary & Secondary)</option>
          <option value="vocational">Vocational/Technical</option>
          <option value="university">University/College</option>
        </select>
        <p v-if="getError('school_type')" class="text-sm text-destructive">
          {{ getError('school_type') }}
        </p>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">School Email *</label>
        <input
          id="email"
          type="email"
          placeholder="info@school.edu"
          :value="data.owner_email"
          @input="updateField('owner_email', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('owner_email') ? 'border-destructive' : '']"
        />
        <p v-if="getError('owner_email')" class="text-sm text-destructive">
          {{ getError('owner_email') }}
        </p>
      </div>

      <div class="space-y-2">
        <label for="phone" class="text-sm font-medium">Phone Number *</label>
        <input
          id="phone"
          type="tel"
          placeholder="+234 800 000 0000"
          :value="data.phone"
          @input="updateField('phone', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('phone') ? 'border-destructive' : '']"
        />
        <p v-if="getError('phone')" class="text-sm text-destructive">
          {{ getError('phone') }}
        </p>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
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
      </div>
    </div>

    <!-- Address -->
    <div class="space-y-2">
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
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label for="city" class="text-sm font-medium">City *</label>
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
      </div>

      <div class="space-y-2">
        <label for="country" class="text-sm font-medium">Country *</label>
        <select
          id="country"
          :value="data.country"
          @change="updateField('country', $event.target.value)"
          :class="['flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm', getError('country') ? 'border-destructive' : '']"
        >
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
    </div>

    <!-- Description -->
    <div class="space-y-2">
      <label for="description" class="text-sm font-medium">School Description</label>
      <textarea
        id="description"
        rows="4"
        placeholder="Tell us about your school's mission, values, and what makes it special..."
        :value="data.description"
        @input="updateField('description', $event.target.value)"
        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
      ></textarea>
      <p class="text-sm text-muted-foreground">
        This will be displayed on your public school profile
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Upload } from 'lucide-vue-next'

const UploadIcon = Upload

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

const updateField = (field, value) => {
  emit('update', 'profile', { [field]: value })
}

const handleLogoChange = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    emit('update', 'profile', { logo: file })
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const getError = (field) => {
  return props.errors[`profile.${field}`] || props.errors[field]
}
</script>