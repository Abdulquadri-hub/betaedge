<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, Edit, Globe, Archive, 
    // Trash2,
    // BookOpen, 
    Users, Wallet, TrendingUp,
    // Calendar, Clock,
     Video, FileText, Link2,
    // Download, 
    ExternalLink, Plus, MoreVertical,
    // CheckCircle2, 
    AlertCircle, Copy,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    // DropdownMenuSeparator,
     DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    course:      { type: Object, required: true },
    enrollments: { type: Array,  default: () => [] },
})

const page      = usePage()
const activeTab = ref('overview')

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

const courseBatches = computed(() => props.course?.batches ?? [])
const materials     = computed(() => props.course?.materials ?? [])
const totalStudents = computed(() => props.course?.total_students ?? 0)
const grossRevenue  = computed(() => (props.course?.price ?? 0) * totalStudents.value)

const statusCfg = {
    active:   { label: 'Published', variant: 'default'   },
    draft:    { label: 'Draft',     variant: 'secondary' },
    archived: { label: 'Archived',  variant: 'outline'   },
}

const batchStatusCfg = {
    open:      { label: 'Enrolling',   variant: 'default'   },
    active:    { label: 'In Progress', variant: 'secondary' },
    closed:    { label: 'Closed',      variant: 'outline'   },
    completed: { label: 'Completed',   variant: 'outline'   },
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtNaira(amount) {
    if (!amount) return '₦0'
    return '₦' + Number(amount).toLocaleString('en-NG')
}

function fmtTime(t) {
    if (!t) return '—'
    const [h, m] = t.split(':')
    const hour   = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

function platformLabel(p) {
    return {
        google_meet: 'Google Meet',
        zoom:        'Zoom',
        jitsi:       'Jitsi Meet',
        teams:       'Microsoft Teams',
        custom:      'Custom',
    }[p] ?? p ?? '—'
}

function enrollmentPct(batch) {
    if (!batch.max_students) return 0
    return Math.round((batch.current_enrollment / batch.max_students) * 100)
}

function matTypeIcon(type) {
    return { link: Link2, video: Video, pdf: FileText }[type] ?? FileText
}

function matTypeColor(type) {
    return {
        link:  'bg-secondary/10 text-secondary',
        video: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        pdf:   'bg-primary/10 text-primary',
    }[type] ?? 'bg-muted text-muted-foreground'
}

function fmtSize(kb) {
    if (!kb) return ''
    return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB'
}

function handlePublish() {
    router.post(`/dashboard/courses/${props.course.id}/publish`, {}, { preserveScroll: true })
}

function handleArchive() {
    router.post(`/dashboard/courses/${props.course.id}/archive`, {}, { preserveScroll: true })
}

function handleDuplicate() {
    router.post(`/dashboard/courses/${props.course.id}/duplicate`, {})
}
</script>

<template>
    <DashboardLayout>
        <div v-if="course" class="p-6 max-w-7xl mx-auto space-y-6">

            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0" @click="router.visit('/dashboard/courses')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground tracking-tight">{{ course.title }}</h1>
                        <Badge :variant="statusCfg[course.status]?.variant ?? 'secondary'" class="text-xs">
                            {{ statusCfg[course.status]?.label ?? course.status }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        {{ course.academic_level }} · {{ course.duration_weeks }} weeks
                        <span v-if="course.session_platform"> · {{ platformLabel(course.session_platform) }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button variant="outline" size="sm" class="gap-2" @click="router.visit(`/dashboard/courses/${course.id}/edit`)">
                        <Edit class="h-4 w-4" />Edit
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon" class="h-9 w-9">
                                <MoreVertical class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem v-if="course.status === 'draft'" @click="handlePublish">
                                <Globe class="mr-2 h-4 w-4 text-primary" />Publish Course
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="course.status === 'active'" @click="handleArchive">
                                <Archive class="mr-2 h-4 w-4" />Archive Course
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="handleDuplicate">
                                <Copy class="mr-2 h-4 w-4" />Duplicate
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mb-2">
                            <Users class="h-3.5 w-3.5" />Total Students
                        </div>
                        <p class="text-2xl font-bold text-foreground">{{ totalStudents }}</p>
                        <p class="text-xs text-muted-foreground">across {{ courseBatches.length }} batch{{ courseBatches.length !== 1 ? 'es' : '' }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mb-2">
                            <Wallet class="h-3.5 w-3.5" />Total Revenue
                        </div>
                        <p class="text-2xl font-bold text-foreground">{{ fmtNaira(grossRevenue) }}</p>
                        <p class="text-xs text-muted-foreground">{{ fmtNaira(course.price) }} per student</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mb-2">
                            <TrendingUp class="h-3.5 w-3.5" />Active Batches
                        </div>
                        <p class="text-2xl font-bold text-foreground">{{ course.active_batches }}</p>
                        <p class="text-xs text-muted-foreground">{{ courseBatches.length }} total</p>
                    </CardContent>
                </Card>
            </div>

            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="batches">
                        Batches
                        <Badge variant="secondary" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ courseBatches.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="materials">
                        Materials
                        <Badge variant="secondary" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ materials.length }}</Badge>
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="overview" class="mt-4 space-y-4">
                    <div class="grid lg:grid-cols-2 gap-4">
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Course Info</CardTitle></CardHeader>
                            <CardContent class="space-y-3 pt-0">
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Academic Level</span><span class="font-medium">{{ course.academic_level || '—' }}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Duration</span><span class="font-medium">{{ course.duration_weeks }} weeks</span></div>
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Price per Student</span><span class="font-medium">{{ fmtNaira(course.price) }}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Platform</span><span class="font-medium">{{ platformLabel(course.session_platform) }}</span></div>
                                <div v-if="course.session_day" class="flex justify-between text-sm"><span class="text-muted-foreground">Schedule</span><span class="font-medium">{{ course.session_day }} · {{ fmtTime(course.session_time) }}</span></div>
                                <div v-if="course.session_duration_minutes" class="flex justify-between text-sm"><span class="text-muted-foreground">Session Length</span><span class="font-medium">{{ course.session_duration_minutes }} min</span></div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Description</CardTitle></CardHeader>
                            <CardContent class="pt-0">
                                <p class="text-sm text-muted-foreground leading-relaxed">{{ course.description || 'No description provided.' }}</p>
                                <Button variant="outline" size="sm" class="gap-2 mt-4" @click="router.visit(`/dashboard/courses/${course.id}/edit`)">
                                    <Edit class="h-3.5 w-3.5" />Edit Course Details
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <div v-if="course.status === 'draft'" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4">
                        <AlertCircle class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-foreground">This course is a draft</p>
                            <p class="text-xs text-muted-foreground mt-0.5">Students cannot see or enroll until you publish it.</p>
                        </div>
                        <Button size="sm" class="gap-2 shrink-0" @click="handlePublish">
                            <Globe class="h-4 w-4" />Publish Now
                        </Button>
                    </div>
                </TabsContent>

                <TabsContent value="batches" class="mt-4">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-muted-foreground">{{ courseBatches.length }} batch{{ courseBatches.length !== 1 ? 'es' : '' }}</p>
                        <Button size="sm" class="gap-2" @click="router.visit(`/dashboard/batches/create?course_id=${course.id}`)">
                            <Plus class="h-4 w-4" />Add Batch
                        </Button>
                    </div>

                    <div v-if="courseBatches.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
                        <Users class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No batches yet. Create one so students can enroll.</p>
                    </div>

                    <div class="space-y-3">
                        <div v-for="batch in courseBatches" :key="batch.id"
                            class="flex items-center gap-4 rounded-xl border border-border bg-card p-4 hover:bg-muted/30 transition-colors cursor-pointer"
                            @click="router.visit(`/dashboard/batches/${batch.id}`)">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-foreground">{{ batch.name }}</p>
                                    <Badge :variant="batchStatusCfg[batch.status]?.variant ?? 'outline'" class="text-xs">
                                        {{ batchStatusCfg[batch.status]?.label ?? batch.status }}
                                    </Badge>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-muted-foreground mt-1">
                                    <span>{{ fmtDate(batch.start_date) }} → {{ fmtDate(batch.end_date) }}</span>
                                </div>
                                <Progress :value="enrollmentPct(batch)" class="h-1 mt-2 w-40" />
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-foreground">{{ batch.current_enrollment }}/{{ batch.max_students }}</p>
                                <p class="text-xs text-muted-foreground">enrolled</p>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="materials" class="mt-4">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-muted-foreground">{{ materials.length }} material{{ materials.length !== 1 ? 's' : '' }}</p>
                        <Button size="sm" class="gap-2" @click="router.visit(`/dashboard/courses/${course.id}/edit`)">
                            <Plus class="h-4 w-4" />Add Material
                        </Button>
                    </div>

                    <div v-if="materials.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
                        <FileText class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No materials yet. Add them in the Course Builder.</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div v-for="mat in materials" :key="mat.id"
                            class="flex items-center gap-3 rounded-lg border border-border bg-card p-3 hover:bg-muted/40 transition-colors">
                            <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', matTypeColor(mat.type)]">
                                <component :is="matTypeIcon(mat.type)" class="h-4 w-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground truncate">{{ mat.title }}</p>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                                    <span>{{ mat.module }}</span>
                                    <span v-if="mat.size_kb">{{ fmtSize(mat.size_kb) }}</span>
                                    <span>{{ mat.downloads }} downloads</span>
                                </div>
                            </div>
                            <Button v-if="mat.url" variant="ghost" size="sm" class="gap-1.5 text-xs h-8 shrink-0" as-child>
                                <a :href="mat.url" target="_blank" rel="noopener noreferrer">
                                    <ExternalLink class="h-3.5 w-3.5" />View
                                </a>
                            </Button>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] p-6 text-center">
            <AlertCircle class="h-10 w-10 text-muted-foreground/40 mb-3" />
            <p class="text-sm font-medium">Course not found</p>
            <Button class="mt-4 gap-2" variant="outline" @click="router.visit('/dashboard/courses')">
                <ArrowLeft class="h-4 w-4" />Back to Courses
            </Button>
        </div>
    </DashboardLayout>
</template>