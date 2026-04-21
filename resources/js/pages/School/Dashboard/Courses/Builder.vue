<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, ArrowRight, Save, Globe, Archive,
    BookOpen, FileText, Users, Plus, Trash2, Upload,
    Link2, Video, CheckCircle2, RefreshCw,
    ExternalLink, Info, ImageIcon, X,
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
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    course:         { type: Object, default: null },
    materials:      { type: Array,  default: () => [] },
    academicLevels: { type: Array,  default: () => [] },
})

const page       = usePage()
const isEditMode = computed(() => !!props.course?.id)
const isSaving   = ref(false)
const isPublishing = ref(false)
const step       = ref(1)

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

// ── Form ───────────────────────────────────────────────────────────────────────
const form = reactive({
    title:             props.course?.title             ?? '',
    course_code:       props.course?.course_code       ?? '',
    description:       props.course?.description       ?? '',
    academic_level_id: props.course?.academic_level_id ?? '',
    duration_weeks:    props.course?.duration_weeks    ?? 12,
})

const thumbnailFile    = ref(null)
const thumbnailPreview = ref(props.course?.thumbnail ?? null)
const thumbnailInputRef = ref(null)
const errors = reactive({})

function onThumbnailSelect(e) {
    const file = e.target.files?.[0]
    if (!file) return
    thumbnailFile.value    = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function clearThumbnail() {
    thumbnailFile.value    = null
    thumbnailPreview.value = null
    if (thumbnailInputRef.value) thumbnailInputRef.value.value = ''
}

// ── Validation ─────────────────────────────────────────────────────────────────
function validateDetails() {
    const e = {}
    if (!form.title.trim())        e.title            = 'Title is required'
    if (!form.course_code.trim())  e.course_code      = 'Course code is required'
    if (!form.description?.trim()) e.description      = 'Description is required'
    if (!form.academic_level_id)   e.academic_level_id = 'Select an academic level'
    if ((form.duration_weeks ?? 0) < 1) e.duration_weeks = 'Must be at least 1 week'
    Object.assign(errors, e)
    return Object.keys(e).length === 0
}

function nextStep() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (step.value === 1 && !validateDetails()) return
    if (step.value < 3) step.value++
}

function prevStep() {
    if (step.value > 1) step.value--
}

// ── Save ───────────────────────────────────────────────────────────────────────
function handleSave() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!validateDetails()) { step.value = 1; return }

    isSaving.value = true

    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') fd.append(k, v)
    })
    if (thumbnailFile.value) fd.append('thumbnail', thumbnailFile.value)

    const url = isEditMode.value
        ? `/dashboard/courses/${props.course.id}/edit`
        : '/dashboard/courses/create'

    router.post(url, fd, {
        forceFormData: true,
        onError: (errs) => {
            Object.assign(errors, errs)
            if (errs.title || errs.course_code || errs.academic_level_id) step.value = 1
        },
        onFinish: () => { isSaving.value = false },
    })
}

function handlePublish() {
    if (!isEditMode.value) return
    isPublishing.value = true
    router.post(`/dashboard/courses/${props.course.id}/publish`, {}, {
        onFinish: () => { isPublishing.value = false },
    })
}

function handleArchive() {
    if (!isEditMode.value) return
    router.post(`/dashboard/courses/${props.course.id}/archive`, {})
}

// ── Material upload ────────────────────────────────────────────────────────────
const showMaterialDialog = ref(false)
const materialType       = ref('pdf')
const isUploadingMat     = ref(false)
const matFileInputRef    = ref(null)

const matForm = reactive({ title: '', module: 'Week 1', file: null, url: '' })
const matErrors = reactive({})

function openAddMaterial(type) {
    materialType.value   = type
    matForm.title  = ''
    matForm.module = 'Week 1'
    matForm.file   = null
    matForm.url    = ''
    Object.keys(matErrors).forEach(k => delete matErrors[k])
    showMaterialDialog.value = true
}

function onMatFileSelect(e) {
    const file = e.target.files?.[0]
    if (!file) return
    matForm.file = file
    if (!matForm.title) matForm.title = file.name.replace(/\.[^/.]+$/, '')
}

function submitMaterial() {
    Object.keys(matErrors).forEach(k => delete matErrors[k])
    if (!matForm.title.trim()) { matErrors.title = 'Title is required'; return }
    if (materialType.value === 'pdf' && !matForm.file) { matErrors.file = 'Select a file'; return }
    if ((materialType.value === 'link' || materialType.value === 'video') && !matForm.url.trim()) {
        matErrors.url = 'URL is required'; return
    }
    if (!isEditMode.value) { toast.error('Save the course first'); return }

    isUploadingMat.value = true
    const fd = new FormData()
    fd.append('title', matForm.title)
    fd.append('material_type', materialType.value)
    fd.append('module', matForm.module)
    if (matForm.file) fd.append('file', matForm.file)
    if (matForm.url)  fd.append('url', matForm.url)

    router.post(`/dashboard/courses/${props.course.id}/materials`, fd, {
        forceFormData: true,
        onSuccess: () => { showMaterialDialog.value = false },
        onError: (errs) => { Object.assign(matErrors, errs) },
        onFinish: () => { isUploadingMat.value = false },
    })
}

function deleteMaterial(mat) {
    if (!isEditMode.value) return
    router.delete(`/dashboard/courses/${props.course.id}/materials/${mat.id}`, {
        preserveScroll: true,
    })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusConfig = {
    active:   { label: 'Published', variant: 'default'   },
    draft:    { label: 'Draft',     variant: 'secondary' },
    archived: { label: 'Archived',  variant: 'outline'   },
}

function matIcon(type) {
    return { link: Link2, video: Video }[type] ?? FileText
}

function matColor(type) {
    return {
        link:  'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400',
        video: 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400',
    }[type] ?? 'bg-primary/10 text-primary'
}

function fmtSize(kb) {
    if (!kb) return ''
    return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB'
}

const groupedMaterials = computed(() => {
    const map = {}
    for (const m of props.materials) {
        const key = m.module || 'General'
        if (!map[key]) map[key] = []
        map[key].push(m)
    }
    return Object.entries(map)
})

const stepsComplete = computed(() => ({
    1: !!(form.title && form.description && form.academic_level_id),
    2: props.materials.length > 0,
}))

const stepLabels = ['Details', 'Materials', 'Done']
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-3xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/courses')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold tracking-tight text-foreground">
                            {{ isEditMode ? (form.title || 'Edit Course') : 'New Course' }}
                        </h1>
                        <Badge v-if="props.course?.status"
                            :variant="statusConfig[props.course.status]?.variant ?? 'secondary'"
                            class="text-xs">
                            {{ statusConfig[props.course.status]?.label ?? props.course.status }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Courses are subjects. Attach them to batches to create a programme.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="isEditMode && props.course?.status === 'active'"
                        variant="outline" size="sm" class="gap-2 hidden sm:flex" @click="handleArchive">
                        <Archive class="h-4 w-4" />Archive
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2" :disabled="isSaving" @click="handleSave">
                        <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isSaving ? 'Saving…' : 'Save' }}
                    </Button>
                    <Button v-if="props.course?.status !== 'active'" size="sm" class="gap-2"
                        :disabled="isPublishing || !isEditMode" @click="handlePublish">
                        <RefreshCw v-if="isPublishing" class="h-4 w-4 animate-spin" />
                        <Globe v-else class="h-4 w-4" />
                        {{ isPublishing ? 'Publishing…' : 'Publish' }}
                    </Button>
                </div>
            </div>

            <!-- Step indicators -->
            <div class="flex items-center gap-1">
                <template v-for="(label, i) in stepLabels" :key="i">
                    <button type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all shrink-0"
                        :class="step === i + 1
                            ? 'bg-primary text-primary-foreground'
                            : stepsComplete[i + 1]
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                        @click="step = i + 1">
                        <CheckCircle2 v-if="stepsComplete[i + 1] && step !== i + 1" class="h-3 w-3" />
                        <span v-else class="h-3 w-3 flex items-center justify-center text-[10px] font-bold">{{ i + 1 }}</span>
                        {{ label }}
                    </button>
                    <div v-if="i < 2" class="h-px flex-1 bg-border max-w-8" />
                </template>
            </div>

            <!-- Step 1: Details -->
            <div v-if="step === 1" class="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <BookOpen class="h-4 w-4 text-primary" />Course Details
                        </CardTitle>
                        <CardDescription class="text-xs">
                            Basic information about this subject. Price and schedule are set on the batch.
                        </CardDescription>
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
                            <Input v-model="form.course_code" placeholder="e.g., PRI4-MATH"
                                :class="errors.course_code && 'border-destructive'" />
                            <p v-if="errors.course_code" class="text-xs text-destructive">{{ errors.course_code }}</p>
                            <p class="text-xs text-muted-foreground">Short unique code for internal reference.</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Description <span class="text-destructive">*</span></Label>
                            <Textarea v-model="form.description"
                                placeholder="What will students learn? Who is this course for? What topics are covered?"
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
                                <Label>Duration (weeks)</Label>
                                <Input v-model.number="form.duration_weeks" type="number" min="1" max="104"
                                    :class="errors.duration_weeks && 'border-destructive'" />
                                <p v-if="errors.duration_weeks" class="text-xs text-destructive">{{ errors.duration_weeks }}</p>
                                <p class="text-xs text-muted-foreground">Used to auto-fill batch end dates.</p>
                            </div>
                        </div>

                        <!-- Thumbnail -->
                        <div class="space-y-2">
                            <Label>Cover Image <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <div class="flex items-start gap-4">
                                <div class="relative shrink-0">
                                    <div v-if="thumbnailPreview"
                                        class="h-24 w-36 overflow-hidden rounded-lg border border-border bg-muted">
                                        <img :src="thumbnailPreview" alt="Course cover"
                                            class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else
                                        class="flex h-24 w-36 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border bg-muted/30 text-muted-foreground transition-colors hover:border-primary/40"
                                        @click="thumbnailInputRef?.click()">
                                        <ImageIcon class="h-6 w-6 mb-1 opacity-40" />
                                        <span class="text-[10px]">No image</span>
                                    </div>
                                    <button v-if="thumbnailPreview" type="button"
                                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-white shadow"
                                        @click="clearThumbnail">
                                        <X class="h-3 w-3" />
                                    </button>
                                </div>
                                <div class="space-y-2 flex-1">
                                    <p class="text-xs text-muted-foreground">
                                        Shown on your school's course listing page. JPG, PNG or WEBP — max 2 MB.
                                    </p>
                                    <Button type="button" variant="outline" size="sm" class="gap-2"
                                        @click="thumbnailInputRef?.click()">
                                        <Upload class="h-3.5 w-3.5" />
                                        {{ thumbnailPreview ? 'Change Image' : 'Upload Image' }}
                                    </Button>
                                    <input ref="thumbnailInputRef" type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden" @change="onThumbnailSelect" />
                                </div>
                            </div>
                        </div>

                        <!-- Info callout -->
                        <!-- <div class="flex items-start gap-2.5 rounded-lg bg-muted/40 border border-border p-3">
                            <Info class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                            <p class="text-xs text-muted-foreground">
                                <strong class="text-foreground">Price and schedule are not set here.</strong>
                                They belong on the batch — one enrollment fee covers all courses in the batch,
                                and each subject has its own class schedule inside the batch.
                            </p>
                        </div> -->
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button class="gap-2" @click="nextStep">
                        Next: Materials <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Step 2: Materials -->
            <div v-if="step === 2" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-foreground">Course Materials</p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            PDFs, worksheets, videos and links students access from their dashboard.
                        </p>
                    </div>
                    <div v-if="isEditMode" class="flex gap-2">
                        <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('pdf')">
                            <Upload class="h-3.5 w-3.5" />Upload File
                        </Button>
                        <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('link')">
                            <Link2 class="h-3.5 w-3.5" />Add Link
                        </Button>
                        <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('video')">
                            <Video class="h-3.5 w-3.5" />Video
                        </Button>
                    </div>
                </div>

                <div v-if="!isEditMode"
                    class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20 p-3">
                    <Info class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                    <p class="text-xs text-amber-700 dark:text-amber-400">Save the course first to upload materials.</p>
                </div>

                <div v-else-if="groupedMaterials.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-14 text-center">
                    <FileText class="h-8 w-8 text-muted-foreground/40 mb-2" />
                    <p class="text-sm text-muted-foreground">No materials yet</p>
                    <p class="text-xs text-muted-foreground/70 mt-1">Upload PDFs, add YouTube links or document links.</p>
                    <Button class="mt-4 gap-2" size="sm" variant="outline" @click="openAddMaterial('pdf')">
                        <Upload class="h-3.5 w-3.5" />Upload First File
                    </Button>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="[module, mats] in groupedMaterials" :key="module">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ module }}</span>
                            <div class="flex-1 h-px bg-border" />
                        </div>
                        <div class="space-y-2">
                            <div v-for="mat in mats" :key="mat.id"
                                class="group flex items-center gap-3 rounded-lg border border-border bg-card p-3 transition-colors hover:bg-muted/40">
                                <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', matColor(mat.type)]">
                                    <component :is="matIcon(mat.type)" class="h-4 w-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ mat.title }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        {{ mat.type.toUpperCase() }}
                                        <span v-if="mat.size_kb"> · {{ fmtSize(mat.size_kb) }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Button v-if="mat.url" variant="ghost" size="icon" class="h-7 w-7" as-child>
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

                <div class="flex items-center justify-between pt-2">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back
                    </Button>
                    <Button class="gap-2" @click="nextStep">
                        Next <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Step 3: Done / next actions -->
            <div v-if="step === 3" class="space-y-4">
                <Card>
                    <CardContent class="pt-6 pb-6 space-y-6">
                        <div class="text-center space-y-2">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950 mx-auto">
                                <CheckCircle2 class="h-7 w-7 text-emerald-600" />
                            </div>
                            <h2 class="text-lg font-bold text-foreground">
                                {{ isEditMode ? 'Course up to date' : 'Course ready' }}
                            </h2>
                            <p class="text-sm text-muted-foreground max-w-sm mx-auto">
                                This course is a subject. To offer it to students, attach it to a batch,
                                set the schedule and price on the batch, then publish.
                            </p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3 max-w-md mx-auto">
                            <Button class="gap-2 w-full" @click="router.visit('/dashboard/batches/create')">
                                <Plus class="h-4 w-4" />Create a Batch
                            </Button>
                            <Button variant="outline" class="gap-2 w-full"
                                @click="router.visit('/dashboard/batches')">
                                <Users class="h-4 w-4" />View All Batches
                            </Button>
                            <Button variant="outline" class="gap-2 w-full" :disabled="!isEditMode"
                                @click="handlePublish">
                                <Globe class="h-4 w-4" />Publish Course
                            </Button>
                            <Button variant="outline" class="gap-2 w-full"
                                @click="router.visit('/dashboard/courses')">
                                <ArrowLeft class="h-4 w-4" />All Courses
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-start">
                    <Button variant="outline" class="gap-2" @click="prevStep">
                        <ArrowLeft class="h-4 w-4" />Back to Materials
                    </Button>
                </div>
            </div>
        </div>

        <!-- Add material dialog -->
        <Dialog :open="showMaterialDialog" @update:open="showMaterialDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <component :is="matIcon(materialType)" class="h-5 w-5 text-primary" />
                        {{ materialType === 'link' ? 'Add Link' : materialType === 'video' ? 'Add Video' : 'Upload File' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        {{ materialType === 'pdf' ? 'PDF, DOCX or PPTX — max 50 MB' : 'Paste a URL students can open.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Title <span class="text-destructive">*</span></Label>
                        <Input v-model="matForm.title" placeholder="e.g., Week 1 Lecture Notes"
                            :class="matErrors.title && 'border-destructive'" />
                        <p v-if="matErrors.title" class="text-xs text-destructive">{{ matErrors.title }}</p>
                    </div>
                    <div v-if="materialType === 'pdf'" class="space-y-1.5">
                        <Label>File <span class="text-destructive">*</span></Label>
                        <div class="cursor-pointer rounded-lg border-2 border-dashed border-border p-4 text-center text-xs text-muted-foreground transition-colors hover:border-primary/40"
                            @click="matFileInputRef?.click()">
                            {{ matForm.file ? matForm.file.name : 'Click to select file' }}
                        </div>
                        <input ref="matFileInputRef" type="file"
                            accept=".pdf,.doc,.docx,.pptx,.xlsx"
                            class="hidden" @change="onMatFileSelect" />
                        <p v-if="matErrors.file" class="text-xs text-destructive">{{ matErrors.file }}</p>
                    </div>
                    <div v-else class="space-y-1.5">
                        <Label>URL <span class="text-destructive">*</span></Label>
                        <Input v-model="matForm.url"
                            :placeholder="materialType === 'video' ? 'https://youtube.com/watch?v=…' : 'https://…'"
                            type="url" :class="matErrors.url && 'border-destructive'" />
                        <p v-if="matErrors.url" class="text-xs text-destructive">{{ matErrors.url }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Module / Week</Label>
                        <Input v-model="matForm.module" placeholder="e.g., Week 1 or Module 2" />
                        <p class="text-xs text-muted-foreground">Used to group materials together.</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="isUploadingMat" @click="showMaterialDialog = false">
                        Cancel
                    </Button>
                    <Button :disabled="isUploadingMat" @click="submitMaterial">
                        <RefreshCw v-if="isUploadingMat" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isUploadingMat ? 'Uploading…' : materialType === 'pdf' ? 'Upload' : 'Add' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>