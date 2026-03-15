<script setup>

import { computed } from 'vue'
import { ShieldCheck, Clock, ShieldX, ShieldAlert } from 'lucide-vue-next'

const props = defineProps({
  status: {
    type: String,
    default: 'unverified',
    validator: v => ['verified', 'pending', 'rejected', 'unverified'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: v => ['sm', 'md', 'lg'].includes(v),
  },
  showLabel: {
    type: Boolean,
    default: true,
  },
})

const config = computed(() => ({
  verified: {
    icon:       ShieldCheck,
    label:      'Verified',
    classes:    'text-emerald-700 bg-emerald-100 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/30 dark:border-emerald-800',
  },
  pending: {
    icon:       Clock,
    label:      'Pending Review',
    classes:    'text-amber-700 bg-amber-100 border-amber-200 dark:text-amber-400 dark:bg-amber-950/30 dark:border-amber-800',
  },
  rejected: {
    icon:       ShieldX,
    label:      'Rejected',
    classes:    'text-destructive bg-destructive/10 border-destructive/20',
  },
  unverified: {
    icon:       ShieldAlert,
    label:      'Not Verified',
    classes:    'text-muted-foreground bg-muted border-border',
  },
}[props.status]))

const sizeClasses = computed(() => ({
  sm: 'text-[10px] px-1.5 py-0.5 gap-1',
  md: 'text-xs px-2.5 py-1 gap-1.5',
  lg: 'text-sm px-3 py-1.5 gap-2',
}[props.size]))

const iconSize = computed(() => ({
  sm: 'h-3 w-3',
  md: 'h-3.5 w-3.5',
  lg: 'h-4 w-4',
}[props.size]))
</script>

<template>
  <span
    class="inline-flex items-center rounded-full border font-semibold"
    :class="[config.classes, sizeClasses]"
  >
    <component :is="config.icon" :class="iconSize" />
    <span v-if="showLabel">{{ config.label }}</span>
  </span>
</template>