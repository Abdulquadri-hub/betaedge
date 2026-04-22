<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, Search, MoreVertical, Eye, Edit, Trash2,
    Users, Calendar, Clock, MessageCircle,
    RefreshCw, Lock, Unlock,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
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
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    batches:    { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
    stats:      { type: Object, default: () => ({}) },
    pagination: { type: Object, default: null },
})


const page = usePage()
watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

const search       = ref(props.filters.search ?? '')
const filterStatus = ref(props.filters.status ?? 'all')


const filteredBatches = computed(() => {
    let list = props.batches

    if (filterStatus.value !== 'all') {
        list = list.filter(b => b.status === filterStatus.value)
    }

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(b =>
            b.name?.toLowerCase().includes(q) ||
            b.course_name?.toLowerCase().includes(q) ||
            b.instructor_name?.toLowerCase().includes(q)
        )
    }
    return list
})

const statusCounts = computed(() => ({
    all:       props.batches.length,
    planning:  props.batches.filter(b => b.status === 'planning').length,
    active:    props.batches.filter(b => b.status === 'active').length,
    completed: props.batches.filter(b => b.status === 'completed').length,
}))

// Lifecycle status config
const lifecycleConfig = {
    planning:  { label: 'Planning',   variant: 'outline'   },
    active:    { label: 'In Progress', variant: 'secondary' },
    completed: { label: 'Completed',  variant: 'outline'   },
    cancelled: { label: 'Cancelled',  variant: 'destructive' },
}

// ── Helpers ───────────────────────────────────────────────────────────────

function enrollmentPct(batch) {
    if (!batch.max_students) return 0
    return Math.round((batch.current_enrollment / batch.max_students) * 100)
}

function isFull(batch) {
    return batch.current_enrollment >= batch.max_students
}


function scheduleLabel(batch) {
    const day  = batch.course_session_day
    const time = batch.course_session_time
    if (!day && !time) return null
    const parts = []
    if (day)  parts.push(day)
    if (time) parts.push(fmtTime(time))
    return parts.join(' · ')
}

function fmtTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

function fmtNaira(amount) {
    if (!amount) return '₦0'
    return '₦' + Number(amount).toLocaleString('en-NG')
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function progressColor(pct) {
    if (pct >= 90) return '[&>div]:bg-destructive'
    if (pct >= 70) return '[&>div]:bg-amber-500'
    return '[&>div]:bg-primary'
}

// ── Actions ───────────────────────────────────────────────────────────────

function viewBatch(batch) { router.visit(`/dashboard/batches/${batch.id}`) }
function editBatch(batch) { router.visit(`/dashboard/batches/${batch.id}/edit`) }

/**
 * Toggles enrollment_status (open ↔ closed).
 * Does NOT change lifecycle status.
 */
function handleToggleEnrollment(batch) {
    router.patch(`/dashboard/batches/${batch.id}/toggle`, {}, { preserveScroll: true })
}

const showDeleteDialog = ref(false)
const deletingBatch    = ref(null)
const isDeleting       = ref(false)

function confirmDelete(batch) {
    deletingBatch.value    = batch
    showDeleteDialog.value = true
}

function handleDelete() {
    if (!deletingBatch.value) return
    isDeleting.value = true
    router.delete(`/dashboard/batches/${deletingBatch.value.id}`, {
        onSuccess: () => { showDeleteDialog.value = false; deletingBatch.value = null },
        onFinish:  () => { isDeleting.value = false },
    })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground tracking-tight">Batches</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage cohorts of students across all your courses.
                    </p>
                </div>
                <Button class="gap-2 shrink-0" @click="router.visit('/dashboard/batches/create')">
                    <Plus class="h-4 w-4" />Create Batch
                </Button>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground font-medium">Total Batches</p>
                    <p class="text-2xl font-bold text-foreground mt-1">
                        {{ props.stats?.total ?? props.batches.length }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground font-medium">Enrolling Now</p>
                    <p class="text-2xl font-bold text-primary mt-1">
                        {{ props.batches.filter(b => b.enrollment_status === 'open').length }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground font-medium">In Progress</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ statusCounts.active }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground font-medium">Total Students</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ props.stats?.total_students ?? 0 }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Tabs :model-value="filterStatus" @update:model-value="filterStatus = $event">
                    <TabsList class="h-auto flex-wrap">
                        <TabsTrigger value="all" class="gap-1.5">
                            All
                            <Badge variant="secondary" class="h-5 px-1.5 text-xs">{{ statusCounts.all }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="planning" class="gap-1.5">
                            Planning
                            <Badge variant="outline" class="h-5 px-1.5 text-xs">{{ statusCounts.planning }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="active" class="gap-1.5">
                            Active
                            <Badge variant="secondary" class="h-5 px-1.5 text-xs">{{ statusCounts.active }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="completed" class="gap-1.5">
                            Completed
                            <Badge variant="outline" class="h-5 px-1.5 text-xs">{{ statusCounts.completed }}</Badge>
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
                <div class="relative flex-1 sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search batches or courses…" class="pl-9" />
                </div>
            </div>

            <!-- Grid -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <div v-if="filteredBatches.length === 0"
                    class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
                    <Users class="h-10 w-10 text-muted-foreground/40 mb-3" />
                    <p class="text-sm font-medium text-foreground">No batches found</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ search ? 'Try a different search term.' : 'Create your first batch to get started.' }}
                    </p>
                    <Button class="mt-4 gap-2" size="sm" @click="router.visit('/dashboard/batches/create')">
                        <Plus class="h-4 w-4" />Create Batch
                    </Button>
                </div>

                <Card v-for="batch in filteredBatches" :key="batch.id"
                    class="group transition-all duration-200 hover:shadow-md hover:border-primary/30 cursor-pointer"
                    @click="viewBatch(batch)">
                    <CardContent class="p-5">

                        <!-- Name + menu -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-foreground truncate text-sm group-hover:text-primary transition-colors">
                                    {{ batch.name }}
                                </h3>
                                <p class="text-xs text-muted-foreground truncate mt-0.5">{{ batch.course_name }}</p>
                                <p v-if="batch.academic_level" class="text-[10px] text-muted-foreground">
                                    {{ batch.academic_level }}
                                </p>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon"
                                        class="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                        @click.stop>
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" @click.stop>
                                    <DropdownMenuItem @click.stop="viewBatch(batch)">
                                        <Eye class="mr-2 h-4 w-4" />View Details
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click.stop="editBatch(batch)">
                                        <Edit class="mr-2 h-4 w-4" />Edit Batch
                                    </DropdownMenuItem>
                                    <!-- Toggle uses enrollment_status, not status -->
                                    <DropdownMenuItem @click.stop="handleToggleEnrollment(batch)">
                                        <component
                                            :is="batch.enrollment_status === 'open' ? Lock : Unlock"
                                            class="mr-2 h-4 w-4" />
                                        {{ batch.enrollment_status === 'open' ? 'Close Enrollment' : 'Open Enrollment' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click.stop="confirmDelete(batch)">
                                        <Trash2 class="mr-2 h-4 w-4" />Delete Batch
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Status badges: lifecycle + enrollment -->
                        <div class="flex items-center gap-1.5 flex-wrap mb-3">
                            <!-- Lifecycle status -->
                            <Badge :variant="lifecycleConfig[batch.status]?.variant ?? 'outline'" class="text-xs">
                                {{ lifecycleConfig[batch.status]?.label ?? batch.status }}
                            </Badge>
                            <!-- Enrollment status — independent badge -->
                            <Badge
                                :variant="batch.enrollment_status === 'open' ? 'default' : 'secondary'"
                                class="text-xs gap-1">
                                <component :is="batch.enrollment_status === 'open' ? Unlock : Lock" class="h-2.5 w-2.5" />
                                {{ batch.enrollment_status === 'open' ? 'Enrolling' : 'Closed' }}
                            </Badge>
                            <Badge v-if="isFull(batch)" variant="destructive" class="text-xs">Full</Badge>
                        </div>

                        <!-- Dates -->
                        <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2">
                            <Calendar class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ fmtDate(batch.start_date) }} → {{ fmtDate(batch.end_date) }}</span>
                        </div>

                        <!-- Schedule — from course, read-only -->
                        <div v-if="scheduleLabel(batch)"
                            class="flex items-center gap-2 text-xs text-muted-foreground mb-3">
                            <Clock class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ scheduleLabel(batch) }}</span>
                            <span v-if="batch.course_duration_min" class="text-muted-foreground/60">
                                · {{ batch.course_duration_min }}min
                            </span>
                        </div>

                        <!-- Instructor -->
                        <div class="flex items-center gap-2 mb-4">
                            <Avatar class="h-5 w-5">
                                <AvatarFallback class="text-[8px] bg-primary/10 text-primary font-bold">
                                    {{ initials(batch.instructor_name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="text-xs text-muted-foreground truncate">
                                {{ batch.instructor_name || 'No instructor assigned' }}
                            </span>
                        </div>

                        <!-- Enrollment progress -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-1 text-muted-foreground">
                                    <Users class="h-3.5 w-3.5" />Students
                                </div>
                                <span class="font-semibold"
                                    :class="isFull(batch) ? 'text-destructive' : 'text-foreground'">
                                    {{ batch.current_enrollment }} / {{ batch.max_students }}
                                </span>
                            </div>
                            <Progress :value="enrollmentPct(batch)"
                                :class="['h-1.5', progressColor(enrollmentPct(batch))]" />
                            <p class="text-[10px] text-muted-foreground text-right">
                                {{ enrollmentPct(batch) }}% filled
                            </p>
                        </div>

                        <!-- WhatsApp indicator -->
                        <div v-if="batch.whatsapp_link"
                            class="flex items-center gap-1.5 mt-3 text-xs text-emerald-600">
                            <MessageCircle class="h-3.5 w-3.5" />WhatsApp group linked
                        </div>

                        <!-- Price footer -->
                        <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Price per student</span>
                            <span class="text-xs font-semibold text-foreground">
                                {{ fmtNaira(batch.price) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Delete dialog -->
            <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete this batch?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong class="text-foreground">{{ deletingBatch?.name }}</strong>
                            will be permanently deleted. This cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isDeleting">Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="isDeleting" @click="handleDelete">
                            <RefreshCw v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isDeleting ? 'Deleting…' : 'Delete Batch' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </DashboardLayout>
</template>