<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Clock, RefreshCw } from 'lucide-vue-next'

const ClockIcon = Clock
const RefreshCwIcon = RefreshCw

const props = defineProps({
  tenant: {
    type: Object,
    default: null
  },
  canResend: {
    type: Boolean,
    default: false
  }
})

const resending = ref(false)

const resendVerification = () => {
  if (resending.value) return

  resending.value = true

  router.post('/verification/resend', {
    email: props.tenant?.email
  }, {
    preserveScroll: true,
    onFinish: () => {
      resending.value = false
    }
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-primary/5 px-4">
    <div class="max-w-md w-full">
      <div class="bg-card rounded-2xl border shadow-lg p-8 text-center">
        <!-- Warning Icon -->
        <div class="w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mx-auto mb-6">
          <ClockIcon class="w-10 h-10 text-orange-600 dark:text-orange-400" />
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-foreground mb-2">
          Link Expired
        </h1>
        <p class="text-muted-foreground mb-6">
          Your verification link has expired (valid for 24 hours only)
        </p>

        <!-- Tenant Info -->
        <div v-if="tenant" class="p-4 bg-muted/30 rounded-lg mb-6">
          <p class="text-sm text-muted-foreground mb-1">School:</p>
          <p class="font-medium">{{ tenant.name }}</p>
          <p class="text-sm text-muted-foreground">{{ tenant.email }}</p>
        </div>

        <!-- Resend -->
        <div v-if="canResend">
          <button
            @click="resendVerification"
            :disabled="resending"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 mb-3"
          >
            <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': resending }" />
            <span v-if="resending">Sending...</span>
            <span v-else>Request New Verification Link</span>
          </button>
        </div>

        <a
          href="/login"
          class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground"
        >
          Back to Login
        </a>
      </div>
    </div>
  </div>
</template>