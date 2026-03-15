<script setup>

import { computed } from 'vue'
import { CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({
  steps: {
    type: Array,
    required: true,
  },
  current: {
    type: Number,
    required: true,
  },
})

const stepState = computed(() =>
  props.steps.map((step, i) => ({
    ...step,
    index: i + 1,
    done:    i + 1 < props.current,
    active:  i + 1 === props.current,
    pending: i + 1 > props.current,
  }))
)
</script>

<template>
  <div class="flex items-center justify-center gap-0">
    <template v-for="(step, i) in stepState" :key="step.index">

      <!-- Step bubble -->
      <div class="flex flex-col items-center gap-1.5">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300 shrink-0"
          :class="{
            'border-primary bg-primary text-primary-foreground shadow-md':     step.active,
            'border-emerald-500 bg-emerald-500 text-white':                    step.done,
            'border-border bg-muted text-muted-foreground':                    step.pending,
          }"
        >
          <CheckCircle2 v-if="step.done" class="h-5 w-5" />
          <component v-else :is="step.icon" class="h-4 w-4" />
        </div>
        <span
          class="text-[10px] font-medium text-center max-w-[64px] leading-tight hidden sm:block"
          :class="{
            'text-primary':            step.active,
            'text-emerald-600':        step.done,
            'text-muted-foreground':   step.pending,
          }"
        >
          {{ step.label }}
        </span>
      </div>


      <div
        v-if="i < stepState.length - 1"
        class="h-0.5 flex-1 mx-2 mb-4 rounded-full transition-all duration-500"
        :class="stepState[i + 1].done || stepState[i + 1].active ? 'bg-primary' : 'bg-border'"
      />

    </template>
  </div>
</template>