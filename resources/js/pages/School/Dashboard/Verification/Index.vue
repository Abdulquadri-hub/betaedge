<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CheckCircle2, Clock, AlertCircle, ArrowLeft } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  verificationStatus: Object,
  user: Object,
  isRequired: Boolean,
})

const form = useForm({
  id_type: 'nin',
  id_number: '',
  first_name: '',
  last_name: '',
})

const isSubmitting = computed(() => form.processing)

const showForm = computed(() => {
  if (!props.verificationStatus) return true
  return props.verificationStatus.status !== 'verified'
})

const statusBgColor = computed(() => {
  if (!props.verificationStatus) return 'bg-gray-100 dark:bg-gray-950'
  if (props.verificationStatus.status === 'verified') return 'bg-emerald-100 dark:bg-emerald-950'
  if (props.verificationStatus.status === 'pending') return 'bg-amber-100 dark:bg-amber-950'
  if (props.verificationStatus.status === 'rejected') return 'bg-red-100 dark:bg-red-950'
  return 'bg-gray-100 dark:bg-gray-950'
})

const statusTextColor = computed(() => {
  if (!props.verificationStatus) return 'text-gray-600'
  if (props.verificationStatus.status === 'verified') return 'text-emerald-600'
  if (props.verificationStatus.status === 'pending') return 'text-amber-600'
  if (props.verificationStatus.status === 'rejected') return 'text-red-600'
  return 'text-gray-600'
})

const statusIcon = computed(() => {
  if (!props.verificationStatus) return AlertCircle
  if (props.verificationStatus.status === 'verified') return CheckCircle2
  if (props.verificationStatus.status === 'pending') return Clock
  if (props.verificationStatus.status === 'rejected') return AlertCircle
  return AlertCircle
})

async function handleSubmit() {
  await form.post('/dashboard/verification', {
    onSuccess: () => {
      toast.success('Verification submitted successfully')
      form.reset()
    },
  })
}
</script>

<template>
  <DashboardLayout>
    <div class="p-6 max-w-2xl mx-auto space-y-6">

      <div class="flex items-center gap-3">
        <Link href="/dashboard/profile">
          <Button variant="ghost" size="sm" class="gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Profile
          </Button>
        </Link>
      </div>

      <div>
        <h1 class="text-2xl font-bold text-foreground">ID Verification</h1>
        <p class="text-sm text-muted-foreground mt-1">
          School owners and instructors must be verified to process payments
        </p>
      </div>

      <Card v-if="props.verificationStatus">
        <CardContent class="pt-6">
          <div :class="['flex items-center gap-4 p-4 rounded-lg', statusBgColor]">
            <component :is="statusIcon" :class="['h-8 w-8 shrink-0', statusTextColor]" />
            <div>
              <p :class="['font-bold text-lg capitalize', statusTextColor]">
                {{ props.verificationStatus.status }}
              </p>
              <p v-if="props.verificationStatus.status === 'pending'" class="text-sm opacity-90 mt-1">
                Your verification is under review. We'll notify you within 24 hours.
              </p>
              <p v-else-if="props.verificationStatus.status === 'rejected'" class="text-sm opacity-90 mt-1">
                {{ props.verificationStatus.rejection_reason || 'Please try again with correct information.' }}
              </p>
              <p v-else-if="props.verificationStatus.status === 'verified'" class="text-sm opacity-90 mt-1">
                You are verified and can process payments
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="showForm">
        <CardHeader>
          <CardTitle>Submit for Verification</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-4">

            <div class="space-y-2">
              <Label for="id_type">ID Type</Label>
              <Select v-model="form.id_type">
                <SelectTrigger id="id_type">
                  <SelectValue placeholder="Select ID type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="nin">National ID (NIN)</SelectItem>
                  <SelectItem value="license">Driver's License</SelectItem>
                  <SelectItem value="passport">International Passport</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.id_type" class="text-xs text-red-500">{{ form.errors.id_type }}</p>
            </div>

            <div class="space-y-2">
              <Label for="id_number">ID Number</Label>
              <Input
                id="id_number"
                v-model="form.id_number"
                type="text"
                placeholder="e.g., 12345678901"
                :disabled="isSubmitting"
              />
              <p v-if="form.errors.id_number" class="text-xs text-red-500">{{ form.errors.id_number }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="first_name">First Name</Label>
                <Input
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  placeholder="First name"
                  :disabled="isSubmitting"
                />
                <p v-if="form.errors.first_name" class="text-xs text-red-500">{{ form.errors.first_name }}</p>
              </div>

              <div class="space-y-2">
                <Label for="last_name">Last Name</Label>
                <Input
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  placeholder="Last name"
                  :disabled="isSubmitting"
                />
                <p v-if="form.errors.last_name" class="text-xs text-red-500">{{ form.errors.last_name }}</p>
              </div>
            </div>

            <Button type="submit" :disabled="isSubmitting" class="w-full">
              {{ isSubmitting ? 'Submitting...' : 'Submit for Verification' }}
            </Button>

          </form>
        </CardContent>
      </Card>

    </div>
  </DashboardLayout>
</template>