<script setup>

import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight, Plus } from 'lucide-vue-next'
// import { Badge }  from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { SESSION_STATUS } from '@/composables/useLiveSessionsManager'

// ─── Props / emits ────────────────────────────────────────────────────────────
const props = defineProps({
  sessions: { type: Array, default: () => [] },
})
const emit = defineEmits(['select', 'create'])

// ─── Calendar state ───────────────────────────────────────────────────────────
const today = new Date()
const viewYear  = ref(today.getFullYear())
const viewMonth = ref(today.getMonth()) // 0-indexed

const monthNames = [
  'January','February','March','April','May','June',
  'July','August','September','October','November','December',
]
const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']

function prevMonth() {
  if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value-- }
  else viewMonth.value--
}
function nextMonth() {
  if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++ }
  else viewMonth.value++
}
function goToday() {
  viewYear.value  = today.getFullYear()
  viewMonth.value = today.getMonth()
}

// ─── Grid computation ─────────────────────────────────────────────────────────
const calendarDays = computed(() => {
  const firstDay   = new Date(viewYear.value, viewMonth.value, 1).getDay()
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()
  const daysInPrev  = new Date(viewYear.value, viewMonth.value, 0).getDate()

  const days = []

  // Prev month padding
  for (let i = firstDay - 1; i >= 0; i--) {
    days.push({ date: `${viewYear.value}-${String(viewMonth.value).padStart(2,'0')}-${String(daysInPrev - i).padStart(2,'0')}`, current: false, day: daysInPrev - i })
  }

  // Current month
  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${viewYear.value}-${String(viewMonth.value + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
    days.push({ date: dateStr, current: true, day: d })
  }

  // Next month padding (complete to 6 rows)
  const remaining = 42 - days.length
  for (let d = 1; d <= remaining; d++) {
    days.push({ date: `${viewYear.value}-${String(viewMonth.value + 2).padStart(2,'0')}-${String(d).padStart(2,'0')}`, current: false, day: d })
  }

  return days
})

// ─── Sessions mapped by date ──────────────────────────────────────────────────
const sessionsByDate = computed(() => {
  const map = {}
  for (const s of props.sessions) {
    if (!map[s.scheduled_date]) map[s.scheduled_date] = []
    map[s.scheduled_date].push(s)
  }
  return map
})

function sessionsOnDate(dateStr) {
  return sessionsByDate.value[dateStr] ?? []
}

// ─── Today check ─────────────────────────────────────────────────────────────
const todayStr = today.toISOString().split('T')[0]

function isToday(dateStr) {
  return dateStr === todayStr
}

function isPast(dateStr) {
  return dateStr < todayStr
}

// ─── Status dot color ─────────────────────────────────────────────────────────
const statusDot = {
  scheduled: 'bg-secondary',
  live:      'bg-emerald-500 animate-pulse',
  completed: 'bg-muted-foreground',
  cancelled: 'bg-destructive',
}

function formatTime(t) {
  if (!t) return ''
  const [h, m] = t.split(':').map(Number)
  const p  = h >= 12 ? 'PM' : 'AM'
  const hr = h === 0 ? 12 : h > 12 ? h - 12 : h
  return `${hr}:${String(m).padStart(2, '0')} ${p}`
}
</script>

<template>
  <div class="rounded-xl border border-border bg-card overflow-hidden">

    <!-- Calendar header -->
    <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-border bg-muted/30">
      <div class="flex items-center gap-2">
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="prevMonth">
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <h2 class="text-sm font-bold text-foreground min-w-[140px] text-center">
          {{ monthNames[viewMonth] }} {{ viewYear }}
        </h2>
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="nextMonth">
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
      <Button variant="outline" size="sm" class="text-xs h-7" @click="goToday">Today</Button>
    </div>

    <!-- Day headers -->
    <div class="grid grid-cols-7 border-b border-border">
      <div
        v-for="d in dayNames"
        :key="d"
        class="py-2 text-center text-[10px] font-semibold uppercase tracking-wide text-muted-foreground"
      >{{ d }}</div>
    </div>

    <!-- Calendar grid -->
    <div class="grid grid-cols-7">
      <div
        v-for="(day, i) in calendarDays"
        :key="i"
        class="min-h-[80px] sm:min-h-[100px] border-b border-r border-border/50 p-1.5 transition-colors"
        :class="[
          !day.current && 'bg-muted/20',
          isToday(day.date) && 'bg-primary/5',
          day.current && !isPast(day.date) && 'hover:bg-muted/30 cursor-pointer',
          (i + 1) % 7 === 0 && 'border-r-0',
        ]"
        @click="day.current && !isPast(day.date) && sessionsOnDate(day.date).length === 0 && emit('create', day.date)"
      >
        <!-- Day number -->
        <div class="flex items-center justify-between mb-1">
          <span
            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
            :class="[
              isToday(day.date) ? 'bg-primary text-primary-foreground' : '',
              !day.current ? 'text-muted-foreground/50' : 'text-foreground',
            ]"
          >{{ day.day }}</span>

          <!-- Add session button (empty future days) -->
          <button
            v-if="day.current && !isPast(day.date) && sessionsOnDate(day.date).length === 0"
            type="button"
            class="opacity-0 hover:opacity-100 group-hover:opacity-100 flex h-5 w-5 items-center justify-center rounded text-muted-foreground hover:text-primary hover:bg-primary/10 transition-all"
            @click.stop="emit('create', day.date)"
          >
            <Plus class="h-3 w-3" />
          </button>
        </div>

        <!-- Session chips on this day -->
        <div class="space-y-0.5">
          <button
            v-for="s in sessionsOnDate(day.date).slice(0, 3)"
            :key="s.id"
            type="button"
            class="w-full flex items-center gap-1 rounded px-1 py-0.5 text-left text-[10px] font-medium truncate transition-colors hover:opacity-80"
            :class="[
              s.status === 'live'      ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400' :
              s.status === 'cancelled' ? 'bg-destructive/10 text-destructive line-through'  :
              s.status === 'completed' ? 'bg-muted text-muted-foreground'                   :
                                         'bg-primary/10 text-primary'
            ]"
            @click.stop="emit('select', s)"
          >
            <span :class="['h-1.5 w-1.5 rounded-full shrink-0', statusDot[s.status]]" />
            <span class="truncate">{{ formatTime(s.scheduled_time) }} {{ s.title }}</span>
          </button>

          <!-- Overflow indicator -->
          <div
            v-if="sessionsOnDate(day.date).length > 3"
            class="text-[10px] text-muted-foreground pl-1"
          >+{{ sessionsOnDate(day.date).length - 3 }} more</div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex items-center gap-4 px-4 py-2.5 border-t border-border bg-muted/20">
      <span class="text-[10px] text-muted-foreground font-medium">Legend:</span>
      <div
        v-for="(cfg, status) in SESSION_STATUS"
        :key="status"
        class="flex items-center gap-1.5 text-[10px] text-muted-foreground"
      >
        <span :class="['h-2 w-2 rounded-full', statusDot[status]]" />
        {{ cfg.label }}
      </div>
    </div>
  </div>
</template>