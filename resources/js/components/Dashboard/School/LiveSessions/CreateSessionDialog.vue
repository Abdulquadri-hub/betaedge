<script setup>

import { ref, watch, computed } from 'vue'
import {
  Video, 
  //Calendar, Clock, Link2, FileText,
  RefreshCw, 
  //X, 
  AlertCircle, 
  //BookOpen, Users,
} from 'lucide-vue-next'
import {
  Dialog, DialogContent, DialogDescription,
  DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select, SelectContent, SelectItem,
  SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useDashboardBatches } from '@/composables/useDashboardBatches'
import { useDashboardCourses } from '@/composables/useDashboardCourses'

// ─── Props / Emits ────────────────────────────────────────────────────────────
const props = defineProps({
  open:    { type: Boolean, default: false },
  session: { type: Object,  default: null  },  // null = create mode
})

const emit = defineEmits(['update:open', 'saved'])

// ─── Composables ──────────────────────────────────────────────────────────────
const { batches }          = useDashboardBatches()
const { publishedCourses } = useDashboardCourses()

// Active batches for the dropdown
const activeBatches = computed(() =>
  batches.value.filter(b => b.status === 'open' || b.status === 'active')
)

// ─── Form ─────────────────────────────────────────────────────────────────────
const defaultForm = () => ({
  batch_id:         '',
  course_id:        '',
  title:            '',
  scheduled_date:   '',
  scheduled_time:   '18:00',
  duration_minutes: 90,
  meet_link:        '',
  notes:            '',
})

const form   = ref(defaultForm())
const errors = ref({})
const isSaving = ref(false)

const isEditMode = computed(() => !!props.session)

// Sync form when dialog opens / session changes
watch(() => props.open, (open) => {
  if (open) {
    if (props.session) {
      form.value = {
        batch_id:         props.session.batch_id         ?? '',
        course_id:        props.session.course_id        ?? '',
        title:            props.session.title            ?? '',
        scheduled_date:   props.session.scheduled_date   ?? '',
        scheduled_time:   props.session.scheduled_time   ?? '18:00',
        duration_minutes: props.session.duration_minutes ?? 90,
        meet_link:        props.session.meet_link        ?? '',
        notes:            props.session.notes            ?? '',
      }
    } else {
      form.value = defaultForm()
    }
    errors.value = {}
  }
})

// Auto-fill course when batch is selected
watch(() => form.value.batch_id, (batchId) => {
  const batch = batches.value.find(b => b.id === batchId)
  if (batch && !form.value.course_id) {
    form.value.course_id = batch.course_id ?? ''
  }
  // Auto suggest title from batch + course
  if (batch && !form.value.title) {
    const course = publishedCourses.value.find(c => c.id === batch.course_id)
    if (course) form.value.title = `${course.title} – Session`
  }
})

// ─── Validation ───────────────────────────────────────────────────────────────
function validate() {
  const e   = {}
  const now  = new Date().toISOString().split('T')[0]

  if (!form.value.batch_id)
    e.batch_id = 'Select a batch'

  if (!form.value.title.trim())
    e.title = 'Session title is required'
  else if (form.value.title.length > 200)
    e.title = 'Title must be under 200 characters'

  if (!form.value.scheduled_date)
    e.scheduled_date = 'Date is required'
  else if (!isEditMode.value && form.value.scheduled_date < now)
    e.scheduled_date = 'Date cannot be in the past'

  if (!form.value.scheduled_time)
    e.scheduled_time = 'Time is required'

  if (form.value.duration_minutes < 15)
    e.duration_minutes = 'Minimum 15 minutes'
  else if (form.value.duration_minutes > 480)
    e.duration_minutes = 'Maximum 8 hours'

  if (form.value.meet_link && !form.value.meet_link.startsWith('https://'))
    e.meet_link = 'Link must start with https://'

  errors.value = e
  return Object.keys(e).length === 0
}

// ─── Submit ───────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) return
  isSaving.value = true

  try {
    await new Promise(r => setTimeout(r, 600))
    /**
     * TODO (Laravel 12):
     * if (isEditMode.value) {
     *   router.put(route('dashboard.sessions.update', props.session.id), form.value, {
     *     preserveScroll: true,
     *     onSuccess: () => emit('saved', { ...props.session, ...form.value }),
     *     onError:   (e) => { errors.value = e },
     *   })
     * } else {
     *   router.post(route('dashboard.sessions.store'), form.value, {
     *     preserveScroll: true,
     *     onSuccess: (page) => emit('saved', page.props.session),
     *     onError:   (e)    => { errors.value = e },
     *   })
     * }
     */

    // Derive display fields from selected batch/course
    const batch  = batches.value.find(b => b.id === form.value.batch_id)
    const course = publishedCourses.value.find(c => c.id === form.value.course_id)

    const sessionData = {
      ...form.value,
      course_name:      course?.title         ?? '',
      batch_name:       batch?.name           ?? '',
      instructor_name:  batch?.instructor_name ?? '—',
      total_enrolled:   batch?.current_enrollment ?? 0,
    }

    emit('saved', sessionData)
    emit('update:open', false)
  } finally {
    isSaving.value = false
  }
}

// Suggested meeting link prefixes
const meetPlatforms = [
  { label: 'Jitsi Meet',    prefix: 'https://meet.jit.si/' },
  { label: 'Google Meet',   prefix: 'https://meet.google.com/' },
  { label: 'Zoom',          prefix: 'https://zoom.us/j/' },
  { label: 'Microsoft Teams', prefix: 'https://teams.microsoft.com/l/meetup-join/' },
]

function applyPrefix(prefix) {
  if (!form.value.meet_link.startsWith('https://')) {
    form.value.meet_link = prefix
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Video class="h-5 w-5 text-primary" />
          {{ isEditMode ? 'Edit Session' : 'Schedule New Session' }}
        </DialogTitle>
        <DialogDescription class="text-xs">
          {{ isEditMode ? 'Update session details below.' : 'Create a live session for a batch. Students will be notified.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-5 py-2">

        <!-- Batch selector -->
        <div class="space-y-1.5">
          <Label>Batch <span class="text-destructive">*</span></Label>
          <Select v-model="form.batch_id" :disabled="isEditMode">
            <SelectTrigger :class="errors.batch_id && 'border-destructive'">
              <SelectValue placeholder="Select a batch" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="b in activeBatches"
                :key="b.id"
                :value="b.id"
              >
                <div class="flex flex-col">
                  <span class="font-medium">{{ b.name }}</span>
                  <span class="text-xs text-muted-foreground">{{ b.course_name }} · {{ b.current_enrollment }} students</span>
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="errors.batch_id" class="text-xs text-destructive">{{ errors.batch_id }}</p>
        </div>

        <!-- Session title -->
        <div class="space-y-1.5">
          <Label>Session Title <span class="text-destructive">*</span></Label>
          <Input
            v-model="form.title"
            placeholder="e.g., Week 9 – Vue 3 Composition API"
            maxlength="200"
            :class="errors.title && 'border-destructive'"
          />
          <div class="flex items-center justify-between">
            <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
            <p class="text-xs text-muted-foreground ml-auto">{{ form.title.length }}/200</p>
          </div>
        </div>

        <!-- Date + Time -->
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Date <span class="text-destructive">*</span></Label>
            <Input
              v-model="form.scheduled_date"
              type="date"
              :class="errors.scheduled_date && 'border-destructive'"
            />
            <p v-if="errors.scheduled_date" class="text-xs text-destructive">{{ errors.scheduled_date }}</p>
          </div>
          <div class="space-y-1.5">
            <Label>Start Time <span class="text-destructive">*</span></Label>
            <Input
              v-model="form.scheduled_time"
              type="time"
              :class="errors.scheduled_time && 'border-destructive'"
            />
            <p v-if="errors.scheduled_time" class="text-xs text-destructive">{{ errors.scheduled_time }}</p>
          </div>
        </div>

        <!-- Duration -->
        <div class="space-y-1.5">
          <Label>Duration (minutes) <span class="text-destructive">*</span></Label>
          <div class="flex items-center gap-2">
            <Input
              v-model.number="form.duration_minutes"
              type="number"
              min="15"
              max="480"
              class="w-32"
              :class="errors.duration_minutes && 'border-destructive'"
            />
            <div class="flex gap-1">
              <button
                v-for="d in [45, 60, 90, 120]"
                :key="d"
                type="button"
                class="rounded-md border px-2 py-1 text-xs transition-colors"
                :class="form.duration_minutes === d
                  ? 'border-primary bg-primary/10 text-primary font-semibold'
                  : 'border-border text-muted-foreground hover:border-primary/40'"
                @click="form.duration_minutes = d"
              >{{ d }}m</button>
            </div>
          </div>
          <p v-if="errors.duration_minutes" class="text-xs text-destructive">{{ errors.duration_minutes }}</p>
        </div>

        <!-- Meeting link -->
        <div class="space-y-1.5">
          <Label>Meeting Link</Label>
          <Input
            v-model="form.meet_link"
            type="url"
            placeholder="https://meet.jit.si/your-room-name"
            :class="errors.meet_link && 'border-destructive'"
          />
          <p v-if="errors.meet_link" class="text-xs text-destructive">{{ errors.meet_link }}</p>
          <!-- Quick platform shortcuts -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-[10px] text-muted-foreground">Quick:</span>
            <button
              v-for="p in meetPlatforms"
              :key="p.label"
              type="button"
              class="rounded px-1.5 py-0.5 text-[10px] border border-border hover:border-primary/40 hover:bg-muted/50 transition-colors text-muted-foreground"
              @click="applyPrefix(p.prefix)"
            >{{ p.label }}</button>
          </div>
          <p class="text-xs text-muted-foreground flex items-center gap-1">
            <AlertCircle class="h-3 w-3" />
            Create the meeting room first, then paste the link here. Students will see this link on their dashboard 15 min before class.
          </p>
        </div>

        <!-- Session notes -->
        <div class="space-y-1.5">
          <Label>Session Notes (Optional)</Label>
          <Textarea
            v-model="form.notes"
            placeholder="e.g., Students should review Week 8 code before class. Install Python 3.11 if not already done."
            :rows="3"
          />
          <p class="text-xs text-muted-foreground">Shown to students on their upcoming sessions view.</p>
        </div>

      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="isSaving" @click="$emit('update:open', false)">
          Cancel
        </Button>
        <Button :disabled="isSaving" @click="handleSave">
          <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
          <Video v-else class="mr-2 h-4 w-4" />
          {{ isSaving ? 'Saving...' : isEditMode ? 'Update Session' : 'Schedule Session' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>