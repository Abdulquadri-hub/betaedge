<script setup lang="ts">
import { XCircle } from 'lucide-vue-next'

const XCircleIcon = XCircle

defineProps({
  message: {
    type: String,
    default: 'Invalid or expired verification link'
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-primary/5 px-4">
    <div class="max-w-md w-full">
      <div class="bg-card rounded-2xl border shadow-lg p-8 text-center">
        <!-- Error Icon -->
        <div class="w-20 h-20 rounded-full bg-destructive/10 flex items-center justify-center mx-auto mb-6">
          <XCircleIcon class="w-10 h-10 text-destructive" />
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-foreground mb-2">
          Verification Failed
        </h1>
        <p class="text-muted-foreground mb-6">
          {{ message }}
        </p>

        <!-- Actions -->
        <div class="space-y-3">
          <a
            href="/onboarding"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90"
          >
            Start New Registration
          </a>
          <a
            href="/login"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground"
          >
            Go to Login
          </a>
        </div>

        <!-- Help -->
        <p class="text-xs text-muted-foreground mt-6">
          Need help? Contact <a href="mailto:support@example.com" class="text-primary hover:underline">support@example.com</a>
        </p>
      </div>
    </div>
  </div>
</template>