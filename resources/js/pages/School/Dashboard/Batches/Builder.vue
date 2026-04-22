<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, ArrowRight, Save, RefreshCw, Plus,
    Users, Calendar, MessageCircle, CheckCircle2,
    Info, ChevronDown, ChevronUp, BookOpen,
    // Video, Globe, Lock, 
    X,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    batch:       { type: Object, default: null },
    courses:     { type: Array,  default: () => [] },
    instructors: { type: Array,  default: () => [] },
})


const page       = usePage()
const isEditMode = computed(() => !!props.batch?.id)
const isSaving   = ref(false)
const step       = ref(1)

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

// ── Batch form ──────────────────────────────────────────────────────────────
const form = reactive({
    name:          props.batch?.name          ?? '',
    description:   props.batch?.description   ?? '',
    start_date:    props.batch?.start_date    ?? '',
    end_date:      props.batch?.end_date      ?? '',
    max_students:  props.batch?.max_students  ?? 30,
    price:         props.batch?.price         ?? '',
    price_note:    props.batch?.price_note    ?? '',
    whatsapp_link: props.batch?.whatsapp_link ?? '',
    notes:         props.batch?.notes         ?? '',
})

// ── Course slots ────────────────────────────────────────────────────────────
// Each slot = { course_id, instructor_id, session_day, session_time,
//               session_duration_minutes, session_platform, session_frequency }
const courseSlots = ref(
    props.batch?.batch_courses?.map(bc => ({
        course_id:                 bc.course_id,
        instructor_id:             bc.instructor_id ?? '',
        session_day:               bc.session_day ?? '',
        session_time:              bc.session_time ?? '',
        session_duration_minutes:  bc.session_duration_minutes ?? 90,
        session_platform:          bc.session_platform ?? '',
        session_frequency:         bc.session_frequency ?? 'weekly',
        _expanded:                 false,
    })) ?? []
)

const errors = reactive({})

const PLATFORMS = [
    { value: 'google_meet', label: 'Google Meet' },
    { value: 'zoom',        label: 'Zoom'        },
    { value: 'jitsi',       label: 'Jitsi Meet'  },
    { value: 'teams',       label: 'Microsoft Teams' },
    { value: 'custom',      label: 'Custom link' },
]

const FREQUENCIES = [
    { value: 'weekly',        label: 'Weekly'       },
    { value: 'twice_weekly',  label: 'Twice a week' },
    { value: 'thrice_weekly', label: '3× a week'    },
    { value: 'daily',         label: 'Daily'        },
    { value: 'biweekly',      label: 'Every 2 weeks'},
]

const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']

// ── Course slot helpers ──────────────────────────────────────────────────────
const usedCourseIds = computed(() => courseSlots.value.map(s => s.course_id).filter(Boolean))

const availableCourses = computed(() =>
    props.courses.filter(c => !usedCourseIds.value.includes(c.id) && !usedCourseIds.value.includes(String(c.id)))
)

function addCourseSlot() {
    courseSlots.value.push({
        course_id: '', instructor_id: '',
        session_day: '', session_time: '',
        session_duration_minutes: 90,
        session_platform: 'google_meet',
        session_frequency: 'weekly',
        _expanded: true,
    })
}

function removeSlot(i) {
    courseSlots.value.splice(i, 1)
}

function toggleExpand(i) {
    courseSlots.value[i]._expanded = !courseSlots.value[i]._expanded
}

function courseTitle(courseId) {
    const c = props.courses.find(c => c.id == courseId || String(c.id) === String(courseId))
    return c?.title ?? '—'
}

function instructorName(instructorId) {
    const i = props.instructors.find(i => i.id == instructorId)
    return i?.name ?? null
}

function fmtTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

// function platformLabel(p) {
//     return PLATFORMS.find(x => x.value === p)?.label ?? p ?? ''
// }

// Auto-fill end date from course duration
function autoFillEndDate() {
    if (!form.start_date) return
    const firstCourseId = courseSlots.value[0]?.course_id
    if (!firstCourseId) return
    const course = props.courses.find(c => c.id == firstCourseId)
    if (!course?.duration_weeks) return
    const start = new Date(form.start_date)
    start.setDate(start.getDate() + course.duration_weeks * 7)
    form.end_date = start.toISOString().split('T')[0]
}

const durationWeeks = computed(() => {
    if (!form.start_date || !form.end_date) return null
    const diff = Math.round(
        (new Date(form.end_date) - new Date(form.start_date)) / (1000 * 60 * 60 * 24 * 7)
    )
    return diff > 0 ? diff : null
})

const spotsLeft = computed(() =>
    form.max_students - (props.batch?.current_enrollment ?? 0)
)

// ── Validation ─────────────────────────────────────────────────────────────
function validateBasics() {
    const e = {}
    if (!form.name.trim())   e.name       = 'Batch name is required'
    if (!form.start_date)    e.start_date = 'Start date is required'
    if (!form.end_date)      e.end_date   = 'End date is required'
    if (form.start_date && form.end_date && form.start_date >= form.end_date) {
        e.end_date = 'End date must be after start date'
    }
    if (!form.max_students || form.max_students < 1) e.max_students = 'At least 1 student required'
    if (form.price !== '' && isNaN(Number(form.price))) e.price = 'Enter a valid price'
    if (form.whatsapp_link && !form.whatsapp_link.includes('chat.whatsapp.com')) {
        e.whatsapp_link = 'Must be a chat.whatsapp.com invite link'
    }
    Object.assign(errors, e)
    return Object.keys(e).length === 0
}

function nextStep() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (step.value === 1 && !validateBasics()) return
    if (step.value < 2) step.value++
}

function prevStep() {
    if (step.value > 1) step.value--
}

// ── Save ────────────────────────────────────────────────────────────────────
function handleSave() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!validateBasics()) { step.value = 1; return }

    isSaving.value = true

    const payload = {
        ...form,
        price: form.price !== '' ? form.price : null,
        courses: courseSlots.value
            .filter(s => s.course_id)
            .map(({ _expanded, ...s }) => s),
    }

    const url    = isEditMode.value ? `/dashboard/batches/${props.batch.id}/edit` : '/dashboard/batches/create'
    const method = isEditMode.value ? 'put' : 'post'

    router[method](url, payload, {
        onError: (errs) => {
            Object.assign(errors, errs)
            if (errs.name || errs.start_date || errs.end_date || errs.max_students) step.value = 1
        },
        onFinish: () => { isSaving.value = false },
    })
}

const lifecycleConfig = {
    planning:  { label: 'Planning',    variant: 'outline'    },
    active:    { label: 'In Progress', variant: 'secondary'  },
    completed: { label: 'Completed',   variant: 'outline'    },
    cancelled: { label: 'Cancelled',   variant: 'destructive'},
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-3xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/batches')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold tracking-tight text-foreground">
                            {{ isEditMode ? (form.name || 'Edit Batch') : 'New Batch' }}
                        </h1>
                        <Badge v-if="props.batch?.status"
                            :variant="lifecycleConfig[props.batch.status]?.variant ?? 'outline'"
                            class="text-xs capitalize">
                            {{ lifecycleConfig[props.batch.status]?.label ?? props.batch.status }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        A batch is the product students enroll in. Attach courses and set their schedules here.
                    </p>
                </div>
                <Button :disabled="isSaving" class="gap-2 shrink-0" @click="handleSave">
                    <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ isSaving ? 'Saving…' : isEditMode ? 'Save' : 'Create' }}
                </Button>
            </div>

            <!-- Step indicators -->
            <div class="flex items-center gap-3">
                <button type="button" class="flex items-center gap-1.5 text-xs font-medium transition-all"
                    :class="step === 1 ? 'text-foreground' : 'text-muted-foreground'"
                    @click="step = 1">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold"
                        :class="step === 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        1
                    </span>
                    Basics
                </button>
                <div class="h-px flex-1 bg-border max-w-12" />
                <button type="button" class="flex items-center gap-1.5 text-xs font-medium transition-all"
                    :class="step === 2 ? 'text-foreground' : 'text-muted-foreground'"
                    @click="step = 2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold"
                        :class="step === 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        {{ courseSlots.filter(s => s.course_id).length > 0 ? '2' : '2' }}
                    </span>
                    Courses
                    <Badge v-if="courseSlots.filter(s => s.course_id).length > 0"
                        variant="secondary" class="h-4 px-1.5 text-[10px]">
                        {{ courseSlots.filter(s => s.course_id).length }}
                    </Badge>
                </button>
            </div>

            <!-- Step 1: Basics -->
            <div v-if="step === 1" class="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <BookOpen class="h-4 w-4 text-primary" />Batch Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>Batch Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.name" placeholder="e.g., JAMB 2026 Intensive — April Cohort"
                                :class="errors.name && 'border-destructive'" />
                            <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                            <p class="text-xs text-muted-foreground">This is what students see on the enrollment page.</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Description <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <Textarea v-model="form.description"
                                placeholder="What's special about this cohort? Any key information for prospective students?"
                                :rows="3" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-primary" />Dates
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Start Date <span class="text-destructive">*</span></Label>
                                <Input v-model="form.start_date" type="date"
                                    :class="errors.start_date && 'border-destructive'"
                                    @change="autoFillEndDate" />
                                <p v-if="errors.start_date" class="text-xs text-destructive">{{ errors.start_date }}</p>
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
                                <strong>{{ durationWeeks }} week{{ durationWeeks !== 1 ? 's' : '' }}</strong> duration
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <Users class="h-4 w-4 text-primary" />Capacity & Pricing
                        </CardTitle>
                        <CardDescription class="text-xs">
                            One enrollment fee covers all courses in this batch.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Max Students <span class="text-destructive">*</span></Label>
                                <Input v-model.number="form.max_students" type="number" min="1" max="1000"
                                    :class="errors.max_students && 'border-destructive'" />
                                <p v-if="errors.max_students" class="text-xs text-destructive">{{ errors.max_students }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Enrollment Fee (₦)</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground font-medium">₦</span>
                                    <Input v-model="form.price" type="number" min="0" placeholder="0"
                                        class="pl-7" :class="errors.price && 'border-destructive'" />
                                </div>
                                <p v-if="errors.price" class="text-xs text-destructive">{{ errors.price }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Price Note <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <Input v-model="form.price_note"
                                placeholder='e.g., "Includes Maths, English, Physics, Chemistry"' />
                            <p class="text-xs text-muted-foreground">Shown below the price on the batch card.</p>
                        </div>

                        <!-- Revenue preview -->
                        <div v-if="form.price > 0"
                            class="rounded-lg bg-muted/40 border border-border p-3 space-y-1.5">
                            <p class="text-xs font-semibold text-foreground">Revenue Estimate</p>
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Full batch ({{ form.max_students }} students)</span>
                                <span class="font-medium">₦{{ (form.price * form.max_students).toLocaleString('en-NG') }}</span>
                            </div>
                            <!-- <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Your earnings (90%)</span>
                                <span class="font-semibold text-emerald-600">
                                    ₦{{ Math.round(form.price * form.max_students * 0.9).toLocaleString('en-NG') }}
                                </span>
                            </div> -->
                        </div>

                        <!-- Enrollment progress (edit mode) -->
                        <div v-if="isEditMode" class="rounded-lg bg-muted/40 border border-border p-3 space-y-1.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Enrolled</span>
                                <span class="font-medium">{{ props.batch?.current_enrollment ?? 0 }} / {{ form.max_students }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full bg-primary transition-all"
                                    :style="`width:${Math.min(100, ((props.batch?.current_enrollment ?? 0) / form.max_students) * 100)}%`" />
                            </div>
                            <p class="text-xs text-muted-foreground">{{ spotsLeft }} spot{{ spotsLeft !== 1 ? 's' : '' }} remaining</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <MessageCircle class="h-4 w-4 text-emerald-600" />WhatsApp Group
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-1.5">
                            <Label>Invite Link <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <Input v-model="form.whatsapp_link" placeholder="https://chat.whatsapp.com/…"
                                :class="errors.whatsapp_link && 'border-destructive'" />
                            <p v-if="errors.whatsapp_link" class="text-xs text-destructive">{{ errors.whatsapp_link }}</p>
                            <p class="text-xs text-muted-foreground">Only enrolled students and instructors will see this.</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button class="gap-2" @click="nextStep">
                        Next: Add Courses <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Step 2: Courses -->
            <div v-if="step === 2" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-foreground">Courses in this Batch</p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Add subjects and set the schedule for each one.
                            Students enrolled in this batch access all of them.
                        </p>
                    </div>
                    <Button size="sm" class="gap-2"
                        :disabled="availableCourses.length === 0"
                        @click="addCourseSlot">
                        <Plus class="h-4 w-4" />Add Course
                    </Button>
                </div>

                <!-- Must have at least one rule -->
                <div class="flex items-start gap-2.5 rounded-lg border border-border bg-muted/30 p-3">
                    <Info class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                    <p class="text-xs text-muted-foreground">
                        A batch must have at least one course before it can be published.
                        <span v-if="availableCourses.length === 0" class="text-amber-600 font-medium">
                            All your courses are already added.
                        </span>
                    </p>
                </div>

                <!-- Empty state -->
                <div v-if="courseSlots.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-12 text-center">
                    <BookOpen class="h-8 w-8 text-muted-foreground/40 mb-2" />
                    <p class="text-sm text-muted-foreground">No courses added yet</p>
                    <Button class="mt-4 gap-2" size="sm" @click="addCourseSlot">
                        <Plus class="h-4 w-4" />Add First Course
                    </Button>
                </div>

                <!-- Course slots -->
                <div class="space-y-3">
                    <div v-for="(slot, i) in courseSlots" :key="i"
                        class="rounded-xl border border-border bg-card overflow-hidden">

                        <!-- Slot header -->
                        <div class="flex items-center gap-3 p-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                                {{ i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <Select :model-value="String(slot.course_id || '')"
                                    @update:model-value="v => { slot.course_id = v; slot._expanded = true }">
                                    <SelectTrigger class="h-9 bg-transparent border-none shadow-none p-0 text-sm font-medium focus:ring-0">
                                        <SelectValue placeholder="Select a course…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <!-- Show currently selected option even if not in availableCourses -->
                                        <SelectItem v-if="slot.course_id && !availableCourses.find(c => c.id == slot.course_id)"
                                            :value="String(slot.course_id)">
                                            {{ courseTitle(slot.course_id) }}
                                        </SelectItem>
                                        <SelectItem v-for="c in availableCourses" :key="c.id" :value="String(c.id)">
                                            <div class="flex flex-col leading-snug">
                                                <span>{{ c.title }}</span>
                                                <span class="text-xs text-muted-foreground">{{ c.academic_level }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="slot.course_id && slot.session_day" class="text-xs text-muted-foreground mt-0.5">
                                    {{ slot.session_day }} · {{ fmtTime(slot.session_time) }}
                                    <span v-if="slot.instructor_id"> · {{ instructorName(slot.instructor_id) }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <Button variant="ghost" size="icon" class="h-7 w-7 text-muted-foreground"
                                    @click="toggleExpand(i)">
                                    <component :is="slot._expanded ? ChevronUp : ChevronDown" class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon"
                                    class="h-7 w-7 text-muted-foreground hover:text-destructive"
                                    @click="removeSlot(i)">
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Expanded schedule fields -->
                        <div v-if="slot._expanded"
                            class="border-t border-border bg-muted/20 p-4 space-y-4">

                            <!-- Instructor -->
                            <div class="space-y-1.5">
                                <Label class="text-xs">Instructor</Label>
                                <Select :model-value="String(slot.instructor_id || '')"
                                    @update:model-value="v => slot.instructor_id = v">
                                    <SelectTrigger class="h-9">
                                        <SelectValue placeholder="Assign an instructor (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="inst in props.instructors" :key="inst.id" :value="String(inst.id)">
                                            {{ inst.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Day picker -->
                            <div class="space-y-1.5">
                                <Label class="text-xs">Class Days</Label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="day in DAYS" :key="day" type="button"
                                        class="px-2.5 py-1 rounded-md border text-xs font-medium transition-all"
                                        :class="slot.session_day?.includes(day)
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'bg-card border-border text-foreground hover:border-primary/40'"
                                        @click="() => {
                                            const days = slot.session_day ? slot.session_day.split(', ').filter(Boolean) : []
                                            const idx = days.indexOf(day)
                                            if (idx === -1) days.push(day)
                                            else days.splice(idx, 1)
                                            slot.session_day = days.join(', ')
                                        }">
                                        {{ day.slice(0, 3) }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Start Time</Label>
                                    <Input v-model="slot.session_time" type="time" class="h-9" />
                                    <p v-if="slot.session_time" class="text-xs text-muted-foreground">
                                        {{ fmtTime(slot.session_time) }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Duration (min)</Label>
                                    <Input v-model.number="slot.session_duration_minutes" type="number"
                                        min="15" max="480" class="h-9" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Frequency</Label>
                                    <Select :model-value="slot.session_frequency"
                                        @update:model-value="v => slot.session_frequency = v">
                                        <SelectTrigger class="h-9">
                                            <SelectValue placeholder="How often?" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="f in FREQUENCIES" :key="f.value" :value="f.value">
                                                {{ f.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Platform</Label>
                                    <Select :model-value="slot.session_platform"
                                        @update:model-value="v => slot.session_platform = v">
                                        <SelectTrigger class="h-9">
                                            <SelectValue placeholder="Select platform" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in PLATFORMS" :key="p.value" :value="p.value">
                                                {{ p.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add another -->
                <button v-if="availableCourses.length > 0" type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border py-3 text-sm text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                    @click="addCourseSlot">
                    <Plus class="h-4 w-4" />Add another course
                </button>

                <div class="flex items-center justify-between pt-2">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back
                    </Button>
                    <Button :disabled="isSaving" class="gap-2" @click="handleSave">
                        <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isSaving ? 'Saving…' : isEditMode ? 'Save Changes' : 'Create Batch' }}
                    </Button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>