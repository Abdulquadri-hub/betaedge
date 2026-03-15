<script setup>

import { ref, computed } from 'vue'
import { Phone, MapPin, FileText, ArrowRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input }  from '@/components/ui/input'
import { Label }  from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'

const emit = defineEmits(['next'])

const form = ref({
  first_name:  '',
  last_name:   '',
  phone:       '',
  location:    '',
  gender:      '',
  bio:         '',
})

const errors = ref({})

const isValid = computed(() =>
  form.value.first_name.trim() &&
  form.value.last_name.trim() &&
  form.value.phone.trim() &&
  form.value.location.trim() &&
  form.value.bio.trim().length >= 30
)

function validate() {
  errors.value = {}
  if (!form.value.first_name.trim()) errors.value.first_name = 'First name is required'
  if (!form.value.last_name.trim())  errors.value.last_name  = 'Last name is required'
  if (!form.value.phone.trim())      errors.value.phone      = 'Phone number is required'
  if (!form.value.location.trim())   errors.value.location   = 'Location is required'
  if (form.value.bio.trim().length < 30) errors.value.bio    = 'Bio must be at least 30 characters'
  return Object.keys(errors.value).length === 0
}

function handleNext() {
  if (!validate()) return
  emit('next', { ...form.value })
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-foreground">Tell us about yourself</h2>
      <p class="text-sm text-muted-foreground mt-1">
        This information will appear on your public tutor profile.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">

      <!-- First name -->
      <div class="space-y-1.5">
        <Label>First Name <span class="text-destructive">*</span></Label>
        <Input
          v-model="form.first_name"
          placeholder="e.g., Adebayo"
          :class="errors.first_name ? 'border-destructive' : ''"
        />
        <p v-if="errors.first_name" class="text-xs text-destructive">{{ errors.first_name }}</p>
      </div>

      <!-- Last name -->
      <div class="space-y-1.5">
        <Label>Last Name <span class="text-destructive">*</span></Label>
        <Input
          v-model="form.last_name"
          placeholder="e.g., Johnson"
          :class="errors.last_name ? 'border-destructive' : ''"
        />
        <p v-if="errors.last_name" class="text-xs text-destructive">{{ errors.last_name }}</p>
      </div>

      <!-- Phone -->
      <div class="space-y-1.5">
        <Label>Phone Number <span class="text-destructive">*</span></Label>
        <div class="relative">
          <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="form.phone"
            placeholder="+234 8XX XXX XXXX"
            class="pl-9"
            :class="errors.phone ? 'border-destructive' : ''"
          />
        </div>
        <p v-if="errors.phone" class="text-xs text-destructive">{{ errors.phone }}</p>
      </div>

      <!-- Location -->
      <div class="space-y-1.5">
        <Label>Location <span class="text-destructive">*</span></Label>
        <div class="relative">
          <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="form.location"
            placeholder="e.g., Lagos, Nigeria"
            class="pl-9"
            :class="errors.location ? 'border-destructive' : ''"
          />
        </div>
        <p v-if="errors.location" class="text-xs text-destructive">{{ errors.location }}</p>
      </div>

      <!-- Gender -->
      <div class="space-y-1.5">
        <Label>Gender <span class="text-muted-foreground text-xs">(optional)</span></Label>
        <Select v-model="form.gender">
          <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="male">Male</SelectItem>
            <SelectItem value="female">Female</SelectItem>
            <SelectItem value="prefer_not">Prefer not to say</SelectItem>
          </SelectContent>
        </Select>
      </div>

    </div>

    <!-- Bio -->
    <div class="space-y-1.5">
      <Label class="flex items-center gap-1.5">
        <FileText class="h-3.5 w-3.5" />
        Professional Bio <span class="text-destructive">*</span>
      </Label>
      <Textarea
        v-model="form.bio"
        placeholder="Tell schools and students about your background, teaching style, and what makes you a great tutor. Minimum 30 characters."
        rows="4"
        :class="errors.bio ? 'border-destructive' : ''"
      />
      <div class="flex items-center justify-between">
        <p v-if="errors.bio" class="text-xs text-destructive">{{ errors.bio }}</p>
        <p v-else class="text-xs text-muted-foreground">{{ form.bio.length }} characters</p>
        <p class="text-xs" :class="form.bio.length >= 30 ? 'text-emerald-600' : 'text-muted-foreground'">
          min. 30
        </p>
      </div>
    </div>

    <div class="flex justify-end pt-2">
      <Button :disabled="!isValid" class="gap-2 min-w-32" @click="handleNext">
        Continue <ArrowRight class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>