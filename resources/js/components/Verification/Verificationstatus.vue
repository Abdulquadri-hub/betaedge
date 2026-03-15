<script setup>

import { computed } from 'vue'
import { ShieldCheck, Clock, ShieldX, ShieldAlert, ChevronRight } from 'lucide-vue-next'
import VerificationBadge from './Verificationbadge.vue'

const props = defineProps({
  status:      { type: String, default: 'unverified' },
  userType:    { type: String, default: 'instructor' },
  submittedAt: { type: String, default: null         },
  reviewedAt:  { type: String, default: null         },
  documents:   { type: Array,  default: () => []     },
})

const statusConfig = computed(() => ({
  unverified: {
    icon:    ShieldAlert,
    title:   'Verification Required',
    message: `Upload your documents to get your ${props.userType === 'school' ? 'school' : 'tutor'} profile verified and appear in the marketplace.`,
    bg:      'bg-muted/50 border-border',
    iconBg:  'bg-muted',
    iconColor: 'text-muted-foreground',
  },
  pending: {
    icon:    Clock,
    title:   'Under Review',
    message: 'Your documents have been submitted. Our team typically reviews within 1–2 business days. You\'ll receive an email once approved.',
    bg:      'bg-amber-50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-800',
    iconBg:  'bg-amber-100 dark:bg-amber-900/30',
    iconColor: 'text-amber-600',
  },
  verified: {
    icon:    ShieldCheck,
    title:   'Verified',
    message: `Your ${props.userType === 'school' ? 'school is verified' : 'profile is verified'} and visible in the marketplace. Keep your documents up to date.`,
    bg:      'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800',
    iconBg:  'bg-emerald-100 dark:bg-emerald-900/30',
    iconColor: 'text-emerald-600',
  },
  rejected: {
    icon:    ShieldX,
    title:   'Verification Rejected',
    message: 'Some documents were rejected. Please review the feedback below, upload the corrected documents, and resubmit.',
    bg:      'bg-destructive/5 border-destructive/20',
    iconBg:  'bg-destructive/10',
    iconColor: 'text-destructive',
  },
}[props.status]))

const docsApproved = computed(() =>
  props.documents.filter(d => d.status === 'approved').length
)

function fmtDate(iso) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
  })
}
</script>

<template>
  <div class="rounded-2xl border p-5 space-y-4" :class="statusConfig.bg">

    <div class="flex items-start gap-4">
      <!-- Icon -->
      <div
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
        :class="statusConfig.iconBg"
      >
        <component :is="statusConfig.icon" class="h-6 w-6" :class="statusConfig.iconColor" />
      </div>

      <!-- Text -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h3 class="text-base font-bold text-foreground">{{ statusConfig.title }}</h3>
          <VerificationBadge :status="status" size="sm" />
        </div>
        <p class="text-sm text-muted-foreground mt-1 leading-relaxed">{{ statusConfig.message }}</p>
      </div>
    </div>

    <!-- Meta info row -->
    <div
      v-if="submittedAt || reviewedAt || documents.length"
      class="flex flex-wrap gap-4 pt-3 border-t border-border/50 text-xs text-muted-foreground"
    >
      <span v-if="submittedAt" class="flex items-center gap-1.5">
        <Clock class="h-3.5 w-3.5" />
        Submitted {{ fmtDate(submittedAt) }}
      </span>
      <span v-if="reviewedAt" class="flex items-center gap-1.5">
        <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
        Reviewed {{ fmtDate(reviewedAt) }}
      </span>
      <span v-if="documents.length" class="flex items-center gap-1.5">
        <ChevronRight class="h-3.5 w-3.5" />
        {{ docsApproved }}/{{ documents.length }} documents approved
      </span>
    </div>

    <!-- Document checklist (compact) -->
    <div v-if="documents.length" class="grid sm:grid-cols-3 gap-2 pt-1">
      <div
        v-for="doc in documents" :key="doc.label"
        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
        :class="{
          'border-emerald-200 bg-emerald-50/50 dark:bg-emerald-950/10 dark:border-emerald-800': doc.status === 'approved',
          'border-destructive/30 bg-destructive/5': doc.status === 'rejected',
          'border-amber-200 bg-amber-50/50 dark:bg-amber-950/10 dark:border-amber-800': doc.status === 'pending',
          'border-border bg-muted/30': doc.status === 'idle',
        }"
      >
        <ShieldCheck v-if="doc.status === 'approved'" class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
        <ShieldX     v-else-if="doc.status === 'rejected'" class="h-3.5 w-3.5 text-destructive shrink-0" />
        <Clock       v-else-if="doc.status === 'pending'" class="h-3.5 w-3.5 text-amber-500 shrink-0" />
        <ShieldAlert v-else class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
        <span class="truncate font-medium text-foreground">{{ doc.label }}</span>
      </div>
    </div>

  </div>
</template>