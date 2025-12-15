<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Mail, RefreshCw } from 'lucide-vue-next'

const MailIcon = Mail
const RefreshCwIcon = RefreshCw

defineProps({
  message: {
    type: String,
    default: 'Please check your email for verification link'
  }
})

const resending = ref(false)
const countdown = ref(0)

const resendEmail = () => {
  if (resending.value || countdown.value > 0) return

  resending.value = true

  router.post('/verification/resend', {}, {
    preserveScroll: true,
    onSuccess: () => {
      resending.value = false
      countdown.value = 60
      
      const interval = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
          clearInterval(interval)
        }
      }, 1000)
    },
    onError: () => {
      resending.value = false
    }
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-primary/5 px-4">
    <div class="max-w-md w-full">
      <div class="bg-card rounded-2xl border shadow-lg p-8 text-center">
        <!-- Icon -->
        <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
          <MailIcon class="w-10 h-10 text-primary" />
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-foreground mb-2">
          Check Your Email
        </h1>
        <p class="text-muted-foreground mb-6">
          {{ message }}
        </p>

        <!-- Email sent to -->
        <div class="p-4 bg-muted/30 rounded-lg mb-6">
          <p class="text-sm text-muted-foreground mb-1">Email sent to:</p>
          <p class="font-medium">{{ $page.props.auth?.user?.email || 'your email' }}</p>
        </div>

        <!-- Instructions -->
        <div class="text-left space-y-3 mb-6">
          <div class="flex gap-3">
            <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-xs font-bold text-primary">1</span>
            </div>
            <p class="text-sm text-muted-foreground">
              Check your inbox for the verification email
            </p>
          </div>
          <div class="flex gap-3">
            <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-xs font-bold text-primary">2</span>
            </div>
            <p class="text-sm text-muted-foreground">
              Click the verification link in the email
            </p>
          </div>
          <div class="flex gap-3">
            <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-xs font-bold text-primary">3</span>
            </div>
            <p class="text-sm text-muted-foreground">
              Set your password and access your dashboard
            </p>
          </div>
        </div>

        <!-- Resend -->
        <div class="border-t pt-6">
          <p class="text-sm text-muted-foreground mb-3">
            Didn't receive the email?
          </p>
          <button
            @click="resendEmail"
            :disabled="resending || countdown > 0"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': resending }" />
            <span v-if="countdown > 0">Resend in {{ countdown }}s</span>
            <span v-else-if="resending">Sending...</span>
            <span v-else>Resend Email</span>
          </button>
        </div>

        <!-- Help -->
        <p class="text-xs text-muted-foreground mt-6">
          Having trouble? Contact <a href="mailto:support@example.com" class="text-primary hover:underline">support@example.com</a>
        </p>
      </div>
    </div>
  </div>
</template>