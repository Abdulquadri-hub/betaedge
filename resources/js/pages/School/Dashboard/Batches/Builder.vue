<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, Save, RefreshCw, Users, Calendar,
    MessageCircle, CheckCircle2, BookOpen, Info,
    Clock, Video,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
// import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    batch: { type: Object, default: null },
    course: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
})

const page = usePage()
const isEditMode = computed(() => !!props.batch?.id)
const isSaving = ref(false)

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error) toast.error(flash.error)
}, { deep: true })


const selectedCourse = ref(
    props.batch
        ? props.courses.find(c => c.id === props.batch.course_id) ?? props.course
        : props.course ?? null
)

// Auto-set end date from course duration when start date changes
function autoSetEndDate() {
    if (!form.start_date || !selectedCourse.value?.duration_weeks) return
    const start = new Date(form.start_date)
    start.setDate(start.getDate() + selectedCourse.value.duration_weeks * 7)
    form.end_date = start.toISOString().split('T')[0]
}

function onCourseChange(courseId) {
    const c = props.courses.find(c => c.id == courseId)
    selectedCourse.value = c ?? null
    form.course_id = courseId
}

const form = reactive({
    name: props.batch?.name ?? '',
    course_id: props.batch?.course_id ?? props.course?.id ?? '',
    start_date: props.batch?.start_date ?? '',
    end_date: props.batch?.end_date ?? '',
    max_students: props.batch?.max_students ?? 30,
    whatsapp_link: props.batch?.whatsapp_link ?? '',
    description: props.batch?.description ?? '',
    notes: props.batch?.notes ?? '',
})

const errors = reactive({})

const durationWeeks = computed(() => {
    if (!form.start_date || !form.end_date) return null
    const diff = Math.round(
        (new Date(form.end_date) - new Date(form.start_date)) / (1000 * 60 * 60 * 24 * 7)
    )
    return diff > 0 ? diff : null
})

const spotsLeft = computed(() => {
    if (!isEditMode.value) return form.max_students
    return form.max_students - (props.batch?.current_enrollment ?? 0)
})

// ── Schedule preview helpers ───────────────────────────────────────────────
function fmtTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

function platformLabel(p) {
    return {
        google_meet: 'Google Meet',
        zoom: 'Zoom',
        jitsi: 'Jitsi Meet',
        teams: 'Microsoft Teams',
        custom: 'Custom link',
    }[p] ?? p ?? ''
}

// ── Validation & save ──────────────────────────────────────────────────────
function validate() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!form.name.trim()) errors.name = 'Batch name is required'
    if (!form.course_id) errors.course_id = 'Select a course'
    if (!form.start_date) errors.start_date = 'Start date is required'
    if (!form.end_date) errors.end_date = 'End date is required'
    if (form.start_date && form.end_date && form.start_date >= form.end_date) {
        errors.end_date = 'End date must be after start date'
    }
    if (form.max_students < 1) errors.max_students = 'At least 1 student required'
    if (form.whatsapp_link && !form.whatsapp_link.includes('chat.whatsapp.com')) {
        errors.whatsapp_link = 'Must be a chat.whatsapp.com invite link'
    }
    return Object.keys(errors).length === 0
}

function handleSave() {
    if (!validate()) return
    isSaving.value = true

    const url = isEditMode.value
        ? `/dashboard/batches/${props.batch.id}/edit`
        : '/dashboard/batches/create'
    const method = isEditMode.value ? 'put' : 'post'

    router[method](url, { ...form }, {
        onError: (errs) => { Object.assign(errors, errs) },
        onFinish: () => { isSaving.value = false },
    })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/batches')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-foreground tracking-tight">
                        {{ isEditMode ? (form.name || 'Edit Batch') : 'Create New Batch' }}
                    </h1>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        {{ isEditMode
                            ? 'Update this batchs details.'
                            : 'Set up a new cohort for students to enroll into.' }}
                    </p>
                </div>
                <Button :disabled="isSaving" class="gap-2 shrink-0" @click="handleSave">
                    <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ isSaving ? 'Saving…' : isEditMode ? 'Save Changes' : 'Create Batch' }}
                </Button>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">

                <!-- Left column: main fields -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Batch Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <BookOpen class="h-4 w-4 text-primary" />Batch Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label>Batch Name <span class="text-destructive">*</span></Label>
                                <Input v-model="form.name" placeholder="e.g., January 2026 Batch"
                                    :class="errors.name && 'border-destructive'" />
                                <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Course <span class="text-destructive">*</span></Label>
                                <Select :model-value="String(form.course_id || '')"
                                    @update:model-value="onCourseChange">
                                    <SelectTrigger :class="errors.course_id && 'border-destructive'">
                                        <SelectValue placeholder="Select a course" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="c in props.courses" :key="c.id" :value="String(c.id)">
                                            <div class="flex flex-col leading-snug">
                                                <span>{{ c.title }}</span>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ c.academic_level }} · {{ c.course_code }}
                                                </span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.course_id" class="text-xs text-destructive">{{ errors.course_id }}</p>
                            </div>

                            <!-- Course schedule — READ ONLY — no editing here -->
                            <div v-if="selectedCourse"
                                class="rounded-lg border border-border bg-muted/30 p-3 space-y-2">
                                <p class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                                    Live Class Schedule
                                    <span class="text-[10px] font-normal text-muted-foreground ml-1">
                                        (inherited from course)
                                    </span>
                                </p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                    <div v-if="selectedCourse.session_day"
                                        class="flex items-center gap-1.5 text-muted-foreground">
                                        <Calendar class="h-3 w-3 shrink-0" />
                                        <span class="text-foreground font-medium">{{ selectedCourse.session_day
                                            }}</span>
                                    </div>
                                    <div v-if="selectedCourse.session_time"
                                        class="flex items-center gap-1.5 text-muted-foreground">
                                        <Clock class="h-3 w-3 shrink-0" />
                                        <span class="text-foreground font-medium">{{
                                            fmtTime(selectedCourse.session_time) }}</span>
                                    </div>
                                    <div v-if="selectedCourse.session_duration_minutes"
                                        class="flex items-center gap-1.5 text-muted-foreground">
                                        <span>{{ selectedCourse.session_duration_minutes }} min per class</span>
                                    </div>
                                    <div v-if="selectedCourse.session_platform"
                                        class="flex items-center gap-1.5 text-muted-foreground">
                                        <Video class="h-3 w-3 shrink-0" />
                                        <span>{{ platformLabel(selectedCourse.session_platform) }}</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-muted-foreground/70 flex items-center gap-1">
                                    <Info class="h-3 w-3 shrink-0" />
                                    To change the schedule, edit the course settings.
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Description <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                <Textarea v-model="form.description"
                                    placeholder="What makes this batch special? Mention focus areas or key info for students."
                                    :rows="3" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Dates -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-primary" />Dates & Duration
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Start Date <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.start_date" type="date"
                                        :class="errors.start_date && 'border-destructive'" @change="autoSetEndDate" />
                                    <p v-if="errors.start_date" class="text-xs text-destructive">{{ errors.start_date }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>End Date <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.end_date" type="date" :min="form.start_date || undefined"
                                        :class="errors.end_date && 'border-destructive'" />
                                    <p v-if="errors.end_date" class="text-xs text-destructive">{{ errors.end_date }}</p>
                                </div>
                            </div>

                            <div v-if="durationWeeks"
                                class="flex items-center gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                    This batch runs for <strong>{{ durationWeeks }} week{{ durationWeeks !== 1 ? 's' :
                                        '' }}</strong>
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Internal notes -->
                    <!-- <Card>
                        <CardHeader>
                            <CardTitle class="text-sm">
                                Internal Notes
                                <span class="text-muted-foreground text-xs font-normal">(optional)</span>
                            </CardTitle>
                            <CardDescription class="text-xs">Private — not visible to students.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Textarea v-model="form.notes"
                                placeholder="e.g., instructor confirmed, materials ready for week 1…" :rows="3" />
                        </CardContent>
                    </Card> -->
                </div>

                <!-- Right column: capacity sidebar -->
                <div class="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <Users class="h-4 w-4 text-primary" />Capacity
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label>Max Students <span class="text-destructive">*</span></Label>
                                <Input v-model.number="form.max_students" type="number" min="1" max="500"
                                    :class="errors.max_students && 'border-destructive'" />
                                <p v-if="errors.max_students" class="text-xs text-destructive">{{ errors.max_students }}
                                </p>
                            </div>

                            <!-- Enrollment progress (edit mode only) -->
                            <div v-if="isEditMode" class="rounded-lg bg-muted/40 border border-border p-3 space-y-1.5">
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Enrolled</span>
                                    <span class="font-medium text-foreground">
                                        {{ props.batch?.current_enrollment ?? 0 }} / {{ form.max_students }}
                                    </span>
                                </div>
                                <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                                    <div class="h-full rounded-full bg-primary transition-all"
                                        :style="`width:${Math.min(100, ((props.batch?.current_enrollment ?? 0) / form.max_students) * 100)}%`" />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ spotsLeft }} spot{{ spotsLeft !== 1 ? 's' : '' }} remaining
                                </p>
                            </div>

                            <!-- Price — read-only, comes from course -->
                            <div v-if="selectedCourse?.price"
                                class="rounded-lg bg-muted/30 border border-border p-3 space-y-1">
                                <p class="text-xs font-semibold text-foreground">Pricing</p>
                                <p class="text-lg font-bold text-foreground">
                                    ₦{{ Number(selectedCourse.price).toLocaleString('en-NG') }}
                                    <span class="text-xs font-normal text-muted-foreground">per student</span>
                                </p>
                                <p class="text-[10px] text-muted-foreground flex items-center gap-1">
                                    <Info class="h-3 w-3 shrink-0" />
                                    Price set on the course. Edit the course to change it.
                                </p>
                                <div v-if="form.max_students > 0" class="mt-2 pt-2 border-t border-border space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-muted-foreground">Full batch</span>
                                        <span class="font-medium">
                                            ₦{{ (selectedCourse.price * form.max_students).toLocaleString('en-NG') }}
                                        </span>
                                    </div>
                                    <!-- <div class="flex justify-between text-xs">
                                        <span class="text-muted-foreground">Your earnings (90%)</span>
                                        <span class="font-semibold text-emerald-600">
                                            ₦{{ Math.round(selectedCourse.price * form.max_students *
                                            0.9).toLocaleString('en-NG') }}
                                        </span>
                                    </div> -->
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- WhatsApp -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <MessageCircle class="h-4 w-4 text-emerald-600" />WhatsApp Group
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Add an invite link so enrolled students can join the class group.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-1.5">
                                <Label>Group Invite Link <span
                                        class="text-muted-foreground text-xs">(optional)</span></Label>
                                <Input v-model="form.whatsapp_link" placeholder="https://chat.whatsapp.com/…"
                                    :class="errors.whatsapp_link && 'border-destructive'" />
                                <p v-if="errors.whatsapp_link" class="text-xs text-destructive">{{ errors.whatsapp_link
                                    }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Only enrolled students and the instructor will see this link.
                                </p>
                            </div>
                        </CardContent>
                    </Card>


                    <Button :disabled="isSaving" class="w-full gap-2" @click="handleSave">
                        <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isSaving ? 'Saving…' : isEditMode ? 'Save Changes' : 'Create Batch' }}
                    </Button>

                    <Button v-if="isEditMode" variant="outline" class="w-full"
                        @click="router.visit(`/dashboard/batches/${props.batch.id}`)">
                        View Batch
                    </Button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>