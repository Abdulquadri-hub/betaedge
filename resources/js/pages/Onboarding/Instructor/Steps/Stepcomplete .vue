<script setup>

import { CheckCircle2, Clock, Briefcase, User, ArrowRight, GraduationCap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Link }   from '@inertiajs/vue3'

defineProps({
  name: {
    type: String,
    default: 'Instructor',
  },
})
</script>

<template>
  <div class="flex flex-col items-center text-center space-y-6 py-4">

    <!-- Success icon -->
    <div class="relative">
      <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/40">
        <CheckCircle2 class="h-10 w-10 text-emerald-500" />
      </div>
      <div class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-secondary shadow-md">
        <GraduationCap class="h-4 w-4 text-secondary-foreground" />
      </div>
    </div>

    <div>
      <h2 class="text-2xl font-black text-foreground">You're all set, {{ name }}!</h2>
      <p class="text-sm text-muted-foreground mt-2 max-w-sm mx-auto">
        Your application has been submitted. Here's what happens next.
      </p>
    </div>

    <!-- Next steps timeline -->
    <div class="w-full max-w-sm space-y-3 text-left">

      <div class="flex items-start gap-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 p-4">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500">
          <CheckCircle2 class="h-4 w-4 text-white" />
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">Profile created</p>
          <p class="text-xs text-muted-foreground mt-0.5">Your account is active. You can log in anytime.</p>
        </div>
      </div>

      <div class="flex items-start gap-4 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-400">
          <Clock class="h-4 w-4 text-white" />
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">Verification in review</p>
          <p class="text-xs text-muted-foreground mt-0.5">
            Our team will review your documents within <strong>1–2 business days</strong>.
            You'll receive an email once approved.
          </p>
        </div>
      </div>

      <div class="flex items-start gap-4 rounded-xl border border-border bg-muted/30 p-4">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted border border-border">
          <Briefcase class="h-4 w-4 text-muted-foreground" />
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">Start applying to schools</p>
          <p class="text-xs text-muted-foreground mt-0.5">
            Once verified, your profile becomes visible to schools and you can apply for teaching positions.
          </p>
        </div>
      </div>

    </div>

    <!-- CTA buttons -->
    <div class="flex flex-col sm:flex-row gap-3 w-full max-w-sm pt-2">
      <Button as-child class="flex-1 gap-2">
        <Link href="/instructor">
          Go to Dashboard <ArrowRight class="h-4 w-4" />
        </Link>
      </Button>
      <Button variant="outline" as-child class="flex-1 gap-2">
        <Link href="/instructor/profile">
          <User class="h-4 w-4" />Complete Profile
        </Link>
      </Button>
    </div>

    <p class="text-xs text-muted-foreground max-w-xs">
      Questions? Email us at <a href="mailto:support@teach.com" class="text-primary underline">support@teach.com</a>
    </p>
  </div>
</template>