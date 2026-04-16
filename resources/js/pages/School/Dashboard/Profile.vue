<script setup>
import { computed } from 'vue'
import { useUserProfile } from '@/composables/useUserProfile'
import {CheckCircle2, Clock, AlertCircle } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
// import { Badge } from '@/components/ui/badge'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'
import { Link } from '@inertiajs/vue3'

const {
//   user,
//   tenant,
  verification,
  isVerified,
  isPending,
  isRejected,
  verificationStatusLabel,
  profileForm,
  updateProfile,
} = useUserProfile()

const isSaving = computed(() => profileForm.processing)

const verificationColor = computed(() => {
  if (isVerified.value) return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950'
  if (isPending.value) return 'bg-amber-100 text-amber-600 dark:bg-amber-950'
  if (isRejected.value) return 'bg-red-100 text-red-600 dark:bg-red-950'
  return 'bg-gray-100 text-gray-600 dark:bg-gray-950'
})

const verificationIcon = computed(() => {
  if (isVerified.value) return CheckCircle2
  if (isPending.value) return Clock
  if (isRejected.value) return AlertCircle
  return AlertCircle
})

async function handleSubmit() {
  await updateProfile()
  if (!profileForm.hasErrors) {
    toast.success('Profile updated successfully')
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="p-6 max-w-3xl mx-auto space-y-6">

      <div>
        <h1 class="text-2xl font-bold text-foreground">Profile</h1>
        <p class="text-sm text-muted-foreground mt-1">Manage your account information and verification status</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">

        <div class="lg:col-span-2 space-y-6">

          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
            </CardHeader>
            <CardContent>
              <form @submit.prevent="handleSubmit" class="space-y-4">

                <div class="space-y-2">
                  <Label for="name">Full Name</Label>
                  <Input
                    id="name"
                    v-model="profileForm.name"
                    type="text"
                    placeholder="Your full name"
                    :disabled="isSaving"
                  />
                  <p v-if="profileForm.errors.name" class="text-xs text-red-500">{{ profileForm.errors.name }}</p>
                </div>

                <div class="space-y-2">
                  <Label for="email">Email Address</Label>
                  <Input
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    placeholder="your@email.com"
                    :disabled="isSaving"
                  />
                  <p v-if="profileForm.errors.email" class="text-xs text-red-500">{{ profileForm.errors.email }}</p>
                </div>

                <div class="space-y-2">
                  <Label for="phone">Phone Number</Label>
                  <Input
                    id="phone"
                    v-model="profileForm.phone"
                    type="tel"
                    placeholder="+234 800 000 0000"
                    :disabled="isSaving"
                  />
                  <p v-if="profileForm.errors.phone" class="text-xs text-red-500">{{ profileForm.errors.phone }}</p>
                </div>

                <Button type="submit" :disabled="isSaving" class="w-full">
                  {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </Button>

              </form>
            </CardContent>
          </Card>

        </div>

        <div class="space-y-6">

          <Card>
            <CardHeader>
              <CardTitle class="text-base">Verification Status</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">

              <div :class="['flex items-center gap-3 p-3 rounded-lg', verificationColor]">
                <component :is="verificationIcon" class="h-5 w-5 shrink-0" />
                <div class="min-w-0">
                  <p class="font-medium text-sm">{{ verificationStatusLabel }}</p>
                  <p class="text-xs opacity-75 mt-0.5">School Owner & Instructor</p>
                </div>
              </div>

              <div v-if="!isVerified" class="text-xs space-y-2">
                <p v-if="!isPending" class="text-muted-foreground">
                  Complete verification to enable payments and full access
                </p>
                <p v-else class="text-amber-600 dark:text-amber-400">
                  Your verification is under review. You'll be notified once approved.
                </p>
                <p v-if="isRejected" class="text-red-600 dark:text-red-400">
                  Your verification was rejected. Please try again with correct information.
                </p>

                <Link href="/dashboard/verification" class="block">
                  <Button type="button" variant="outline" size="sm" class="w-full">
                    {{ isPending ? 'View Details' : 'Start Verification' }}
                  </Button>
                </Link>
              </div>

              <div v-else class="text-xs space-y-3 pt-2 border-t border-border">
                <div>
                  <p class="text-muted-foreground">Verified ID Type</p>
                  <p class="font-medium capitalize text-sm mt-1">{{ verification.id_type }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Verified On</p>
                  <p class="font-medium text-sm mt-1">{{ new Date(verification.verified_at).toLocaleDateString() }}</p>
                </div>
              </div>

            </CardContent>
          </Card>

        </div>

      </div>

    </div>
  </DashboardLayout>
</template>
