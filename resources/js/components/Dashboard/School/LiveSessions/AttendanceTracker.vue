<script setup>

import { ref, computed, watch, onMounted } from 'vue'
import {
  CheckCircle2, Clock, XCircle, MinusCircle,
  RefreshCw, Download, Search, Users,
  //ChevronDown,
  Save,
} from 'lucide-vue-next'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
// import {
//   DropdownMenu, DropdownMenuContent, DropdownMenuItem,
//   DropdownMenuTrigger,
// } from '@/components/ui/dropdown-menu'
import {
  useLiveSessionsManager,
  ATTENDANCE_STATUS,
} from '@/composables/useLiveSessionsManager'
import { toast } from 'vue-sonner'

// ─── Props / emits ────────────────────────────────────────────────────────────
const props = defineProps({
  sessionId: { type: String,  required: true  },
  readonly:  { type: Boolean, default:  false },
})
const emit = defineEmits(['saved'])

const { getAttendance, 
    //markAttendance, 
    bulkMarkAttendance, getSessionById, formatTime 
} = useLiveSessionsManager()


// ─── State ────────────────────────────────────────────────────────────────────
const session     = computed(() => getSessionById(props.sessionId))
const attendance  = ref([])
const isLoading   = ref(true)
const isSaving    = ref(false)
const search      = ref('')
const isDirty     = ref(false)

onMounted(async () => {
  attendance.value = await getAttendance(props.sessionId)
  isLoading.value  = false
})

watch(() => props.sessionId, async (id) => {
  isLoading.value  = true
  attendance.value = await getAttendance(id)
  isLoading.value  = false
  isDirty.value    = false
})

// ─── Filtered list ────────────────────────────────────────────────────────────
const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return attendance.value
  return attendance.value.filter(a => a.student_name.toLowerCase().includes(q))
})

// ─── Summary counts ───────────────────────────────────────────────────────────
const summary = computed(() => ({
  present: attendance.value.filter(a => a.status === 'present').length,
  late:    attendance.value.filter(a => a.status === 'late').length,
  absent:  attendance.value.filter(a => a.status === 'absent').length,
  excused: attendance.value.filter(a => a.status === 'excused').length,
  total:   attendance.value.length,
}))

const attendanceRate = computed(() => {
  if (!summary.value.total) return 0
  return Math.round(((summary.value.present + summary.value.late) / summary.value.total) * 100)
})

// ─── Mark single student ──────────────────────────────────────────────────────
function setStatus(record, status) {
  if (props.readonly) return
  record.status = status
  isDirty.value  = true
}

// ─── Bulk mark all ────────────────────────────────────────────────────────────
function markAll(status) {
  if (props.readonly) return
  attendance.value.forEach(a => { a.status = status })
  isDirty.value = true
}

// ─── Save bulk ────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!isDirty.value) return
  isSaving.value = true
  try {
    const records = attendance.value.map(a => ({ student_id: a.student_id, status: a.status }))
    const result  = await bulkMarkAttendance(props.sessionId, records)
    if (result?.success !== false) {
      isDirty.value = false
      toast({ title: 'Attendance saved', description: `${summary.value.total} records updated.` })
      emit('saved', records)
    }
  } finally {
    isSaving.value = false
  }
}

// ─── Export CSV ───────────────────────────────────────────────────────────────
function exportCSV() {
  const headers = 'Student Name,Status,Joined At,Left At,Duration (min)\n'
  const rows    = attendance.value.map(a =>
    `"${a.student_name}","${a.status}","${a.joined_at ?? ''}","${a.left_at ?? ''}","${a.duration_minutes ?? ''}"`
  ).join('\n')
  const blob = new Blob([headers + rows], { type: 'text/csv' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `attendance-${props.sessionId}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function initials(name) {
  return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const statusIcons = {
  present: CheckCircle2,
  late:    Clock,
  absent:  XCircle,
  excused: MinusCircle,
}

const statusColors = {
  present: 'text-emerald-600 border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30',
  late:    'text-amber-600   border-amber-200   bg-amber-50   dark:border-amber-800   dark:bg-amber-950/30',
  absent:  'text-destructive border-destructive/30 bg-destructive/5',
  excused: 'text-muted-foreground border-border bg-muted/30',
}
</script>

<template>
  <div class="space-y-4">

    <!-- Header row -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <div>
        <p class="text-sm font-semibold text-foreground">
          {{ session?.title ?? 'Attendance' }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ session ? `${session.scheduled_date} · ${formatTime(session.scheduled_time)}` : '' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button v-if="!readonly" variant="outline" size="sm" class="gap-1.5 text-xs h-8" @click="exportCSV">
          <Download class="h-3.5 w-3.5" />Export
        </Button>
        <Button
          v-if="!readonly && isDirty"
          size="sm"
          class="gap-1.5 text-xs h-8"
          :disabled="isSaving"
          @click="handleSave"
        >
          <RefreshCw v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
          <Save v-else class="h-3.5 w-3.5" />
          {{ isSaving ? 'Saving...' : 'Save Attendance' }}
        </Button>
      </div>
    </div>

    <!-- Summary pills -->
    <div class="flex flex-wrap gap-2">
      <div
        v-for="(label, key) in { present: 'Present', late: 'Late', absent: 'Absent', excused: 'Excused' }"
        :key="key"
        :class="['flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium', statusColors[key]]"
      >
        <component :is="statusIcons[key]" class="h-3 w-3" />
        {{ label }}: {{ summary[key] }}
      </div>
      <div class="flex items-center gap-1.5 rounded-full border border-border bg-muted/30 px-3 py-1 text-xs font-medium text-muted-foreground">
        <Users class="h-3 w-3" />Rate: {{ attendanceRate }}%
      </div>
    </div>

    <!-- Bulk actions (edit mode only) -->
    <div v-if="!readonly" class="flex items-center gap-2">
      <span class="text-xs text-muted-foreground">Mark all as:</span>
      <button
        v-for="(cfg, status) in ATTENDANCE_STATUS"
        :key="status"
        type="button"
        class="rounded-md border px-2 py-1 text-xs font-medium transition-all hover:opacity-80"
        :class="statusColors[status]"
        @click="markAll(status)"
      >{{ cfg.label }}</button>
    </div>

    <!-- Search -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
      <Input v-model="search" placeholder="Search student..." class="pl-9 h-8 text-sm" />
    </div>

    <!-- Student list -->
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="h-12 bg-muted rounded-lg animate-pulse" />
    </div>

    <div v-else-if="filtered.length === 0" class="py-8 text-center text-sm text-muted-foreground">
      No students found.
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="record in filtered"
        :key="record.id"
        class="flex items-center gap-3 rounded-lg border border-border bg-card p-3 transition-colors hover:bg-muted/30"
      >
        <!-- Avatar -->
        <Avatar class="h-8 w-8 shrink-0">
          <AvatarFallback
            :class="['text-xs font-bold', statusColors[record.status].split(' ').slice(0,2).join(' ')]"
          >{{ initials(record.student_name) }}</AvatarFallback>
        </Avatar>

        <!-- Name + time info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-foreground">{{ record.student_name }}</p>
          <div v-if="record.joined_at" class="flex items-center gap-2 text-xs text-muted-foreground">
            <span>Joined {{ record.joined_at }}</span>
            <span v-if="record.left_at">· Left {{ record.left_at }}</span>
            <span v-if="record.duration_minutes">· {{ record.duration_minutes }}min</span>
          </div>
        </div>

        <!-- Status selector (edit) or badge (readonly) -->
        <div v-if="readonly">
          <Badge :variant="ATTENDANCE_STATUS[record.status]?.variant ?? 'outline'" class="text-xs gap-1">
            <component :is="statusIcons[record.status]" class="h-3 w-3" />
            {{ ATTENDANCE_STATUS[record.status]?.label }}
          </Badge>
        </div>

        <div v-else class="flex items-center gap-1 shrink-0">
          <button
            v-for="(cfg, s) in ATTENDANCE_STATUS"
            :key="s"
            type="button"
            :title="cfg.label"
            class="flex h-7 w-7 items-center justify-center rounded-md border transition-all"
            :class="record.status === s
              ? statusColors[s] + ' border-current shadow-sm'
              : 'border-border text-muted-foreground hover:border-primary/30'"
            @click="setStatus(record, s)"
          >
            <component :is="statusIcons[s]" class="h-3.5 w-3.5" />
          </button>
        </div>

      </div>
    </div>

    <!-- Dirty save reminder -->
    <div
      v-if="isDirty && !readonly"
      class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 px-4 py-2.5"
    >
      <p class="text-xs text-amber-800 dark:text-amber-300 font-medium">
        You have unsaved attendance changes.
      </p>
      <Button size="sm" class="gap-1.5 text-xs h-7" :disabled="isSaving" @click="handleSave">
        <RefreshCw v-if="isSaving" class="h-3 w-3 animate-spin" />
        <Save v-else class="h-3 w-3" />
        Save
      </Button>
    </div>

  </div>
</template>