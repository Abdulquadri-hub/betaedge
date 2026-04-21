<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, Search, Video, ExternalLink, MoreVertical,
    Edit, Trash2, CheckCircle2, XCircle, Clock, Radio,
    Users, RefreshCw, Zap, BookOpen,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription,
} from '@/components/ui/dialog'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    sessions:    { type: Array,  default: () => [] },
    liveNow:     { type: Object, default: null      },
    stats:       { type: Object, default: () => ({}) },
    upcoming:    { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
    batches:     { type: Array,  default: () => [] }, // [{id, name, courses:[{id,title,...}]}]
    pagination:  { type: Object, default: null      },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

// ── Filtering ─────────────────────────────────────────────────────────────────
const search       = ref(props.filters.search ?? '')
const filterStatus = ref(props.filters.status  ?? 'all')

const filteredSessions = computed(() => {
    let list = props.sessions
    if (filterStatus.value !== 'all') {
        list = list.filter(s => s.status === filterStatus.value)
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            s.title?.toLowerCase().includes(q) ||
            s.course_name?.toLowerCase().includes(q) ||
            s.batch_name?.toLowerCase().includes(q)
        )
    }
    return list
})

const statusCounts = computed(() => ({
    all:       props.sessions.length,
    scheduled: props.sessions.filter(s => s.status === 'scheduled').length,
    live:      props.sessions.filter(s => s.status === 'live').length,
    completed: props.sessions.filter(s => s.status === 'completed').length,
    cancelled: props.sessions.filter(s => s.status === 'cancelled').length,
}))

const STATUS_CONFIG = {
    scheduled: { label: 'Scheduled', variant: 'outline',     icon: Clock         },
    live:      { label: 'Live',      variant: 'default',     icon: Radio         },
    completed: { label: 'Completed', variant: 'secondary',   icon: CheckCircle2  },
    cancelled: { label: 'Cancelled', variant: 'destructive', icon: XCircle       },
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function fmtTime(t) {
    if (!t) return '—'
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

function fmtShortDate(iso) {
    if (!iso) return { month: '—', day: '—', dow: '—' }
    const d = new Date(iso)
    return {
        month: d.toLocaleString('en', { month: 'short' }),
        day:   d.getDate(),
        dow:   d.toLocaleString('en', { weekday: 'short' }),
    }
}

// ── Create dialog ──────────────────────────────────────────────────────────────
const showCreateDialog = ref(false)
const showEditDialog   = ref(false)
const editingSession   = ref(null)
const isSubmitting     = ref(false)

const sessionForm = ref({
    batch_id:         '',
    course_id:        '',
    title:            '',
    scheduled_date:   '',
    scheduled_time:   '',
    duration_minutes: 90,
    meet_link:        '',
    notes:            '',
})
const sessionErrors = ref({})

// Courses available for selected batch
const batchCourses = computed(() => {
    if (!sessionForm.value.batch_id) return []
    const batch = props.batches.find(b => b.id == sessionForm.value.batch_id)
    return batch?.courses ?? []
})

function onBatchChange(batchId) {
    sessionForm.value.batch_id  = batchId
    sessionForm.value.course_id = ''
    // Auto-fill title with batch name
    const batch = props.batches.find(b => b.id == batchId)
    if (batch && !sessionForm.value.title) {
        sessionForm.value.title = `${batch.name} — Session`
    }
}

function onCourseChange(courseId) {
    sessionForm.value.course_id = courseId
    // Auto-fill schedule from batch_courses pivot
    const course = batchCourses.value.find(c => c.id == courseId)
    if (course) {
        if (course.session_time)      sessionForm.value.scheduled_time   = course.session_time
        if (course.duration_minutes)  sessionForm.value.duration_minutes = course.duration_minutes
        // Auto-title: "CourseName — Session"
        const batch = props.batches.find(b => b.id == sessionForm.value.batch_id)
        const batchPart = batch?.name ?? ''
        sessionForm.value.title = `${batchPart} — ${course.title}`
    }
}

function openCreate() {
    sessionForm.value    = { batch_id: '', course_id: '', title: '', scheduled_date: '',
                             scheduled_time: '', duration_minutes: 90, meet_link: '', notes: '' }
    sessionErrors.value  = {}
    showCreateDialog.value = true
}

function openEdit(session) {
    editingSession.value = session
    sessionForm.value = {
        batch_id:         session.batch_id,
        course_id:        session.course_id,
        title:            session.title,
        scheduled_date:   session.scheduled_date,
        scheduled_time:   session.scheduled_time,
        duration_minutes: session.duration_minutes,
        meet_link:        session.meet_link ?? '',
        notes:            session.notes ?? '',
    }
    sessionErrors.value  = {}
    showEditDialog.value = true
}

function handleCreate() {
    sessionErrors.value = {}
    if (!sessionForm.value.batch_id)       { sessionErrors.value.batch_id  = 'Select a batch'; return }
    if (!sessionForm.value.course_id)      { sessionErrors.value.course_id = 'Select a subject'; return }
    if (!sessionForm.value.title?.trim())  { sessionErrors.value.title     = 'Title is required'; return }
    if (!sessionForm.value.scheduled_date) { sessionErrors.value.scheduled_date = 'Date is required'; return }
    if (!sessionForm.value.scheduled_time) { sessionErrors.value.scheduled_time = 'Time is required'; return }

    isSubmitting.value = true
    router.post('/dashboard/live-sessions', { ...sessionForm.value }, {
        onSuccess: () => { showCreateDialog.value = false },
        onError:   (errs) => { Object.assign(sessionErrors.value, errs) },
        onFinish:  () => { isSubmitting.value = false },
    })
}

function handleUpdate() {
    if (!editingSession.value) return
    isSubmitting.value = true
    router.put(`/dashboard/live-sessions/${editingSession.value.id}`, { ...sessionForm.value }, {
        onSuccess: () => { showEditDialog.value = false },
        onError:   (errs) => { Object.assign(sessionErrors.value, errs) },
        onFinish:  () => { isSubmitting.value = false },
    })
}

// ── Session actions ────────────────────────────────────────────────────────────
const isGoingLive      = ref(null)
const isEndingSession  = ref(null)

function handleGoLive(session) {
    isGoingLive.value = session.id
    router.post(`/dashboard/live-sessions/${session.id}/go-live`, {}, {
        preserveScroll: true,
        onFinish: () => { isGoingLive.value = null },
    })
}

function handleEndSession(session) {
    isEndingSession.value = session.id
    router.post(`/dashboard/live-sessions/${session.id}/end`,
        { total_attendees: session.total_enrolled },
        { preserveScroll: true, onFinish: () => { isEndingSession.value = null } }
    )
}

// ── Cancel / Delete dialog ────────────────────────────────────────────────────
const showActionDialog = ref(false)
const actionSession    = ref(null)
const actionMode       = ref('cancel')
const isProcessing     = ref(false)

function confirmAction(session, mode) {
    actionSession.value    = session
    actionMode.value       = mode
    showActionDialog.value = true
}

function handleConfirmedAction() {
    if (!actionSession.value) return
    isProcessing.value = true
    const url    = actionMode.value === 'cancel'
        ? `/dashboard/live-sessions/${actionSession.value.id}/cancel`
        : `/dashboard/live-sessions/${actionSession.value.id}`
    const method = actionMode.value === 'delete' ? 'delete' : 'post'

    router[method](url, {}, {
        onSuccess: () => { showActionDialog.value = false; actionSession.value = null },
        onFinish:  () => { isProcessing.value = false },
    })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Live Sessions</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Schedule, manage, and track attendance for all live classes.
                    </p>
                </div>
                <Button class="gap-2 shrink-0" @click="openCreate">
                    <Plus class="h-4 w-4" />Schedule Session
                </Button>
            </div>

            <!-- Live now banner -->
            <div v-if="liveNow"
                class="flex items-center gap-4 rounded-xl border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 shadow-lg">
                    <Radio class="h-5 w-5 text-white animate-pulse" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-emerald-800 dark:text-emerald-400">LIVE NOW</p>
                    <p class="text-xs text-emerald-700 truncate">{{ liveNow.title }}</p>
                    <p class="text-xs text-emerald-600/80">
                        {{ liveNow.batch_name }}
                        <span v-if="liveNow.course_name"> · {{ liveNow.course_name }}</span>
                        · {{ liveNow.total_enrolled }} students
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="liveNow.meet_link" size="sm"
                        class="gap-2 bg-emerald-600 hover:bg-emerald-700 text-white" as-child>
                        <a :href="liveNow.meet_link" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="h-4 w-4" />Join
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" class="border-emerald-300 text-emerald-700 text-xs"
                        :disabled="isEndingSession === liveNow.id"
                        @click="handleEndSession(liveNow)">
                        <RefreshCw v-if="isEndingSession === liveNow.id" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                        End Session
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="(cfg, status) in STATUS_CONFIG" :key="status"
                    class="rounded-xl border border-border bg-card p-4 cursor-pointer hover:border-primary/30 transition-colors"
                    @click="filterStatus = status">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mb-2">
                        <component :is="cfg.icon" class="h-3.5 w-3.5" />{{ cfg.label }}
                    </div>
                    <p class="text-2xl font-bold text-foreground">{{ statusCounts[status] ?? 0 }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Tabs :model-value="filterStatus" class="flex-1" @update:model-value="filterStatus = $event">
                    <TabsList>
                        <TabsTrigger value="all">All
                            <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ statusCounts.all }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="scheduled">Upcoming</TabsTrigger>
                        <TabsTrigger value="live">Live</TabsTrigger>
                        <TabsTrigger value="completed">Past</TabsTrigger>
                    </TabsList>
                </Tabs>
                <div class="relative w-full sm:w-56">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search sessions…" class="pl-9" />
                </div>
            </div>

            <!-- Empty -->
            <div v-if="filteredSessions.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
                <Video class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                <p class="text-sm font-medium text-foreground">No sessions found</p>
                <p class="text-xs text-muted-foreground mt-1">Schedule your first live class to get started.</p>
                <Button class="mt-4 gap-2" size="sm" @click="openCreate">
                    <Plus class="h-4 w-4" />Schedule Session
                </Button>
            </div>

            <!-- Session list -->
            <div v-else class="space-y-3">
                <Card v-for="session in filteredSessions" :key="session.id"
                    class="group transition-all duration-200 hover:shadow-md hover:border-primary/20"
                    :class="session.status === 'live' && 'border-emerald-400'">
                    <CardContent class="p-5">
                        <div class="flex items-start gap-4">
                            <!-- Date block -->
                            <div class="flex flex-col items-center justify-center w-14 shrink-0 rounded-xl border border-border bg-muted/30 py-2 px-1 text-center">
                                <p class="text-[10px] text-muted-foreground font-medium uppercase">
                                    {{ fmtShortDate(session.scheduled_date).month }}
                                </p>
                                <p class="text-xl font-black text-foreground leading-none">
                                    {{ fmtShortDate(session.scheduled_date).day }}
                                </p>
                                <p class="text-[9px] text-muted-foreground mt-0.5 font-medium">
                                    {{ fmtShortDate(session.scheduled_date).dow }}
                                </p>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span v-if="session.status === 'live'"
                                        class="flex items-center gap-1 text-xs font-bold text-emerald-600">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />LIVE
                                    </span>
                                    <p class="text-sm font-semibold text-foreground group-hover:text-primary transition-colors">
                                        {{ session.title }}
                                    </p>
                                    <Badge :variant="STATUS_CONFIG[session.status]?.variant ?? 'outline'" class="text-xs gap-1">
                                        <component :is="STATUS_CONFIG[session.status]?.icon" class="h-3 w-3" />
                                        {{ STATUS_CONFIG[session.status]?.label }}
                                    </Badge>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />{{ fmtTime(session.scheduled_time) }} · {{ session.duration_minutes }}min
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <BookOpen class="h-3 w-3" />{{ session.course_name }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Users class="h-3 w-3" />{{ session.batch_name }}
                                    </span>
                                </div>
                                <!-- Attendance for completed -->
                                <div v-if="session.status === 'completed'" class="flex items-center gap-3 mt-1.5">
                                    <span class="text-xs text-foreground font-medium">
                                        {{ session.total_attendees }}/{{ session.total_enrolled }} attended
                                        <span v-if="session.attendance_rate !== null"
                                            :class="['font-bold ml-1', session.attendance_rate >= 70 ? 'text-emerald-600' : 'text-amber-600']">
                                            ({{ session.attendance_rate }}%)
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <Button v-if="session.meet_link && ['scheduled','live'].includes(session.status)"
                                    variant="outline" size="sm" class="gap-1.5 text-xs h-8" as-child>
                                    <a :href="session.meet_link" target="_blank" rel="noopener noreferrer">
                                        <ExternalLink class="h-3.5 w-3.5" />Join
                                    </a>
                                </Button>
                                <Button v-if="session.status === 'scheduled'" size="sm"
                                    class="gap-1.5 text-xs h-8 bg-emerald-600 hover:bg-emerald-700 text-white"
                                    :disabled="isGoingLive === session.id" @click="handleGoLive(session)">
                                    <RefreshCw v-if="isGoingLive === session.id" class="h-3.5 w-3.5 animate-spin" />
                                    <Zap v-else class="h-3.5 w-3.5" />
                                    {{ isGoingLive === session.id ? 'Starting…' : 'Go Live' }}
                                </Button>
                                <Button v-if="session.status === 'live'" variant="outline" size="sm"
                                    class="gap-1.5 text-xs h-8"
                                    :disabled="isEndingSession === session.id"
                                    @click="handleEndSession(session)">
                                    <RefreshCw v-if="isEndingSession === session.id" class="h-3.5 w-3.5 animate-spin" />
                                    End
                                </Button>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            v-if="['scheduled','live'].includes(session.status)"
                                            @click="openEdit(session)">
                                            <Edit class="mr-2 h-4 w-4" />Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem v-if="session.status === 'scheduled'"
                                            class="text-destructive focus:text-destructive"
                                            @click="confirmAction(session, 'cancel')">
                                            <XCircle class="mr-2 h-4 w-4" />Cancel
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="['cancelled','completed'].includes(session.status)"
                                            class="text-destructive focus:text-destructive"
                                            @click="confirmAction(session, 'delete')">
                                            <Trash2 class="mr-2 h-4 w-4" />Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Create dialog -->
        <Dialog :open="showCreateDialog" @update:open="showCreateDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Video class="h-5 w-5 text-primary" />Schedule Live Session
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Select the batch and subject for this session. Schedule auto-fills from your batch settings.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <!-- Batch -->
                    <div class="space-y-1.5">
                        <Label>Batch <span class="text-destructive">*</span></Label>
                        <Select :model-value="String(sessionForm.batch_id || '')"
                            @update:model-value="onBatchChange">
                            <SelectTrigger :class="sessionErrors.batch_id && 'border-destructive'">
                                <SelectValue placeholder="Select a batch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="b in props.batches" :key="b.id" :value="String(b.id)">
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="sessionErrors.batch_id" class="text-xs text-destructive">{{ sessionErrors.batch_id }}</p>
                    </div>

                    <!-- Course (subject) — only shown after batch selected -->
                    <div v-if="sessionForm.batch_id" class="space-y-1.5">
                        <Label>Subject <span class="text-destructive">*</span></Label>
                        <Select :model-value="String(sessionForm.course_id || '')"
                            @update:model-value="onCourseChange">
                            <SelectTrigger :class="sessionErrors.course_id && 'border-destructive'">
                                <SelectValue placeholder="Which subject?" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in batchCourses" :key="c.id" :value="String(c.id)">
                                    {{ c.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="sessionErrors.course_id" class="text-xs text-destructive">{{ sessionErrors.course_id }}</p>
                        <p v-if="batchCourses.length === 0" class="text-xs text-amber-600">
                            This batch has no courses yet.
                            <button type="button" class="underline" @click="router.visit('/dashboard/batches')">Add courses</button>
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Session Title <span class="text-destructive">*</span></Label>
                        <Input v-model="sessionForm.title" placeholder="e.g., Week 3 — Fractions"
                            :class="sessionErrors.title && 'border-destructive'" />
                        <p v-if="sessionErrors.title" class="text-xs text-destructive">{{ sessionErrors.title }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Date <span class="text-destructive">*</span></Label>
                            <Input v-model="sessionForm.scheduled_date" type="date"
                                :class="sessionErrors.scheduled_date && 'border-destructive'" />
                            <p v-if="sessionErrors.scheduled_date" class="text-xs text-destructive">{{ sessionErrors.scheduled_date }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Time <span class="text-destructive">*</span></Label>
                            <Input v-model="sessionForm.scheduled_time" type="time"
                                :class="sessionErrors.scheduled_time && 'border-destructive'" />
                            <p v-if="sessionErrors.scheduled_time" class="text-xs text-destructive">{{ sessionErrors.scheduled_time }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Duration (minutes)</Label>
                        <Input v-model.number="sessionForm.duration_minutes" type="number" min="15" max="480" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Meeting Link <span class="text-muted-foreground text-xs">(add before class)</span></Label>
                        <Input v-model="sessionForm.meet_link" placeholder="https://meet.google.com/…" type="url" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Notes <span class="text-muted-foreground text-xs">(optional)</span></Label>
                        <Input v-model="sessionForm.notes" placeholder="Topic for this session" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="isSubmitting" @click="showCreateDialog = false">Cancel</Button>
                    <Button :disabled="isSubmitting" @click="handleCreate">
                        <RefreshCw v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Scheduling…' : 'Schedule Session' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit dialog -->
        <Dialog :open="showEditDialog" @update:open="showEditDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Edit class="h-5 w-5 text-primary" />Edit Session
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Title</Label>
                        <Input v-model="sessionForm.title" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Date</Label>
                            <Input v-model="sessionForm.scheduled_date" type="date" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Time</Label>
                            <Input v-model="sessionForm.scheduled_time" type="time" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Duration (minutes)</Label>
                        <Input v-model.number="sessionForm.duration_minutes" type="number" min="15" max="480" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Meeting Link</Label>
                        <Input v-model="sessionForm.meet_link" placeholder="https://meet.google.com/…" type="url" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Notes</Label>
                        <Input v-model="sessionForm.notes" placeholder="Session topic" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="isSubmitting" @click="showEditDialog = false">Cancel</Button>
                    <Button :disabled="isSubmitting" @click="handleUpdate">
                        <RefreshCw v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Cancel / Delete confirmation -->
        <AlertDialog :open="showActionDialog" @update:open="showActionDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ actionMode === 'cancel' ? 'Cancel this session?' : 'Delete this session?' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong class="text-foreground">{{ actionSession?.title }}</strong>
                        {{ actionMode === 'cancel'
                            ? ' will be marked as cancelled. This cannot be undone.'
                            : ' will be permanently deleted.' }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isProcessing">Keep</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isProcessing" @click="handleConfirmedAction">
                        <RefreshCw v-if="isProcessing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isProcessing ? 'Processing…' : actionMode === 'cancel' ? 'Cancel Session' : 'Delete' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </DashboardLayout>
</template>