<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, Users,  MessageCircle,
    Video, Edit, Search, Download, Trophy,
    Medal, Award, CheckCircle2,AlertCircle,
    // Eye, FileText, Plus, RefreshCw, UserCheck, ExternalLink,
    Lock, Unlock,
    Plus, BookOpen,
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
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    batch:    { type: Object, required: true },
    students: { type: Array,  default: () => [] },
    courses:  { type: Array,  default: () => [] },
})


const page      = usePage()
const activeTab = ref('students')

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

const studentSearch = ref('')

const filteredStudents = computed(() => {
    const q = studentSearch.value.trim().toLowerCase()
    if (!q) return props.students
    return props.students.filter(s =>
        s.name?.toLowerCase().includes(q) ||
        s.email?.toLowerCase().includes(q)
    )
})

const leaderboard = computed(() =>
    [...props.students]
        .sort((a, b) => (b.grade ?? 0) - (a.grade ?? 0))
        .map((s, i) => ({ ...s, rank: i + 1 }))
)

function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

// function fmtDateTime(iso) {
//     if (!iso) return '—'
//     return new Date(iso).toLocaleString('en-NG', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
// }

function fmtTime(t) {
    if (!t) return '—'
    const [h, m] = t.split(':')
    const hour   = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

function fmtNaira(amount) {
    if (!amount) return '₦0'
    return '₦' + Number(amount).toLocaleString('en-NG')
}

function enrollmentPct() {
    if (!props.batch?.max_students) return 0
    return Math.round(((props.batch.current_enrollment ?? 0) / props.batch.max_students) * 100)
}

function gradeColor(grade) {
    if (grade >= 90) return 'text-secondary'
    if (grade >= 80) return 'text-primary'
    if (grade >= 70) return 'text-secondary'
    if (grade >= 60) return 'text-primary'
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

const scheduleDays = computed(() => props.batch?.course_session_day ?? null)

// Two separate status configs
const lifecycleConfig = {
    planning:  { label: 'Planning',    variant: 'outline'    },
    active:    { label: 'In Progress', variant: 'secondary'  },
    completed: { label: 'Completed',   variant: 'outline'    },
    cancelled: { label: 'Cancelled',   variant: 'destructive'},
}

function handleToggleEnrollment() {
    router.patch(`/dashboard/batches/${props.batch.id}/toggle`, {}, { preserveScroll: true })
}

function handleExportStudents() {
    toast.info('Export coming soon')
}
</script>

<template>
    <DashboardLayout>
        <div v-if="batch" class="p-6 max-w-7xl mx-auto space-y-6">

            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0" @click="router.visit('/dashboard/batches')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground tracking-tight">{{ batch.name }}</h1>
                        <!-- Lifecycle badge -->
                        <Badge :variant="lifecycleConfig[batch.status]?.variant ?? 'outline'" class="text-xs capitalize">
                            {{ lifecycleConfig[batch.status]?.label ?? batch.status }}
                        </Badge>
                        <!-- Enrollment badge — independent of lifecycle -->
                        <Badge :variant="batch.enrollment_status === 'open' ? 'default' : 'secondary'" class="text-xs">
                            {{ batch.enrollment_status === 'open' ? 'Enrolling' : 'Enrollment Closed' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ batch.course_name }}</p>
                    <p v-if="batch.academic_level" class="text-xs text-muted-foreground">{{ batch.academic_level }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="batch.status !== 'completed' && batch.status !== 'cancelled'"
                        variant="outline" size="sm" class="gap-2" @click="handleToggleEnrollment">
                        <component :is="batch.enrollment_status === 'open' ? Lock : Unlock" class="h-4 w-4" />
                        {{ batch.enrollment_status === 'open' ? 'Close Enrollment' : 'Open Enrollment' }}
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2" @click="router.visit(`/dashboard/batches/${batch.id}/edit`)">
                        <Edit class="h-4 w-4" />Edit Batch
                    </Button>
                    <Button v-if="batch.whatsapp_link" variant="outline" size="sm"
                        class="gap-2 text-emerald-600 border-emerald-200" as-child>
                        <a :href="batch.whatsapp_link" target="_blank" rel="noopener noreferrer">
                            <MessageCircle class="h-4 w-4" />WhatsApp
                        </a>
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Enrolled</p>
                        <p class="text-2xl font-bold text-foreground mt-1">
                            {{ batch.current_enrollment }}<span class="text-sm font-normal text-muted-foreground">/{{ batch.max_students }}</span>
                        </p>
                        <Progress :value="enrollmentPct()" class="h-1 mt-2" />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Duration</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ fmtDate(batch.start_date) }}</p>
                        <p class="text-xs text-muted-foreground">→ {{ fmtDate(batch.end_date) }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Schedule</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ scheduleDays || '—' }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ batch.course_session_time ? fmtTime(batch.course_session_time) : '' }}
                            <span v-if="batch.course_duration_min" class="ml-1">· {{ batch.course_duration_min }}min</span>
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Revenue</p>
                        <p class="text-sm font-bold text-foreground mt-1">
                            {{ fmtNaira((batch.price ?? 0) * (batch.current_enrollment ?? 0)) }}
                        </p>
                        <p class="text-xs text-muted-foreground">{{ fmtNaira(batch.price) }} × {{ batch.current_enrollment }}</p>
                    </CardContent>
                </Card>
            </div>

            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="students" class="gap-2">
                        <Users class="h-4 w-4" />Students
                        <Badge variant="secondary" class="h-4 px-1.5 text-[10px]">{{ students.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="courses" class="gap-2">
                        <BookOpen class="h-4 w-4" />Courses
                        <Badge variant="secondary" class="h-4 px-1.5 text-[10px]">{{ courses.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="leaderboard" class="gap-2">
                        <Trophy class="h-4 w-4" />Leaderboard
                    </TabsTrigger>
                    <TabsTrigger value="sessions" class="gap-2">
                        <Video class="h-4 w-4" />Sessions
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="students" class="mt-4">
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="relative flex-1 max-w-sm">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input v-model="studentSearch" placeholder="Search students…" class="pl-9 h-9" />
                                </div>
                                <Button variant="outline" size="sm" class="gap-2" @click="handleExportStudents">
                                    <Download class="h-4 w-4" />Export
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="filteredStudents.length === 0" class="py-12 text-center">
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
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="s in filteredStudents" :key="s.id" class="hover:bg-muted/50">
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-8 w-8">
                                                    <AvatarFallback class="text-xs bg-primary/10 text-primary font-bold">
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
                                            <Badge :variant="s.type === 'child' ? 'secondary' : 'outline'" class="text-xs capitalize">
                                                {{ s.type }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <CheckCircle2 v-if="s.attendance_rate >= 80" class="h-3.5 w-3.5 text-primary" />
                                                <AlertCircle v-else class="h-3.5 w-3.5 text-secondary" />
                                                <span class="text-sm font-medium">{{ s.attendance_rate }}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span :class="['text-sm font-bold', gradeColor(s.grade)]">
                                                {{ s.grade != null ? s.grade + '%' : '—' }}
                                            </span>
                                            <span class="ml-1 text-xs text-muted-foreground">({{ gradeLabel(s.grade) }})</span>
                                        </TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ fmtDate(s.enrolled_at) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="courses" class="mt-4">
                    <div v-if="courses.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
                        <BookOpen class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No courses attached to this batch yet</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card v-for="course in courses" :key="course.id" class="hover:shadow-md transition-shadow">
                            <CardHeader class="pb-3">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <CardTitle class="text-base leading-tight line-clamp-2">{{ course.title }}</CardTitle>
                                    <Badge :variant="course.status === 'active' ? 'default' : 'outline'" class="text-xs shrink-0 capitalize">
                                        {{ course.status }}
                                    </Badge>
                                </div>
                                <CardDescription class="text-xs line-clamp-2">
                                    {{ course.description || 'No description' }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pb-3">
                                <div class="space-y-2">
                                    <div v-if="course.instructor" class="flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground font-medium">Instructor:</span>
                                        <span>{{ course.instructor }}</span>
                                    </div>
                                    <div v-if="course.course_code" class="flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground font-medium">Code:</span>
                                        <span class="font-mono text-primary">{{ course.course_code }}</span>
                                    </div>
                                    <div v-if="course.duration_weeks" class="flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground font-medium">Duration:</span>
                                        <span>{{ course.duration_weeks }} weeks</span>
                                    </div>
                                    <!-- <div v-if="course.capacity" class="flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground font-medium">Capacity:</span>
                                        <span>{{ course.enrollment_count || 0 }}/{{ course.capacity }} students</span>
                                    </div> -->
                                    <!-- <div v-if="course.credits" class="flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground font-medium">Credits:</span>
                                        <span>{{ course.credits }}</span>
                                    </div> -->
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="leaderboard" class="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <Trophy class="h-5 w-5 text-secondary" />Batch Leaderboard
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Students ranked by overall performance. Leaderboard activates after Week 3.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div v-if="leaderboard.length === 0" class="py-12 text-center">
                                <Trophy class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-sm text-muted-foreground">No student data yet</p>
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="s in leaderboard" :key="s.id" :class="[
                                    'flex items-center gap-3 rounded-lg p-3 transition-colors',
                                    s.rank <= 3 ? 'bg-muted dark:bg-secondary border border-muted dark:border-secondary' : 'hover:bg-muted/50',
                                ]">
                                    <div class="w-8 shrink-0 text-center">
                                        <component v-if="rankIcon(s.rank)" :is="rankIcon(s.rank)"
                                            :class="['h-5 w-5 mx-auto', rankColor(s.rank)]" />
                                        <span v-else class="text-sm font-semibold text-muted-foreground">#{{ s.rank }}</span>
                                    </div>
                                    <Avatar class="h-9 w-9 shrink-0">
                                        <AvatarFallback :class="['text-xs font-bold', s.rank <= 3 ? 'bg-muted text-secondary' : 'bg-primary/10 text-primary']">
                                            {{ initials(s.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-foreground truncate">{{ s.name }}</p>
                                        <p class="text-xs text-muted-foreground">Attendance: {{ s.attendance_rate }}%</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p :class="['text-lg font-bold leading-none', gradeColor(s.grade)]">{{ s.grade ?? '—' }}%</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{{ gradeLabel(s.grade) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="sessions" class="mt-4">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-muted-foreground">Live sessions for this batch</p>
                        <Button size="sm" class="gap-2" @click="router.visit('/dashboard/live-sessions')">
                            <Plus class="h-4 w-4" />Schedule Session
                        </Button>
                    </div>
                    <div class="py-12 text-center rounded-xl border border-dashed border-border">
                        <Video class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No sessions scheduled yet.</p>
                        <Button class="mt-4 gap-2" size="sm" @click="router.visit('/dashboard/live-sessions')">
                            <Plus class="h-4 w-4" />Schedule First Session
                        </Button>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
            <AlertCircle class="h-10 w-10 text-muted-foreground/40 mb-3" />
            <p class="text-sm font-medium text-foreground">Batch not found</p>
            <Button class="mt-4 gap-2" variant="outline" @click="router.visit('/dashboard/batches')">
                <ArrowLeft class="h-4 w-4" />Back to Batches
            </Button>
        </div>
    </DashboardLayout>
</template>