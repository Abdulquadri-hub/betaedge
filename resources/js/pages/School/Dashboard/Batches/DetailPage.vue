<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    ArrowLeft, Users, Calendar, Clock, MessageCircle,
    Video, Edit, MoreVertical, Search, Download, Trophy,
    Medal, Award, CheckCircle2, XCircle, AlertCircle,
    Eye, FileText, TrendingUp, Star, ExternalLink,
    UserCheck, Lock, Unlock, Plus, RefreshCw,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table'
import { toast } from "vue-sonner"
import { useDashboardBatches } from '@/composables/useDashboardBatches'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

// ─── Props (from Laravel Inertia) ────────────────────────────────────────────
/**
 * TODO (Laravel 12): uncomment and remove mock below
 * const props = defineProps({
 *   batch:    { type: Object, required: true },
 *   students: { type: Array,  default: () => [] },
 *   sessions: { type: Array,  default: () => [] },
 * })
 */

// ─── Composable ──────────────────────────────────────────────────────────────
const { getBatchById, getBatchStudents, formatTime, formatNaira, enrollmentPct, isFull } = useDashboardBatches()

// ─── Active tab ───────────────────────────────────────────────────────────────
const activeTab = ref('students')

// ─── Mock: get batch from URL param ──────────────────────────────────────────
// TODO (Laravel 12): replace with props.batch
const batchId = ref('batch-001') // In real app: from route param via usePage().props or useRoute()
const batch = computed(() => getBatchById(batchId.value))

// ─── Students data ────────────────────────────────────────────────────────────
const students = ref([])
const studentsLoading = ref(true)
const studentSearch = ref('')

onMounted(async () => {
    students.value = await getBatchStudents(batchId.value)
    studentsLoading.value = false
})

const filteredStudents = computed(() => {
    const q = studentSearch.value.trim().toLowerCase()
    if (!q) return students.value
    return students.value.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.email.toLowerCase().includes(q)
    )
})

// ─── Sessions mock data (aligned with live_sessions table) ───────────────────
const sessions = ref([
    {
        id: 'sess-001',
        title: 'Week 1: Environment Setup & HTML Basics',
        scheduled_at: new Date(Date.now() - 7 * 86400000).toISOString(),
        duration: 90,
        platform: 'jitsi',
        status: 'completed',
        meeting_link: null,
        enrolled_count: 24,
        attendee_count: 21,
    },
    {
        id: 'sess-002',
        title: 'Week 2: CSS Fundamentals & Flexbox',
        scheduled_at: new Date(Date.now() + 0.5 * 86400000).toISOString(),
        duration: 90,
        platform: 'jitsi',
        status: 'scheduled',
        meeting_link: 'https://meet.jit.si/teach-batch-001-w2',
        enrolled_count: 24,
        attendee_count: null,
    },
    {
        id: 'sess-003',
        title: 'Week 3: JavaScript Basics',
        scheduled_at: new Date(Date.now() + 7 * 86400000).toISOString(),
        duration: 90,
        platform: 'jitsi',
        status: 'scheduled',
        meeting_link: null,
        enrolled_count: 24,
        attendee_count: null,
    },
])

// ─── Leaderboard computed from students ──────────────────────────────────────
const leaderboard = computed(() =>
    [...students.value]
        .sort((a, b) => (b.grade ?? 0) - (a.grade ?? 0))
        .map((s, i) => ({ ...s, rank: i + 1 }))
)

// ─── Materials mock (from course_materials table) ─────────────────────────────
const materials = ref([
    { id: 'mat-001', name: 'Course Outline & Curriculum', type: 'pdf', module: 'Week 1', size: '1.2 MB', uploaded_at: '2026-03-01', downloads: 22 },
    { id: 'mat-002', name: 'HTML Cheat Sheet', type: 'pdf', module: 'Week 1', size: '0.4 MB', uploaded_at: '2026-03-01', downloads: 20 },
    { id: 'mat-003', name: 'CSS Flexbox Practice Exercise', type: 'pdf', module: 'Week 2', size: '0.8 MB', uploaded_at: '2026-03-08', downloads: 15 },
    { id: 'mat-004', name: 'JavaScript Exercises Pack', type: 'pdf', module: 'Week 3', size: '2.1 MB', uploaded_at: '2026-03-15', downloads: 0 },
])

// ─── Helpers ──────────────────────────────────────────────────────────────────
function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtDateTime(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString('en-NG', {
        day: 'numeric', month: 'short',
        hour: '2-digit', minute: '2-digit',
    })
}

function gradeColor(grade) {
    if (grade >= 90) return 'text-emerald-600'
    if (grade >= 80) return 'text-primary'
    if (grade >= 70) return 'text-amber-600'
    if (grade >= 60) return 'text-orange-600'
    return 'text-destructive'
}

function gradeLabel(grade) {
    if (grade == null) return '—'
    if (grade >= 90) return 'A+'
    if (grade >= 80) return 'A'
    if (grade >= 70) return 'B'
    if (grade >= 60) return 'C'
    return 'F'
}

function rankIcon(rank) {
    if (rank === 1) return Trophy
    if (rank === 2) return Medal
    if (rank === 3) return Award
    return null
}

function rankColor(rank) {
    if (rank === 1) return 'text-amber-500'
    if (rank === 2) return 'text-zinc-400'
    if (rank === 3) return 'text-amber-700'
    return 'text-muted-foreground'
}

const platformLabel = { jitsi: 'Jitsi Meet', zoom: 'Zoom', custom: 'Custom' }

const sessionStatusConfig = {
    scheduled: { label: 'Scheduled', variant: 'outline' },
    live: { label: 'Live Now', variant: 'default' },
    completed: { label: 'Done', variant: 'secondary' },
    cancelled: { label: 'Cancelled', variant: 'destructive' },
}

// ─── Actions ──────────────────────────────────────────────────────────────────
function handleExportStudents() {
    /**
     * TODO (Laravel 12):
     * window.location.href = route('dashboard.batches.students.export', batch.value.id)
     */
    toast({ title: 'Export started', description: 'Student list will download shortly.' })
}

function handleGenerateCertificates() {
    /**
     * TODO (Laravel 12):
     * router.post(route('dashboard.batches.certificates.generate', batch.value.id))
     */
    toast({ title: 'Certificates generating', description: 'This may take a few seconds.' })
}

function handleWhatsApp() {
    if (batch.value?.whatsapp_link) {
        window.open(batch.value.whatsapp_link, '_blank', 'noopener,noreferrer')
    }
}
</script>

<template>
    <DashboardLayout>
        <div v-if="batch" class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- ── Back + Header ─────────────────────────────────────────────────── -->
            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/batches')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground tracking-tight">{{ batch.name }}</h1>
                        <Badge variant="default" class="text-xs">{{ batch.status }}</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ batch.course_name }}</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="batch.whatsapp_link" variant="outline" size="sm"
                        class="gap-2 text-emerald-600 border-emerald-200 hover:bg-emerald-50" @click="handleWhatsApp">
                        <MessageCircle class="h-4 w-4" />
                        WhatsApp
                    </Button>
                    <Button v-if="batch.status === 'completed'" size="sm" class="gap-2"
                        @click="handleGenerateCertificates">
                        <Award class="h-4 w-4" />
                        Generate Certificates
                    </Button>
                </div>
            </div>

            <!-- ── Stat Cards ─────────────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Enrollment -->
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Enrolled</p>
                        <p class="text-2xl font-bold text-foreground mt-1">
                            {{ batch.current_enrollment }}<span class="text-sm font-normal text-muted-foreground">/{{
                                batch.max_students }}</span>
                        </p>
                        <Progress :value="enrollmentPct(batch)" class="h-1 mt-2" />
                    </CardContent>
                </Card>

                <!-- Dates -->
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Duration</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ fmtDate(batch.start_date) }}</p>
                        <p class="text-xs text-muted-foreground">→ {{ fmtDate(batch.end_date) }}</p>
                    </CardContent>
                </Card>

                <!-- Schedule -->
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Schedule</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ batch.schedule_day || '—' }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatTime(batch.schedule_time) }}</p>
                    </CardContent>
                </Card>

                <!-- Revenue -->
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Batch Revenue</p>
                        <p class="text-sm font-bold text-foreground mt-1">
                            {{ formatNaira(batch.price_per_student * batch.current_enrollment) }}
                        </p>
                        <p class="text-xs text-muted-foreground">{{ formatNaira(batch.price_per_student) }} × {{
                            batch.current_enrollment }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Tabs ───────────────────────────────────────────────────────────── -->
            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="students" class="gap-2">
                        <Users class="h-4 w-4" />
                        Students
                        <Badge variant="secondary" class="h-4 px-1.5 text-[10px]">{{ students.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="sessions" class="gap-2">
                        <Video class="h-4 w-4" />
                        Sessions
                        <Badge variant="secondary" class="h-4 px-1.5 text-[10px]">{{ sessions.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="leaderboard" class="gap-2">
                        <Trophy class="h-4 w-4" />
                        Leaderboard
                    </TabsTrigger>
                    <TabsTrigger value="materials" class="gap-2">
                        <FileText class="h-4 w-4" />
                        Materials
                        <Badge variant="secondary" class="h-4 px-1.5 text-[10px]">{{ materials.length }}</Badge>
                    </TabsTrigger>
                </TabsList>

                <!-- ── STUDENTS TAB ─────────────────────────────────────────────────── -->
                <TabsContent value="students" class="mt-4">
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="relative flex-1 max-w-sm">
                                    <Search
                                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input v-model="studentSearch" placeholder="Search students..." class="pl-9 h-9" />
                                </div>
                                <Button variant="outline" size="sm" class="gap-2" @click="handleExportStudents">
                                    <Download class="h-4 w-4" />
                                    Export
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="studentsLoading" class="space-y-3 py-4">
                                <div v-for="i in 4" :key="i" class="h-12 bg-muted rounded animate-pulse" />
                            </div>

                            <div v-else-if="filteredStudents.length === 0" class="py-12 text-center">
                                <Users class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-sm text-muted-foreground">No students found</p>
                            </div>

                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Student</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead class="text-center">Attendance</TableHead>
                                        <TableHead class="text-center">Grade</TableHead>
                                        <TableHead>Enrolled</TableHead>
                                        <TableHead>Paid</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="s in filteredStudents" :key="s.id" class="group hover:bg-muted/50">
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-8 w-8">
                                                    <AvatarFallback
                                                        class="text-xs bg-primary/10 text-primary font-bold">
                                                        {{ initials(s.name) }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p class="text-sm font-medium text-foreground">{{ s.name }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ s.email }}</p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="s.type === 'child' ? 'secondary' : 'outline'"
                                                class="text-xs capitalize">
                                                {{ s.type }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <CheckCircle2 v-if="s.attendance_rate >= 80"
                                                    class="h-3.5 w-3.5 text-emerald-500" />
                                                <AlertCircle v-else class="h-3.5 w-3.5 text-amber-500" />
                                                <span class="text-sm font-medium">{{ s.attendance_rate }}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span :class="['text-sm font-bold', gradeColor(s.grade)]">
                                                {{ s.grade != null ? s.grade + '%' : '—' }}
                                            </span>
                                            <span class="ml-1 text-xs text-muted-foreground">({{ gradeLabel(s.grade)
                                                }})</span>
                                        </TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ fmtDate(s.enrolled_at) }}
                                        </TableCell>
                                        <TableCell class="text-xs font-medium text-foreground">{{
                                            formatNaira(s.paid_amount) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- ── SESSIONS TAB ──────────────────────────────────────────────────── -->
                <TabsContent value="sessions" class="mt-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">{{ sessions.length }} sessions scheduled for this
                                batch</p>
                            <Button size="sm" class="gap-2" @click="router.visit('/dashboard/live-sessions')">
                                <Plus class="h-4 w-4" />
                                Add Session
                            </Button>
                        </div>

                        <Card v-for="sess in sessions" :key="sess.id"
                            :class="['transition-all', sess.status === 'live' && 'ring-2 ring-primary']">
                            <CardContent class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <h4 class="text-sm font-semibold text-foreground">{{ sess.title }}</h4>
                                            <Badge :variant="sessionStatusConfig[sess.status]?.variant" class="text-xs">
                                                {{ sessionStatusConfig[sess.status]?.label }}
                                            </Badge>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            <span class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />{{ fmtDateTime(sess.scheduled_at) }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <Clock class="h-3 w-3" />{{ sess.duration }} min
                                            </span>
                                            <span>{{ platformLabel[sess.platform] ?? sess.platform }}</span>
                                            <span class="flex items-center gap-1">
                                                <UserCheck class="h-3 w-3" />
                                                {{ sess.attendee_count != null ?
                                                    `${sess.attendee_count}/${sess.enrolled_count} attended` :
                                                `${sess.enrolled_count} enrolled` }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <Button v-if="sess.meeting_link && sess.status !== 'completed'" size="sm"
                                            variant="outline" class="gap-1.5 text-xs h-8" as-child>
                                            <a :href="sess.meeting_link" target="_blank" rel="noopener noreferrer">
                                                <ExternalLink class="h-3.5 w-3.5" />
                                                Join
                                            </a>
                                        </Button>
                                        <Button v-if="sess.status === 'completed'" size="sm" variant="ghost"
                                            class="gap-1.5 text-xs h-8">
                                            <UserCheck class="h-3.5 w-3.5" />
                                            Attendance
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- ── LEADERBOARD TAB ───────────────────────────────────────────────── -->
                <TabsContent value="leaderboard" class="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <Trophy class="h-5 w-5 text-amber-500" />
                                Batch Leaderboard
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Ranked by overall grade (assignments 40% · quizzes 30% · attendance 20% · participation
                                10%)
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="studentsLoading" class="space-y-3">
                                <div v-for="i in 4" :key="i" class="h-14 bg-muted rounded animate-pulse" />
                            </div>

                            <div v-else-if="leaderboard.length === 0" class="py-12 text-center">
                                <Trophy class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-sm text-muted-foreground">No student data yet</p>
                            </div>

                            <div v-else class="space-y-2">
                                <div v-for="s in leaderboard" :key="s.id" :class="[
                                    'flex items-center gap-3 rounded-lg p-3 transition-colors',
                                    s.rank <= 3 ? 'bg-amber-50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/30' : 'hover:bg-muted/50',
                                ]">
                                    <!-- Rank -->
                                    <div class="w-8 shrink-0 text-center">
                                        <component v-if="rankIcon(s.rank)" :is="rankIcon(s.rank)"
                                            :class="['h-5 w-5 mx-auto', rankColor(s.rank)]" />
                                        <span v-else class="text-sm font-semibold text-muted-foreground">#{{ s.rank
                                            }}</span>
                                    </div>

                                    <!-- Avatar -->
                                    <Avatar class="h-9 w-9 shrink-0">
                                        <AvatarFallback
                                            :class="['text-xs font-bold', s.rank <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-primary/10 text-primary']">
                                            {{ initials(s.name) }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <!-- Name + type -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-foreground truncate">{{ s.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            Attendance: {{ s.attendance_rate }}%
                                        </p>
                                    </div>

                                    <!-- Grade -->
                                    <div class="text-right shrink-0">
                                        <p :class="['text-lg font-bold leading-none', gradeColor(s.grade)]">
                                            {{ s.grade ?? '—' }}%
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{{ gradeLabel(s.grade) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- ── MATERIALS TAB ─────────────────────────────────────────────────── -->
                <TabsContent value="materials" class="mt-4">
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">Course Materials</CardTitle>
                                <Button size="sm" class="gap-2">
                                    <Plus class="h-4 w-4" />
                                    Upload Material
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="materials.length === 0" class="py-12 text-center">
                                <FileText class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-sm text-muted-foreground">No materials uploaded yet</p>
                            </div>

                            <div v-else class="space-y-2">
                                <div v-for="mat in materials" :key="mat.id"
                                    class="flex items-center gap-3 rounded-lg border border-border p-3 hover:bg-muted/50 transition-colors">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                        <FileText class="h-4 w-4 text-primary" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ mat.name }}</p>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                                            <span>{{ mat.module }}</span>
                                            <span>{{ mat.size }}</span>
                                            <span class="flex items-center gap-1">
                                                <Download class="h-3 w-3" />{{ mat.downloads }} downloads
                                            </span>
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="sm" class="gap-1.5 text-xs h-8">
                                        <ExternalLink class="h-3.5 w-3.5" />
                                        View
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

            </Tabs>

        </div>

        <!-- Batch not found -->
        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
            <AlertCircle class="h-10 w-10 text-muted-foreground/40 mb-3" />
            <p class="text-sm font-medium text-foreground">Batch not found</p>
            <p class="text-xs text-muted-foreground mt-1">This batch may have been deleted or you don't have access.</p>
            <Button class="mt-4 gap-2" variant="outline" @click="router.visit('/dashboard/batches')">
                <ArrowLeft class="h-4 w-4" />
                Back to Batches
            </Button>
        </div>
    </DashboardLayout>
</template>