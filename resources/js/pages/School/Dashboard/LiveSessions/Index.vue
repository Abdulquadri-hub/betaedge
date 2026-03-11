<script setup>

import { ref } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
    Plus, Calendar, List, Search, Video,
    ExternalLink, MoreVertical, Edit, Trash2,
    CheckCircle2, XCircle, Clock, Radio,
    Users, RefreshCw, AlertCircle,
    Zap, BookOpen
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
    Sheet, SheetContent, SheetDescription,
    SheetHeader, SheetTitle,
} from '@/components/ui/sheet'

import {
    useLiveSessionsManager,
    SESSION_STATUS,
    // ATTENDANCE_STATUS,
} from '@/composables/useLiveSessionsManager'

import CreateSessionDialog from '@/components/Dashboard/School/LiveSessions/CreateSessionDialog.vue'
import SessionCalendar from '@/components/Dashboard/School/LiveSessions/SessionCalendar.vue'
import AttendanceTracker from '@/components/Dashboard/School/LiveSessions/AttendanceTracker.vue'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'
import { toast } from 'vue-sonner'

// Composable 
const {
    sessions, filteredSessions,
    //upcomingSessions,
    liveNow,
    isLoading, error,
    search, filterStatus,
    //filterBatch, 
    statusCounts,
    formatTime,
    //formatDate, dayLabel, 
    attendanceRate,
    //getSessionById,
    createSession, updateSession, deleteSession,
    goLive, endSession, cancelSession,
} = useLiveSessionsManager()



// ─── View mode: list | calendar 
const viewMode = ref('list')

// ─── Create / edit dialog 
const showCreateDialog = ref(false)
const editingSession = ref(null)
const prefillDate = ref('')

function openCreate(date = '') {
    editingSession.value = null
    prefillDate.value = date
    showCreateDialog.value = true
}

function openEdit(session) {
    editingSession.value = { ...session }
    showCreateDialog.value = true
}

async function handleSessionSaved(sessionData) {
    if (editingSession.value) {
        // Edit mode
        await updateSession(editingSession.value.id, sessionData)
        toast({ title: 'Session updated', description: `${sessionData.title} has been updated.` })
    } else {
        // Create mode
        const result = await createSession(sessionData)
        if (result?.success !== false) {
            toast({ title: '📅 Session scheduled', description: `${sessionData.title} is scheduled. Students will be notified.` })
        }
    }
    editingSession.value = null
    showCreateDialog.value = false
}

// ─── Attendance drawer ────────────────────────────────────────────────────────
const showAttendance = ref(false)
const attendanceSession = ref(null)

function openAttendance(session) {
    attendanceSession.value = session
    showAttendance.value = true
}

// ─── Go live ──────────────────────────────────────────────────────────────────
const isGoingLive = ref(null)

async function handleGoLive(session) {
    isGoingLive.value = session.id
    try {
        await goLive(session.id)
        toast({
            title: '🔴 Session is LIVE!',
            description: 'Students can now see the "Live Now" banner on their dashboards.',
        })
    } finally {
        isGoingLive.value = null
    }
}

async function handleEndSession(session) {
    const rate = attendanceRate(session) ?? 0
    const attendees = Math.round((rate / 100) * (session.total_enrolled ?? 0))
    await endSession(session.id, attendees)
    toast({ title: 'Session ended', description: 'Attendance has been recorded.' })
}

// ─── Cancel / Delete ──────────────────────────────────────────────────────────
const showDeleteDialog = ref(false)
const deletingSession = ref(null)
const isDeleting = ref(false)
const deleteMode = ref('delete') // 'delete' | 'cancel'

function confirmAction(session, mode) {
    deletingSession.value = session
    deleteMode.value = mode
    showDeleteDialog.value = true
}

async function handleConfirmedAction() {
    if (!deletingSession.value) return
    isDeleting.value = true
    try {
        if (deleteMode.value === 'cancel') {
            await cancelSession(deletingSession.value.id)
            toast({ title: 'Session cancelled', description: 'Students have been notified.' })
        } else {
            await deleteSession(deletingSession.value.id)
            toast({ title: 'Session deleted' })
        }
        showDeleteDialog.value = false
        deletingSession.value = null
    } finally {
        isDeleting.value = false
    }
}

// ─── Status icon map ──────────────────────────────────────────────────────────
const statusIcon = {
    scheduled: Clock,
    live: Radio,
    completed: CheckCircle2,
    cancelled: XCircle,
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground tracking-tight">Live Sessions</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Schedule, manage, and track attendance for all live classes.
                    </p>
                </div>
                <Button class="gap-2 shrink-0" @click="openCreate()">
                    <Plus class="h-4 w-4" />Schedule Session
                </Button>
            </div>


            <div v-if="liveNow"
                class="flex items-center gap-4 rounded-xl border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 shadow-lg">
                    <Radio class="h-5 w-5 text-white animate-pulse" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-emerald-800 dark:text-emerald-400">LIVE NOW</p>
                    <p class="text-xs text-emerald-700 dark:text-emerald-500 truncate">{{ liveNow.title }}</p>
                    <p class="text-xs text-emerald-600/80">{{ liveNow.batch_name }} · {{ liveNow.total_enrolled }}
                        students
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button size="sm" class="gap-2 bg-emerald-600 hover:bg-emerald-700 text-white" as-child>
                        <a :href="liveNow.meet_link" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="h-4 w-4" />Join
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 text-xs" @click="openAttendance(liveNow)">
                        <Users class="h-4 w-4" />Attendance
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 text-xs border-emerald-300 text-emerald-700"
                        @click="handleEndSession(liveNow)">
                        End Session
                    </Button>
                </div>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="(cfg, status) in SESSION_STATUS" :key="status"
                    class="rounded-xl border border-border bg-card p-4 cursor-pointer hover:border-primary/30 transition-colors"
                    @click="filterStatus = status">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mb-2">
                        <component :is="statusIcon[status]" class="h-3.5 w-3.5" :class="cfg.color" />
                        {{ cfg.label }}
                    </div>
                    <p class="text-2xl font-bold text-foreground">{{ statusCounts[status] }}</p>
                </div>
            </div>

            <!-- Toolbar: tabs + search + view toggle -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Tabs :model-value="filterStatus" class="flex-1" @update:model-value="filterStatus = $event">
                    <TabsList>
                        <TabsTrigger value="all">
                            All
                            <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ statusCounts.all }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="scheduled">Upcoming</TabsTrigger>
                        <TabsTrigger value="live">Live</TabsTrigger>
                        <TabsTrigger value="completed">Past</TabsTrigger>
                    </TabsList>
                </Tabs>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-56">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search sessions..." class="pl-9" />
                    </div>

                    <!-- View toggle -->
                    <div class="flex items-center rounded-lg border border-border bg-muted/30 p-1 gap-1">
                        <button v-for="(icon, mode) in { list: List, calendar: Calendar }" :key="mode" type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-md transition-all"
                            :class="viewMode === mode ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="viewMode = mode">
                            <component :is="icon" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── CALENDAR VIEW ───────────────────────────────────────────────────── -->
            <SessionCalendar v-if="viewMode === 'calendar'" :sessions="sessions" @select="openAttendance"
                @create="openCreate" />

            <!-- ── LIST VIEW ──────────────────────────────────────────────────────── -->
            <template v-else>

                <!-- Skeleton -->
                <div v-if="isLoading" class="space-y-3">
                    <div v-for="i in 4" :key="i" class="h-24 bg-muted rounded-xl animate-pulse" />
                </div>

                <!-- Error -->
                <div v-else-if="error"
                    class="flex items-center gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3">
                    <AlertCircle class="h-4 w-4 text-destructive shrink-0" />
                    <p class="text-sm text-destructive">{{ error }}</p>
                </div>

                <!-- Empty -->
                <div v-else-if="filteredSessions.length === 0"
                    class="py-16 text-center rounded-xl border border-dashed border-border">
                    <Video class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p class="text-sm font-medium text-foreground">No sessions found</p>
                    <p class="text-xs text-muted-foreground mt-1">Schedule your first live session to get started.</p>
                    <Button class="mt-4 gap-2" size="sm" @click="openCreate()">
                        <Plus class="h-4 w-4" />Schedule Session
                    </Button>
                </div>

                <!-- Sessions list -->
                <div v-else class="space-y-3">
                    <Card v-for="session in filteredSessions" :key="session.id"
                        class="group transition-all duration-200 hover:shadow-md hover:border-primary/20"
                        :class="session.status === 'live' && 'border-emerald-400 shadow-emerald-100 dark:shadow-none'">
                        <CardContent class="p-5">
                            <div class="flex items-start gap-4">

                                <!-- Date block -->
                                <div
                                    class="flex flex-col items-center justify-center w-14 shrink-0 rounded-xl border border-border bg-muted/30 py-2 px-1 text-center">
                                    <p class="text-[10px] text-muted-foreground font-medium uppercase">
                                        {{ new Date(session.scheduled_date).toLocaleString('en', { month: 'short' }) }}
                                    </p>
                                    <p class="text-xl font-black text-foreground leading-none">
                                        {{ new Date(session.scheduled_date).getDate() }}
                                    </p>
                                    <p class="text-[9px] text-muted-foreground mt-0.5 font-medium">
                                        {{ new Date(session.scheduled_date).toLocaleString('en', { weekday: 'short' })
                                        }}
                                    </p>
                                </div>

                                <!-- Main info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <!-- Live dot -->
                                        <span v-if="session.status === 'live'"
                                            class="flex items-center gap-1 text-xs font-bold text-emerald-600">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />LIVE
                                        </span>

                                        <p
                                            class="text-sm font-semibold text-foreground group-hover:text-primary transition-colors">
                                            {{ session.title }}
                                        </p>

                                        <Badge :variant="SESSION_STATUS[session.status]?.variant" class="text-xs gap-1">
                                            <component :is="statusIcon[session.status]" class="h-3 w-3" />
                                            {{ SESSION_STATUS[session.status]?.label }}
                                        </Badge>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Clock class="h-3 w-3" />
                                            {{ formatTime(session.scheduled_time) }} · {{ session.duration_minutes }}min
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <BookOpen class="h-3 w-3" />{{ session.course_name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Users class="h-3 w-3" />{{ session.batch_name }}
                                        </span>
                                        <span v-if="session.instructor_name" class="text-muted-foreground/60">
                                            {{ session.instructor_name }}
                                        </span>
                                    </div>

                                    <!-- Notes preview -->
                                    <p v-if="session.notes"
                                        class="text-xs text-muted-foreground/70 mt-1.5 italic line-clamp-1">
                                        {{ session.notes }}
                                    </p>

                                    <!-- Completed: attendance + recording -->
                                    <div v-if="session.status === 'completed'" class="flex items-center gap-3 mt-2">
                                        <span class="flex items-center gap-1 text-xs text-foreground font-medium">
                                            <Users class="h-3 w-3 text-primary" />
                                            {{ session.total_attendees }}/{{ session.total_enrolled }} attended
                                            <span
                                                :class="['font-bold', (attendanceRate(session) ?? 0) >= 70 ? 'text-emerald-600' : 'text-amber-600']">
                                                ({{ attendanceRate(session) }}%)
                                            </span>
                                        </span>
                                        <a v-if="session.recording_url" :href="session.recording_url" target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center gap-1 text-xs text-secondary hover:underline">
                                            <Video class="h-3 w-3" />Recording
                                        </a>
                                    </div>
                                </div>

                                <!-- Right-side actions -->
                                <div class="flex items-center gap-2 shrink-0">

                                    <!-- Meet link -->
                                    <Button
                                        v-if="session.meet_link && (session.status === 'scheduled' || session.status === 'live')"
                                        variant="outline" size="sm" class="gap-1.5 text-xs h-8" as-child>
                                        <a :href="session.meet_link" target="_blank" rel="noopener noreferrer">
                                            <ExternalLink class="h-3.5 w-3.5" />Join
                                        </a>
                                    </Button>

                                    <!-- Go Live button -->
                                    <Button v-if="session.status === 'scheduled'" size="sm"
                                        class="gap-1.5 text-xs h-8 bg-emerald-600 hover:bg-emerald-700 text-white"
                                        :disabled="isGoingLive === session.id" @click="handleGoLive(session)">
                                        <RefreshCw v-if="isGoingLive === session.id" class="h-3.5 w-3.5 animate-spin" />
                                        <Zap v-else class="h-3.5 w-3.5" />
                                        {{ isGoingLive === session.id ? 'Starting...' : 'Go Live' }}
                                    </Button>

                                    <!-- Attendance for past sessions -->
                                    <Button v-if="session.status === 'completed'" variant="outline" size="sm"
                                        class="gap-1.5 text-xs h-8" @click="openAttendance(session)">
                                        <Users class="h-3.5 w-3.5" />Attendance
                                    </Button>

                                    <!-- Dropdown -->
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon"
                                                class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                v-if="session.status === 'scheduled' || session.status === 'live'"
                                                @click="openEdit(session)">
                                                <Edit class="mr-2 h-4 w-4" />Edit Session
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="session.status === 'live'"
                                                @click="handleEndSession(session)">
                                                <CheckCircle2 class="mr-2 h-4 w-4 text-emerald-600" />End Session
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openAttendance(session)">
                                                <Users class="mr-2 h-4 w-4" />
                                                {{ session.status === 'completed' ? 'View Attendance' : 'Mark Attendance' }}
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem v-if="session.status === 'scheduled'"
                                                class="text-destructive focus:text-destructive"
                                                @click="confirmAction(session, 'cancel')">
                                                <XCircle class="mr-2 h-4 w-4" />Cancel Session
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="session.status === 'cancelled' || session.status === 'completed'"
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
            </template>

            <!-- ── CREATE / EDIT DIALOG ───────────────────────────────────────────── -->
            <CreateSessionDialog :open="showCreateDialog" :session="editingSession"
                @update:open="showCreateDialog = $event" @saved="handleSessionSaved" />

            <!-- ── ATTENDANCE SHEET ───────────────────────────────────────────────── -->
            <Sheet :open="showAttendance" @update:open="showAttendance = $event">
                <SheetContent class="w-full sm:max-w-lg overflow-y-auto">
                    <SheetHeader class="mb-4">
                        <SheetTitle class="text-base">Attendance</SheetTitle>
                        <SheetDescription class="text-xs">
                            {{ attendanceSession?.status === 'completed' ? 'View attendance records for this session.' :
                                'Mark attendance for students currently in class.' }}
                        </SheetDescription>
                    </SheetHeader>
                    <AttendanceTracker v-if="attendanceSession" :session-id="attendanceSession.id"
                        :readonly="attendanceSession.status === 'completed'" @saved="showAttendance = false" />
                </SheetContent>
            </Sheet>

            <!-- ── CANCEL / DELETE DIALOG ─────────────────────────────────────────── -->
            <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {{ deleteMode === 'cancel' ? 'Cancel this session?' : 'Delete this session?' }}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong class="text-foreground">{{ deletingSession?.title }}</strong>
                            <template v-if="deleteMode === 'cancel'">
                                will be marked as cancelled. All enrolled students will be notified via email. This
                                cannot
                                be undone.
                            </template>
                            <template v-else>
                                will be permanently deleted along with all attendance records.
                            </template>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isDeleting">Keep Session</AlertDialogCancel>
                        <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="isDeleting" @click="handleConfirmedAction">
                            <RefreshCw v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isDeleting ? 'Processing...' : deleteMode === 'cancel' ? 'Yes, Cancel Session' : 'Delete'
                            }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </DashboardLayout>
</template>