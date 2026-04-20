<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, ArrowRight, Save, Globe, Archive,
    BookOpen, Calendar, FileText, Users, Plus,
    Trash2, Upload, Link2, Video, CheckCircle2,
    RefreshCw, ExternalLink, Download, Info, Eye,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Dialog, DialogContent, DialogDescription,
    DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    course:         { type: Object, default: null },
    materials:      { type: Array,  default: () => [] },
    batches:        { type: Array,  default: () => [] },
    academicLevels: { type: Array,  default: () => [] },
})

const page        = usePage()
const isEditMode  = computed(() => !!props.course?.id)
const isSaving    = ref(false)
const isPublishing = ref(false)
const step        = ref(1)
const totalSteps  = 4

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

const form = reactive({
    title:                    props.course?.title                    ?? '',
    course_code:              props.course?.course_code              ?? '',
    description:              props.course?.description              ?? '',
    academic_level_id:        props.course?.academic_level_id        ?? '',
    duration_weeks:           props.course?.duration_weeks           ?? 12,
    price:                    props.course?.price                    ?? 0,
    max_students:             props.course?.max_students             ?? null,
    session_frequency:        props.course?.session_frequency        ?? '',
    session_day:              props.course?.session_day              ?? '',
    session_time:             props.course?.session_time             ?? '',
    session_duration_minutes: props.course?.session_duration_minutes ?? 90,
    session_platform:         props.course?.session_platform         ?? '',
})

// Thumbnail — stored separately since it's a File object
const thumbnailFile    = ref(null)
const thumbnailPreview = ref(props.course?.thumbnail ?? null)
const thumbnailInput   = ref(null)

function onThumbnailSelect(e) {
    const file = e.target.files?.[0]
    if (!file) return
    thumbnailFile.value    = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function clearThumbnail() {
    thumbnailFile.value    = null
    thumbnailPreview.value = null
    if (thumbnailInput.value) thumbnailInput.value.value = ''
}

const errors = reactive({})

// function clearErrors() {
//     Object.keys(errors).forEach(k => delete errors[k])
// }

function validateStep(n) {
    const stepErrors = {}
    if (n === 1) {
        if (!form.title.trim())       stepErrors.title            = 'Course title is required'
        if (!form.course_code.trim()) stepErrors.course_code      = 'Course code is required'
        if (!form.description.trim()) stepErrors.description      = 'Description is required'
        if (!form.academic_level_id)  stepErrors.academic_level_id = 'Academic level is required'
        if (form.duration_weeks < 1)  stepErrors.duration_weeks   = 'Must be at least 1 week'
        if (form.price < 0)           stepErrors.price            = 'Price cannot be negative'
    }
    if (n === 2) {
        // Schedule fields are required when moving between steps
        // but only frequency+day are required for "Next" — platform is strongly encouraged
        if (!form.session_frequency)  stepErrors.session_frequency = 'Select a frequency'
        if (!form.session_day)        stepErrors.session_day       = 'Select class day(s)'
        if (!form.session_platform)   stepErrors.session_platform  = 'Select a platform'
        if (form.session_duration_minutes < 15) stepErrors.session_duration_minutes = 'Min 15 minutes'
    }
    // Merge into reactive errors without clearing other steps' errors
    Object.keys(stepErrors).forEach(k => { errors[k] = stepErrors[k] })
    return Object.keys(stepErrors).length === 0
}

function clearStepErrors(n) {
    const step1Keys = ['title', 'course_code', 'description', 'academic_level_id', 'duration_weeks', 'price']
    const step2Keys = ['session_frequency', 'session_day', 'session_platform', 'session_duration_minutes']
    const keys = n === 1 ? step1Keys : step2Keys
    keys.forEach(k => delete errors[k])
}

function nextStep() {
    clearStepErrors(step.value)
    if (!validateStep(step.value)) return
    if (step.value < totalSteps) step.value++
}

function prevStep() {
    if (step.value > 1) step.value--
}

function handleSave() {
    // Only require step 1 (core details) for save — schedule can be filled later
    clearStepErrors(1)
    if (!validateStep(1)) { step.value = 1; return }

    isSaving.value = true

    const url    = isEditMode.value ? `/dashboard/courses/${props.course.id}/edit` : '/dashboard/courses/create'

    // Use FormData so thumbnail file upload works
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') fd.append(k, v)
    })
    if (thumbnailFile.value) fd.append('thumbnail', thumbnailFile.value)

    router.post(url, fd, {
        forceFormData: true,
        onError: (errs) => {
            Object.assign(errors, errs)
            if (errs.title || errs.course_code || errs.description || errs.academic_level_id) step.value = 1
            else if (errs.session_frequency || errs.session_day || errs.session_platform) step.value = 2
        },
        onFinish: () => { isSaving.value = false },
    })
}

function handlePublish() {
    if (!isEditMode.value) { toast.error('Save the course first'); return }
    isPublishing.value = true
    router.post(`/dashboard/courses/${props.course.id}/publish`, {}, {
        onFinish: () => { isPublishing.value = false },
    })
}

function handleArchive() {
    if (!isEditMode.value) return
    router.post(`/dashboard/courses/${props.course.id}/archive`, {})
}

const PLATFORMS = [
    { value: 'google_meet', label: 'Google Meet' },
    { value: 'zoom',        label: 'Zoom'        },
    { value: 'jitsi',       label: 'Jitsi Meet'  },
    { value: 'teams',       label: 'Microsoft Teams' },
    { value: 'custom',      label: 'Other / Custom link' },
]

const FREQUENCIES = [
    { value: 'weekly',        label: 'Once a week'   },
    { value: 'twice_weekly',  label: 'Twice a week'  },
    { value: 'thrice_weekly', label: 'Three times a week' },
    { value: 'daily',         label: 'Daily (Mon–Fri)' },
    { value: 'biweekly',      label: 'Every two weeks' },
]

const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']

const selectedDays = computed(() =>
    form.session_day ? form.session_day.split(',').map(d => d.trim()).filter(Boolean) : []
)

function toggleDay(day) {
    const days = [...selectedDays.value]
    const idx  = days.indexOf(day)
    if (idx === -1) days.push(day)
    else days.splice(idx, 1)
    form.session_day = days.join(', ')
}

function formatTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour   = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const showAddMaterialDialog = ref(false)
const materialType          = ref('pdf')
const isUploading           = ref(false)
const uploadProgress        = ref(0)
const fileInputRef          = ref(null)

const materialForm = reactive({
    title:  '',
    url:    '',
    module: 'Week 1',
    type:   'pdf',
    file:   null,
})
const materialErrors = reactive({})

function openAddMaterial(type = 'pdf') {
    materialType.value  = type
    materialForm.title  = ''
    materialForm.url    = ''
    materialForm.module = 'Week 1'
    materialForm.type   = type
    materialForm.file   = null
    Object.keys(materialErrors).forEach(k => delete materialErrors[k])
    showAddMaterialDialog.value = true
}

function onFileSelect(e) {
    const file = e.target.files?.[0]
    if (file) {
        materialForm.file  = file
        if (!materialForm.title) materialForm.title = file.name.replace(/\.[^/.]+$/, '')
    }
}

function handleMaterialSubmit() {
    Object.keys(materialErrors).forEach(k => delete materialErrors[k])
    if (!materialForm.title.trim()) { materialErrors.title = 'Title is required'; return }
    if (materialType.value === 'pdf' && !materialForm.file) { materialErrors.file = 'Please select a file'; return }
    if ((materialType.value === 'link' || materialType.value === 'video') && !materialForm.url.trim()) {
        materialErrors.url = 'URL is required'; return
    }

    isUploading.value = true

    const fd = new FormData()
    fd.append('title',  materialForm.title)
    fd.append('type',   materialType.value)
    fd.append('module', materialForm.module)
    if (materialForm.file) fd.append('file', materialForm.file)
    if (materialForm.url)  fd.append('url',  materialForm.url)

    router.post(`/dashboard/courses/${props.course?.id}/materials`, fd, {
        forceFormData: true,
        onSuccess: () => {
            showAddMaterialDialog.value = false
            toast.success('Material added')
        },
        onError: (errs) => { Object.assign(materialErrors, errs) },
        onFinish: () => { isUploading.value = false; uploadProgress.value = 0 },
    })
}

function deleteMaterial(mat) {
    router.delete(`/dashboard/courses/${props.course?.id}/materials/${mat.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Material removed'),
    })
}

function createBatch() {
    if (!props.course?.id) { toast.error('Save the course first'); return }
    router.visit(`/dashboard/batches/create?course_id=${props.course.id}`)
}

function viewBatch(batch) {
    router.visit(`/dashboard/batches/${batch.id}`)
}

const groupedMaterials = computed(() => {
    const map = {}
    for (const mat of props.materials) {
        const key = mat.module || 'General'
        if (!map[key]) map[key] = []
        map[key].push(mat)
    }
    return Object.entries(map)
})

const stepLabels = ['Details', 'Schedule', 'Materials', 'Batches']
const stepIcons  = [BookOpen, Calendar, FileText, Users]

const stepsComplete = computed(() => ({
    1: !!(form.title && form.description && form.academic_level_id),
    2: !!(form.session_frequency && form.session_day && form.session_platform),
    3: props.materials.length > 0,
    4: props.batches.length > 0,
}))

const statusConfig = {
    active:   { label: 'Published', variant: 'default'   },
    draft:    { label: 'Draft',     variant: 'secondary' },
    archived: { label: 'Archived',  variant: 'outline'   },
}

const batchStatusConfig = {
    open:      { label: 'Enrolling',   variant: 'default'   },
    active:    { label: 'In Progress', variant: 'secondary' },
    closed:    { label: 'Closed',      variant: 'outline'   },
    completed: { label: 'Completed',   variant: 'outline'   },
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
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
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0" @click="router.visit('/dashboard/courses')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground tracking-tight">
                            {{ isEditMode ? (form.title || 'Edit Course') : 'New Course' }}
                        </h1>
                        <Badge v-if="props.course?.status"
                            :variant="statusConfig[props.course.status]?.variant ?? 'secondary'" class="text-xs">
                            {{ statusConfig[props.course.status]?.label ?? props.course.status }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        {{ isEditMode ? 'Edit course details, schedule, materials and batches.' : 'Complete each step to set up your course.' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="isEditMode && props.course?.status === 'active'" variant="outline" size="sm"
                        class="gap-2 hidden sm:flex" @click="handleArchive">
                        <Archive class="h-4 w-4" />Archive
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2" :disabled="isSaving" @click="handleSave">
                        <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isSaving ? 'Saving…' : 'Save' }}
                    </Button>
                    <Button v-if="!isEditMode || props.course?.status === 'draft'" size="sm" class="gap-2"
                        :disabled="isPublishing || !isEditMode" @click="handlePublish">
                        <RefreshCw v-if="isPublishing" class="h-4 w-4 animate-spin" />
                        <Globe v-else class="h-4 w-4" />
                        {{ isPublishing ? 'Publishing…' : 'Publish' }}
                    </Button>
                </div>
            </div>

            <div class="flex items-center gap-1 overflow-x-auto pb-1">
                <template v-for="(label, i) in stepLabels" :key="i">
                    <button type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all shrink-0"
                        :class="step === i + 1
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : stepsComplete[i + 1]
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                        @click="step = i + 1">
                        <CheckCircle2 v-if="stepsComplete[i + 1] && step !== i + 1" class="h-3 w-3" />
                        <component :is="stepIcons[i]" v-else class="h-3 w-3" />
                        {{ i + 1 }}. {{ label }}
                    </button>
                    <div v-if="i < 3" class="h-px w-4 bg-border shrink-0" />
                </template>
            </div>

            <div v-if="step === 1">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <BookOpen class="h-4 w-4 text-primary" />Course Details
                        </CardTitle>
                        <CardDescription class="text-xs">Basic information students see on the course listing page.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-1.5">
                            <Label>Course Title <span class="text-destructive">*</span></Label>
                            <Input v-model="form.title" placeholder="e.g., Primary 4 Mathematics"
                                :class="errors.title && 'border-destructive'" />
                            <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Course Code <span class="text-destructive">*</span></Label>
                            <Input v-model="form.course_code" placeholder="e.g., PRI4-MATH-001"
                                :class="errors.course_code && 'border-destructive'" />
                            <p v-if="errors.course_code" class="text-xs text-destructive">{{ errors.course_code }}</p>
                            <p class="text-xs text-muted-foreground">A short unique identifier for this course.</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Description <span class="text-destructive">*</span></Label>
                            <Textarea v-model="form.description"
                                placeholder="Describe what students will learn, who the course is for, and what's included…"
                                :rows="4" :class="errors.description && 'border-destructive'" />
                            <p v-if="errors.description" class="text-xs text-destructive">{{ errors.description }}</p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Academic Level <span class="text-destructive">*</span></Label>
                                <Select v-model="form.academic_level_id">
                                    <SelectTrigger :class="errors.academic_level_id && 'border-destructive'">
                                        <SelectValue placeholder="Select level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="lvl in props.academicLevels" :key="lvl.id"
                                            :value="String(lvl.id)">
                                            {{ lvl.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.academic_level_id" class="text-xs text-destructive">{{ errors.academic_level_id }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Duration (Weeks) <span class="text-destructive">*</span></Label>
                                <Input v-model.number="form.duration_weeks" type="number" min="1" max="104"
                                    :class="errors.duration_weeks && 'border-destructive'" />
                                <p v-if="errors.duration_weeks" class="text-xs text-destructive">{{ errors.duration_weeks }}</p>
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Price per Student (₦)</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground font-medium">₦</span>
                                    <Input v-model.number="form.price" type="number" min="0" placeholder="0"
                                        class="pl-7" :class="errors.price && 'border-destructive'" />
                                </div>
                                <p v-if="errors.price" class="text-xs text-destructive">{{ errors.price }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Max Students per Batch <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                <Input v-model.number="form.max_students" type="number" min="1" placeholder="Unlimited" />
                            </div>
                        </div>

                        <!-- Course thumbnail -->
                        <div class="space-y-1.5">
                            <Label>Course Image <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <div class="flex items-start gap-4">
                                <!-- Preview -->
                                <div class="relative shrink-0">
                                    <div v-if="thumbnailPreview"
                                        class="h-24 w-36 rounded-lg border border-border overflow-hidden bg-muted">
                                        <img :src="thumbnailPreview" alt="Course thumbnail"
                                            class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else
                                        class="flex h-24 w-36 items-center justify-center rounded-lg border-2 border-dashed border-border bg-muted/30 text-muted-foreground cursor-pointer hover:border-primary/40 transition-colors"
                                        @click="thumbnailInput?.click()">
                                        <div class="text-center">
                                            <Eye class="h-6 w-6 mx-auto mb-1 opacity-40" />
                                            <p class="text-[10px]">No image</p>
                                        </div>
                                    </div>
                                    <button v-if="thumbnailPreview" type="button"
                                        class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-white text-xs shadow"
                                        @click="clearThumbnail">✕</button>
                                </div>
                                <!-- Upload button -->
                                <div class="flex-1 space-y-2">
                                    <p class="text-xs text-muted-foreground">
                                        Upload a cover image for this course. Shown on the course listing page.
                                        JPG, PNG or WEBP — max 2 MB.
                                    </p>
                                    <Button type="button" variant="outline" size="sm" class="gap-2"
                                        @click="thumbnailInput?.click()">
                                        <Upload class="h-3.5 w-3.5" />
                                        {{ thumbnailPreview ? 'Change Image' : 'Upload Image' }}
                                    </Button>
                                    <input ref="thumbnailInput" type="file" accept="image/jpeg,image/png,image/webp"
                                        class="hidden" @change="onThumbnailSelect" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <div class="flex justify-end mt-4">
                    <Button class="gap-2" @click="nextStep">
                        Next: Schedule <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div v-if="step === 2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-primary" />Live Session Schedule
                        </CardTitle>
                        <CardDescription class="text-xs">
                            Set the default schedule for all batches of this course. Each batch can override this individually.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Session Frequency <span class="text-destructive">*</span></Label>
                                <Select v-model="form.session_frequency">
                                    <SelectTrigger :class="errors.session_frequency && 'border-destructive'">
                                        <SelectValue placeholder="How often?" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="f in FREQUENCIES" :key="f.value" :value="f.value">
                                            {{ f.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.session_frequency" class="text-xs text-destructive">{{ errors.session_frequency }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Session Duration (minutes) <span class="text-destructive">*</span></Label>
                                <Input v-model.number="form.session_duration_minutes" type="number" min="15" max="480"
                                    :class="errors.session_duration_minutes && 'border-destructive'" />
                                <p v-if="errors.session_duration_minutes" class="text-xs text-destructive">{{ errors.session_duration_minutes }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Class Days <span class="text-destructive">*</span></Label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="day in DAYS" :key="day" type="button"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all"
                                    :class="selectedDays.includes(day)
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'bg-card border-border text-foreground hover:border-primary/40'"
                                    @click="toggleDay(day)">
                                    {{ day.slice(0, 3) }}
                                </button>
                            </div>
                            <p v-if="errors.session_day" class="text-xs text-destructive">{{ errors.session_day }}</p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Class Start Time</Label>
                                <Input v-model="form.session_time" type="time" />
                                <p v-if="form.session_time" class="text-xs text-muted-foreground">{{ formatTime(form.session_time) }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Live Class Platform <span class="text-destructive">*</span></Label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button v-for="p in PLATFORMS" :key="p.value" type="button"
                                    class="flex items-center gap-2 rounded-lg border p-3 text-left text-sm font-medium transition-all"
                                    :class="form.session_platform === p.value
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border bg-card text-foreground hover:border-primary/40'"
                                    @click="form.session_platform = p.value">
                                    <Video class="h-4 w-4 shrink-0" />{{ p.label }}
                                </button>
                            </div>
                            <p v-if="errors.session_platform" class="text-xs text-destructive">{{ errors.session_platform }}</p>
                            <div class="flex items-start gap-2 rounded-lg bg-muted/40 border border-border p-3 mt-1">
                                <Info class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                <p class="text-xs text-muted-foreground">
                                    Meeting links are added manually by the instructor before each class.
                                    Students find the link in their dashboard and WhatsApp group.
                                </p>
                            </div>
                        </div>

                        <div v-if="form.session_day && form.session_time" class="rounded-lg bg-muted/30 border border-border p-4">
                            <p class="text-xs font-semibold text-foreground mb-1 uppercase tracking-wide">Schedule Preview</p>
                            <p class="text-sm text-foreground">{{ form.session_day }} · {{ formatTime(form.session_time) }}</p>
                            <p class="text-xs text-muted-foreground">{{ form.session_duration_minutes }} min per session</p>
                        </div>
                    </CardContent>
                </Card>
                <div class="flex items-center justify-between mt-4">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back
                    </Button>
                    <Button class="gap-2" @click="nextStep">
                        Next: Materials <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div v-if="step === 3">
                <div class="space-y-4">
                    <div v-if="!isEditMode" class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 p-4 flex items-start gap-3">
                        <Info class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-700 dark:text-amber-400">Save the course first to upload materials.</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Course Materials</p>
                            <p class="text-xs text-muted-foreground mt-0.5">PDFs, worksheets, videos and links students can access.</p>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" class="gap-2" :disabled="!isEditMode" @click="openAddMaterial('pdf')">
                                <Upload class="h-4 w-4" />Upload File
                            </Button>
                            <Button variant="outline" size="sm" class="gap-2" :disabled="!isEditMode" @click="openAddMaterial('link')">
                                <Link2 class="h-4 w-4" />Add Link
                            </Button>
                            <Button variant="outline" size="sm" class="gap-2" :disabled="!isEditMode" @click="openAddMaterial('video')">
                                <Video class="h-4 w-4" />Add Video
                            </Button>
                        </div>
                    </div>

                    <div v-if="groupedMaterials.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
                        <FileText class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm text-muted-foreground">No materials yet. Upload your first resource above.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="[module, mats] in groupedMaterials" :key="module">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold text-foreground uppercase tracking-wide">{{ module }}</span>
                                <div class="flex-1 h-px bg-border" />
                            </div>
                            <div class="space-y-2">
                                <div v-for="mat in mats" :key="mat.id"
                                    class="group flex items-center gap-3 rounded-lg border border-border bg-card p-3 hover:bg-muted/40 transition-colors">
                                    <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', matTypeColor(mat.type)]">
                                        <component :is="matTypeIcon(mat.type)" class="h-4 w-4" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ mat.title }}</p>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                                            <span class="capitalize">{{ mat.type }}</span>
                                            <span v-if="mat.size_kb">{{ fmtSize(mat.size_kb) }}</span>
                                            <span class="flex items-center gap-1">
                                                <Download class="h-3 w-3" />{{ mat.downloads }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Button variant="ghost" size="icon" class="h-7 w-7" as-child>
                                            <a :href="mat.url" target="_blank" rel="noopener noreferrer">
                                                <ExternalLink class="h-3.5 w-3.5" />
                                            </a>
                                        </Button>
                                        <Button variant="ghost" size="icon"
                                            class="h-7 w-7 text-destructive hover:text-destructive"
                                            @click="deleteMaterial(mat)">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back
                    </Button>
                    <Button class="gap-2" @click="nextStep">
                        Next: Batches <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div v-if="step === 4">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Batches for this course</p>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                Each batch is an independent cohort of students. Create multiple batches to run this course at different times.
                            </p>
                        </div>
                        <Button size="sm" class="gap-2" :disabled="!isEditMode" @click="createBatch">
                            <Plus class="h-4 w-4" />Create Batch
                        </Button>
                    </div>

                    <div v-if="!isEditMode" class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 p-4 flex items-start gap-3">
                        <Info class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-700 dark:text-amber-400">Save the course first before creating batches.</p>
                    </div>

                    <div v-else-if="props.batches.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
                        <Users class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                        <p class="text-sm font-medium text-foreground">No batches yet</p>
                        <p class="text-xs text-muted-foreground mt-1">Create a batch so students can enroll.</p>
                        <Button class="mt-4 gap-2" size="sm" @click="createBatch">
                            <Plus class="h-4 w-4" />Create First Batch
                        </Button>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="batch in props.batches" :key="batch.id"
                            class="flex items-center gap-4 rounded-xl border border-border bg-card p-4 hover:bg-muted/30 transition-colors cursor-pointer"
                            @click="viewBatch(batch)">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-foreground">{{ batch.name }}</p>
                                    <Badge :variant="batchStatusConfig[batch.status]?.variant ?? 'outline'" class="text-xs">
                                        {{ batchStatusConfig[batch.status]?.label ?? batch.status }}
                                    </Badge>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-muted-foreground mt-1">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />{{ fmtDate(batch.start_date) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Users class="h-3 w-3" />{{ batch.current_enrollment }}/{{ batch.max_students }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <Button variant="outline" size="sm" class="gap-1.5 text-xs h-8"
                                    @click.stop="viewBatch(batch)">
                                    <Eye class="h-3.5 w-3.5" />View
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div v-if="props.course?.status === 'draft' && props.batches.length > 0"
                        class="rounded-xl bg-primary/5 border border-primary/20 p-4 flex items-center gap-4">
                        <CheckCircle2 class="h-6 w-6 text-primary shrink-0" />
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-foreground">Ready to publish?</p>
                            <p class="text-xs text-muted-foreground">You have {{ props.batches.length }} batch{{ props.batches.length > 1 ? 'es' : '' }} ready. Publish the course so students can enroll.</p>
                        </div>
                        <Button size="sm" class="gap-2 shrink-0" :disabled="isPublishing" @click="handlePublish">
                            <Globe class="h-4 w-4" />Publish Course
                        </Button>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back
                    </Button>
                    <Button class="gap-2" :disabled="isSaving" @click="handleSave">
                        <Save class="h-4 w-4" />Save All Changes
                    </Button>
                </div>
            </div>
        </div>

        <Dialog :open="showAddMaterialDialog" @update:open="showAddMaterialDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <component :is="materialType === 'link' ? Link2 : materialType === 'video' ? Video : FileText" class="h-5 w-5 text-primary" />
                        {{ materialType === 'link' ? 'Add Link' : materialType === 'video' ? 'Add Video' : 'Upload File' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        {{ materialType === 'pdf' ? 'Upload a PDF, DOCX or PPTX file.' : 'Add a URL students can access.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Material Title <span class="text-destructive">*</span></Label>
                        <Input v-model="materialForm.title" placeholder="e.g., Week 1 — Introduction Notes"
                            :class="materialErrors.title && 'border-destructive'" />
                        <p v-if="materialErrors.title" class="text-xs text-destructive">{{ materialErrors.title }}</p>
                    </div>

                    <div v-if="materialType === 'pdf'" class="space-y-1.5">
                        <Label>File <span class="text-destructive">*</span></Label>
                        <div class="flex flex-col items-center gap-2 rounded-lg border-2 border-dashed border-border p-4 text-center hover:border-primary/40 transition-colors cursor-pointer"
                            @click="fileInputRef?.click()">
                            <Upload class="h-5 w-5 text-muted-foreground" />
                            <span class="text-xs text-muted-foreground">
                                {{ materialForm.file ? materialForm.file.name : 'Click to select file (PDF, DOCX, PPTX — max 50 MB)' }}
                            </span>
                        </div>
                        <input ref="fileInputRef" type="file" accept=".pdf,.doc,.docx,.pptx,.xlsx" class="hidden" @change="onFileSelect" />
                        <p v-if="materialErrors.file" class="text-xs text-destructive">{{ materialErrors.file }}</p>
                        <div v-if="isUploading" class="space-y-1">
                            <Progress :value="uploadProgress" class="h-1.5" />
                            <p class="text-xs text-muted-foreground text-right">Uploading…</p>
                        </div>
                    </div>

                    <div v-else class="space-y-1.5">
                        <Label>URL <span class="text-destructive">*</span></Label>
                        <Input v-model="materialForm.url"
                            :placeholder="materialType === 'video' ? 'https://youtube.com/…' : 'https://…'"
                            type="url" :class="materialErrors.url && 'border-destructive'" />
                        <p v-if="materialErrors.url" class="text-xs text-destructive">{{ materialErrors.url }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Module / Week</Label>
                        <Input v-model="materialForm.module" placeholder="e.g., Week 1 or Module 2" />
                        <p class="text-xs text-muted-foreground">Used to group materials together.</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="isUploading" @click="showAddMaterialDialog = false">Cancel</Button>
                    <Button :disabled="isUploading" @click="handleMaterialSubmit">
                        <RefreshCw v-if="isUploading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isUploading ? 'Uploading…' : materialType === 'pdf' ? 'Upload' : 'Add' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>