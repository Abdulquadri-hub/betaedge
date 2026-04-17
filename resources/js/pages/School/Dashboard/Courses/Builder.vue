<script setup>

import { ref, computed, watch, onMounted } from 'vue'
import {router } from '@inertiajs/vue3'
import {
  ArrowLeft, Save, Globe, Archive, Eye,
  BookOpen, Calendar, FileText, Users,
  Plus, Trash2, Upload, Link2, Video,
  CheckCircle2, RefreshCw,
  ExternalLink, Download, Info,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge }    from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select, SelectContent, SelectItem,
  SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
  Dialog, DialogContent, DialogDescription,
  DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { Progress } from '@/components/ui/progress'
import { toast } from "vue-sonner"
import {
  useDashboardCourses,
  SESSION_PLATFORMS,
  SESSION_FREQUENCIES,
  SCHEDULE_DAYS,
} from '@/composables/useDashboardCourses'
import { useDashboardBatches } from '@/composables/useDashboardBatches'


const {
  getCourseById, getMaterials,
  createCourse, updateCourse, publishCourse, archiveCourse,
  // formatNaira, 
  formatTime, platformLabel, frequencyLabel,
} = useDashboardCourses()

const {
  filteredBatches: allBatches,
  createBatch, deleteBatch,
  formatNaira: fmtNaira,
  // enrollmentPct, isFull,
} = useDashboardBatches()

const props = defineProps({
  course: {
    type: Object,
    default: null,
  },
  materials: {
    type: Array,
    default: () => [],
  },
  batches: {
    type: Array,
    default: () => [],
  },
  academicLevels: {
    type: Array,
    default: () => [],
  },
})

const courseId  = ref(props.course?.id ?? null)
const isEditMode = computed(() => !!courseId.value)

const course    = ref(props.course ?? null)
const materials = ref(props.materials ?? [])
const batches   = ref(props.batches ?? [])
const pageLoading = ref(false)

onMounted(async () => {
  if (courseId.value && !course.value) {
    // Fallback: fetch from composable if not in props
    course.value = getCourseById(courseId.value)
    materials.value = await getMaterials(courseId.value)
    batches.value   = allBatches.value.filter(b => b.course_id === courseId.value)
  }
  pageLoading.value = false
})

const activeTab = ref('details')

const hasUnsavedChanges = ref(false)

const detailsForm = ref({
  title:             course.value?.title             ?? '',
  course_code:       course.value?.course_code       ?? '',
  description:       course.value?.description       ?? '',
  academic_level_id: course.value?.academic_level_id ?? '',
  duration_weeks:    course.value?.duration_weeks    ?? 12,
  price:             course.value?.price             ?? 0,
})
const detailsErrors = ref({})

watch(course, (c) => {
  if (c && !hasUnsavedChanges.value) {
    detailsForm.value = {
      title:             c.title,
      course_code:       c.course_code,
      description:       c.description       ?? '',
      academic_level_id: c.academic_level_id,
      duration_weeks:    c.duration_weeks,
      price:             c.price,
    }
  }
})

watch(detailsForm, () => { hasUnsavedChanges.value = true }, { deep: true })

function validateDetails() {
  const e = {}
  if (!detailsForm.value.title.trim())        e.title          = 'Course title is required'
  if (detailsForm.value.title.length > 150)   e.title          = 'Title must be under 150 characters'
  if (!detailsForm.value.course_code.trim())  e.course_code    = 'Course code is required'
  if (detailsForm.value.course_code.length > 20) e.course_code = 'Course code must be under 20 characters'
  if (!detailsForm.value.description.trim())  e.description    = 'Description is required'
  if (!detailsForm.value.academic_level_id)   e.academic_level_id = 'Academic level is required'
  if (detailsForm.value.duration_weeks < 1)   e.duration_weeks = 'Must be at least 1 week'
  if (detailsForm.value.price < 0)            e.price = 'Price cannot be negative'
  detailsErrors.value = e
  return Object.keys(e).length === 0
}

const scheduleForm = ref({
  session_frequency:        course.value?.session_frequency        ?? '',
  session_day:              course.value?.session_day              ?? '',
  session_time:             course.value?.session_time             ?? '16:00',
  session_duration_minutes: course.value?.session_duration_minutes ?? 90,
  session_platform:         course.value?.session_platform         ?? '',
})
const scheduleErrors = ref({})

watch(course, (c) => {
  if (c && !hasUnsavedChanges.value) {
    scheduleForm.value = {
      session_frequency:        c.session_frequency,
      session_day:              c.session_day              ?? '',
      session_time:             c.session_time             ?? '16:00',
      session_duration_minutes: c.session_duration_minutes ?? 90,
      session_platform:         c.session_platform,
    }
  }
})

watch(scheduleForm, () => { hasUnsavedChanges.value = true }, { deep: true })

function validateSchedule() {
  const e = {}
  if (!scheduleForm.value.session_frequency) e.session_frequency = 'Select a frequency'
  if (!scheduleForm.value.session_day)       e.session_day       = 'Select class days'
  if (!scheduleForm.value.session_platform)  e.session_platform  = 'Select a platform'
  if (scheduleForm.value.session_duration_minutes < 15) e.session_duration_minutes = 'Minimum 15 minutes'
  scheduleErrors.value = e
  return Object.keys(e).length === 0
}

const isSaving     = ref(false)
const isPublishing = ref(false)

async function handleSave() {
  const detailsOk  = validateDetails()
  const scheduleOk = validateSchedule()
  if (!detailsOk) { activeTab.value = 'details';  return }
  if (!scheduleOk){ activeTab.value = 'schedule'; return }

  isSaving.value = true
  const data = { ...detailsForm.value, ...scheduleForm.value }

  try {
    let result
    if (isEditMode.value) {
      result = await updateCourse(courseId.value, data)
    } else {
      result = await createCourse(data)
      if (result?.course?.id) {
        courseId.value   = result.course.id
        // TODO (Laravel 12): router.replace(route('dashboard.courses.edit', result.course.id))
      }
    }

    if (result?.success !== false) {
      hasUnsavedChanges.value = false
      toast({ title: 'Saved', description: 'Course details saved successfully.' })
    } else {
      toast({ title: 'Error', description: 'Failed to save. Please try again.', variant: 'destructive' })
    }
  } finally {
    isSaving.value = false
  }
}

async function handlePublish() {
  if (!validateDetails() || !validateSchedule()) {
    toast({ title: 'Fix errors first', description: 'Complete all required fields before publishing.', variant: 'destructive' })
    return
  }
  isPublishing.value = true
  try {
    await handleSave()
    if (courseId.value) {
      await publishCourse(courseId.value)
      toast({ title: 'Course published!', description: 'Students can now discover and enroll.' })
    }
  } finally {
    isPublishing.value = false
  }
}

async function handleArchive() {
  if (!courseId.value) return
  await archiveCourse(courseId.value)
  toast({ title: 'Course archived', description: 'No longer visible to students.' })
}

const showAddMaterialDialog = ref(false)
const materialType          = ref('pdf')   
const isDragOver            = ref(false)
const uploadProgress        = ref(0)
const isUploading           = ref(false)

const materialForm = ref({
  title:  '',
  url:    '',
  module: 'Week 1',
  week:   1,
  type:   'pdf',
  file:   null,
})
const materialErrors = ref({})

const weekOptions = computed(() =>
  Array.from({ length: detailsForm.value.duration_weeks || 12 }, (_, i) => i + 1)
)

function openAddMaterial(type = 'pdf') {
  materialType.value = type
  materialForm.value = {
    title:  '',
    url:    '',
    module: `Week ${weekOptions.value[0]}`,
    week:   1,
    type,
    file:   null,
  }
  materialErrors.value        = {}
  showAddMaterialDialog.value = true
}

function handleDrop(e) {
  e.preventDefault()
  isDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) {
    materialForm.value.file  = file
    materialForm.value.title = materialForm.value.title || file.name.replace(/\.[^/.]+$/, '')
    materialType.value       = 'pdf'
    showAddMaterialDialog.value = true
  }
}

function onFileSelect(e) {
  const file = e.target.files?.[0]
  if (file) {
    materialForm.value.file  = file
    materialForm.value.title = materialForm.value.title || file.name.replace(/\.[^/.]+$/, '')
  }
}

function validateMaterial() {
  const e = {}
  if (!materialForm.value.title.trim()) e.title = 'Material title is required'
  if (materialType.value === 'pdf' && !materialForm.value.file && !materialForm.value.url) {
    e.file = 'Please select a file or enter a URL'
  }
  if ((materialType.value === 'link' || materialType.value === 'video') && !materialForm.value.url.trim()) {
    e.url = 'URL is required'
  }
  materialErrors.value = e
  return Object.keys(e).length === 0
}

async function handleAddMaterial() {
  if (!validateMaterial()) return
  isUploading.value = true

  /**
   * TODO (Laravel 12) — multipart/form-data upload:
   * const formData = new FormData()
   * formData.append('title',  materialForm.value.title)
   * formData.append('type',   materialType.value)
   * formData.append('module', materialForm.value.module)
   * formData.append('week',   materialForm.value.week)
   * if (materialForm.value.file) formData.append('file', materialForm.value.file)
   * if (materialForm.value.url)  formData.append('url',  materialForm.value.url)
   *
   * router.post(route('dashboard.courses.materials.store', courseId.value), formData, {
   *   forceFormData: true,
   *   onProgress: (p) => { uploadProgress.value = p.percentage },
   *   onSuccess: () => { showAddMaterialDialog.value = false },
   *   onError: (e) => { materialErrors.value = e },
   *   preserveScroll: true,
   * })
   */

  // Mock upload progress
  for (let i = 10; i <= 100; i += 20) {
    uploadProgress.value = i
    await new Promise(r => setTimeout(r, 120))
  }

  const newMat = {
    id:          'mat-' + Date.now(),
    course_id:   courseId.value,
    title:       materialForm.value.title,
    type:        materialType.value,
    module:      materialForm.value.module,
    week:        materialForm.value.week,
    url:         materialForm.value.url || '#',
    size_kb:     materialForm.value.file ? Math.round(materialForm.value.file.size / 1024) : 0,
    downloads:   0,
    uploaded_at: new Date().toISOString().split('T')[0],
  }

  materials.value.push(newMat)
  showAddMaterialDialog.value = false
  uploadProgress.value        = 0
  isUploading.value           = false
  toast({ title: 'Material added', description: `${newMat.title} uploaded successfully.` })
}

async function handleDeleteMaterial(mat) {
  /**
   * TODO (Laravel 12):
   * router.delete(route('dashboard.courses.materials.destroy', { course: courseId.value, material: mat.id }), {
   *   preserveScroll: true,
   * })
   */
  materials.value = materials.value.filter(m => m.id !== mat.id)
  toast({ title: 'Material removed' })
}

// Group materials by module/week
const groupedMaterials = computed(() => {
  const map = {}
  for (const mat of materials.value) {
    if (!map[mat.module]) map[mat.module] = []
    map[mat.module].push(mat)
  }
  // Sort modules by week number
  return Object.entries(map).sort(([a], [b]) => {
    const numA = parseInt(a.replace(/\D/g, '')) || 999
    const numB = parseInt(b.replace(/\D/g, '')) || 999
    return numA - numB
  })
})

function matTypeIcon(type) {
  if (type === 'link')  return Link2
  if (type === 'video') return Video
  return FileText
}

function matTypeColor(type) {
  if (type === 'link')  return 'bg-secondary/10 text-secondary'
  if (type === 'video') return 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400'
  return 'bg-primary/10 text-primary'
}

function formatSize(kb) {
  if (!kb) return ''
  if (kb >= 1024) return (kb / 1024).toFixed(1) + ' MB'
  return kb + ' KB'
}


const showBatchDialog   = ref(false)
const isSavingBatch     = ref(false)
const batchForm         = ref({
  name:              '',
  start_date:        '',
  end_date:          '',
  max_students:      30,
  price_per_student: detailsForm.value.price,
  schedule_day:      scheduleForm.value.session_day,
  schedule_time:     scheduleForm.value.session_time,
  whatsapp_link:     '',
  status:            'open',
})
const batchErrors = ref({})

// Keep batch default price in sync with course price
watch(() => detailsForm.value.price, (v) => {
  if (!batchForm.value.price_per_student) batchForm.value.price_per_student = v
})

function openAddBatch() {
  batchForm.value = {
    name:              '',
    start_date:        '',
    end_date:          '',
    max_students:      30,
    price_per_student: detailsForm.value.price,
    schedule_day:      scheduleForm.value.session_day,
    schedule_time:     scheduleForm.value.session_time,
    whatsapp_link:     '',
    status:            'open',
  }
  batchErrors.value  = {}
  showBatchDialog.value = true
}

function validateBatch() {
  const e = {}
  if (!batchForm.value.name.trim())  e.name       = 'Batch name is required'
  if (!batchForm.value.start_date)   e.start_date = 'Start date is required'
  if (!batchForm.value.end_date)     e.end_date   = 'End date is required'
  if (batchForm.value.start_date && batchForm.value.end_date &&
      batchForm.value.start_date >= batchForm.value.end_date) {
    e.end_date = 'End date must be after start date'
  }
  if (batchForm.value.max_students < 1) e.max_students = 'At least 1 student'
  batchErrors.value = e
  return Object.keys(e).length === 0
}

async function handleSaveBatch() {
  if (!validateBatch()) return
  if (!courseId.value) {
    toast({ title: 'Save course first', description: 'Save the course details before adding batches.', variant: 'destructive' })
    return
  }
  isSavingBatch.value = true
  try {
    const result = await createBatch({
      ...batchForm.value,
      course_id:       courseId.value,
      course_name:     detailsForm.value.title,
      instructor_name: '—',
    })
    if (result?.success !== false) {
      batches.value.push(result.batch)
      showBatchDialog.value = false
      toast({ title: 'Batch created', description: `${batchForm.value.name} is ready for enrollment.` })
    }
  } finally {
    isSavingBatch.value = false
  }
}

async function handleDeleteBatch(batch) {
  const result = await deleteBatch(batch.id)
  if (result?.success !== false) {
    batches.value = batches.value.filter(b => b.id !== batch.id)
    toast({ title: 'Batch deleted', description: `${batch.name} has been removed.` })
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const statusConfig = {
  open:      { label: 'Enrolling',   variant: 'default'   },
  active:    { label: 'In Progress', variant: 'secondary' },
  closed:    { label: 'Closed',      variant: 'outline'   },
  completed: { label: 'Completed',   variant: 'outline'   },
  draft:     { label: 'Draft',       variant: 'secondary' },
}


const detailsComplete  = computed(() => !!(detailsForm.value.title && detailsForm.value.description && detailsForm.value.academic_level_id))
const scheduleComplete = computed(() => !!(scheduleForm.value.session_frequency && scheduleForm.value.session_day && scheduleForm.value.session_platform))
const materialsCount   = computed(() => materials.value.length)
const batchCount       = computed(() => batches.value.length)
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
      <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0" @click="router.visit('/dashboard/courses')">
        <ArrowLeft class="h-4 w-4" />
      </Button>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2.5 flex-wrap">
          <h1 class="text-xl font-bold text-foreground tracking-tight truncate">
            {{ isEditMode ? (detailsForm.title || 'Edit Course') : 'New Course' }}
          </h1>
          <Badge v-if="course?.status" :variant="statusConfig[course.status]?.variant" class="text-xs">
            {{ statusConfig[course.status]?.label }}
          </Badge>
          <Badge v-if="hasUnsavedChanges" variant="outline" class="text-xs text-amber-600 border-amber-300">
            Unsaved changes
          </Badge>
        </div>
        <p class="text-xs text-muted-foreground mt-0.5">
          {{ isEditMode ? 'Edit course details, schedule, materials and batches.' : 'Fill in the details below to create your course.' }}
        </p>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <!-- Archive (edit mode only, published) -->
        <Button
          v-if="isEditMode && course?.status === 'published'"
          variant="outline"
          size="sm"
          class="gap-2 hidden sm:flex"
          @click="handleArchive"
        >
          <Archive class="h-4 w-4" />Archive
        </Button>

        <!-- Save draft -->
        <Button
          variant="outline"
          size="sm"
          class="gap-2"
          :disabled="isSaving"
          @click="handleSave"
        >
          <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
          <Save v-else class="h-4 w-4" />
          {{ isSaving ? 'Saving...' : 'Save Draft' }}
        </Button>

        <!-- Publish -->
        <Button
          v-if="!isEditMode || course?.status === 'draft'"
          size="sm"
          class="gap-2"
          :disabled="isPublishing"
          @click="handlePublish"
        >
          <RefreshCw v-if="isPublishing" class="h-4 w-4 animate-spin" />
          <Globe v-else class="h-4 w-4" />
          {{ isPublishing ? 'Publishing...' : 'Publish' }}
        </Button>
      </div>
    </div>

    <!-- ── Progress stepper (visual only) ────────────────────────────────── -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1">
      <template v-for="(step, i) in [
        { key:'details',   label:'1. Details',   done: detailsComplete  },
        { key:'schedule',  label:'2. Schedule',  done: scheduleComplete },
        { key:'materials', label:'3. Materials', done: materialsCount > 0 },
        { key:'batches',   label:'4. Batches',   done: batchCount > 0   },
      ]" :key="step.key">
        <button
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all shrink-0"
          :class="activeTab === step.key
            ? 'bg-primary text-primary-foreground shadow-sm'
            : step.done
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
              : 'bg-muted text-muted-foreground hover:bg-muted/80'"
          @click="activeTab = step.key"
        >
          <CheckCircle2 v-if="step.done && activeTab !== step.key" class="h-3 w-3" />
          {{ step.label }}
        </button>
        <div v-if="i < 3" class="h-px w-4 bg-border shrink-0" />
      </template>
    </div>

    <!-- ── Tabs ───────────────────────────────────────────────────────────── -->
    <Tabs v-model="activeTab">
      <TabsList class="hidden">
        <TabsTrigger value="details"   />
        <TabsTrigger value="schedule"  />
        <TabsTrigger value="materials" />
        <TabsTrigger value="batches"   />
      </TabsList>

      <!-- ══════════════════════════════════════════════════════
           TAB 1 — DETAILS
      ══════════════════════════════════════════════════════════ -->
      <TabsContent value="details" class="mt-0">
        <Card>
          <CardHeader>
            <CardTitle class="text-base flex items-center gap-2">
              <BookOpen class="h-4 w-4 text-primary" />
              Course Details
            </CardTitle>
            <CardDescription class="text-xs">
              Basic information students see on the course listing page.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-5">

            <!-- Title -->
            <div class="space-y-1.5">
              <Label for="title">Course Title <span class="text-destructive">*</span></Label>
              <Input
                id="title"
                v-model="detailsForm.title"
                placeholder="e.g., Primary 4 Mathematics"
                maxlength="150"
                :class="detailsErrors.title && 'border-destructive'"
              />
              <div class="flex items-center justify-between">
                <p v-if="detailsErrors.title" class="text-xs text-destructive">{{ detailsErrors.title }}</p>
                <p class="text-xs text-muted-foreground ml-auto">{{ detailsForm.title.length }}/150</p>
              </div>
            </div>

            <!-- Course Code -->
            <div class="space-y-1.5">
              <Label for="course-code">Course Code <span class="text-destructive">*</span></Label>
              <Input
                id="course-code"
                v-model="detailsForm.course_code"
                placeholder="e.g., PRI-4-MATH-001"
                maxlength="20"
                :class="detailsErrors.course_code && 'border-destructive'"
              />
              <div class="flex items-center justify-between">
                <p v-if="detailsErrors.course_code" class="text-xs text-destructive">{{ detailsErrors.course_code }}</p>
                <p class="text-xs text-muted-foreground ml-auto">{{ detailsForm.course_code.length }}/20</p>
              </div>
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
              <Label for="description">Description <span class="text-destructive">*</span></Label>
              <Textarea
                id="description"
                v-model="detailsForm.description"
                placeholder="Describe what students will learn, who the course is for, and what's included..."
                :rows="4"
                :class="detailsErrors.description && 'border-destructive'"
              />
              <p v-if="detailsErrors.description" class="text-xs text-destructive">{{ detailsErrors.description }}</p>
              <p class="text-xs text-muted-foreground">Shown on the public course listing page.</p>
            </div>

            <!-- Academic Level + Duration -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label>Academic Level <span class="text-destructive">*</span></Label>
                <Select v-model="detailsForm.academic_level_id">
                  <SelectTrigger :class="detailsErrors.academic_level_id && 'border-destructive'">
                    <SelectValue placeholder="Select level" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="lvl in props.academicLevels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="detailsErrors.academic_level_id" class="text-xs text-destructive">{{ detailsErrors.academic_level_id }}</p>
              </div>

              <div class="space-y-1.5">
                <Label for="duration">Duration (Weeks) <span class="text-destructive">*</span></Label>
                <Input
                  id="duration"
                  v-model.number="detailsForm.duration_weeks"
                  type="number"
                  min="1"
                  max="104"
                  :class="detailsErrors.duration_weeks && 'border-destructive'"
                />
                <p v-if="detailsErrors.duration_weeks" class="text-xs text-destructive">{{ detailsErrors.duration_weeks }}</p>
              </div>
            </div>

            <!-- Price -->
            <div class="space-y-1.5">
              <Label for="price">Price Per Student (₦)</Label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">₦</span>
                <Input
                  id="price"
                  v-model.number="detailsForm.price"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="pl-7"
                  :class="detailsErrors.price && 'border-destructive'"
                />
              </div>
              <p v-if="detailsErrors.price" class="text-xs text-destructive">{{ detailsErrors.price }}</p>
            </div>

          </CardContent>
        </Card>

        <div class="flex justify-end mt-4">
          <Button class="gap-2" @click="() => { if (validateDetails()) activeTab = 'schedule' }">
            Next: Schedule
            <ArrowLeft class="h-4 w-4 rotate-180" />
          </Button>
        </div>
      </TabsContent>

      <!-- ══════════════════════════════════════════════════════
           TAB 2 — SCHEDULE
      ══════════════════════════════════════════════════════════ -->
      <TabsContent value="schedule" class="mt-0">
        <Card>
          <CardHeader>
            <CardTitle class="text-base flex items-center gap-2">
              <Calendar class="h-4 w-4 text-primary" />
              Live Session Schedule
            </CardTitle>
            <CardDescription class="text-xs">
              Define the default schedule for all batches of this course. Batches can override this individually.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-5">

            <!-- Frequency + Day -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label>Session Frequency <span class="text-destructive">*</span></Label>
                <Select v-model="scheduleForm.session_frequency">
                  <SelectTrigger :class="scheduleErrors.session_frequency && 'border-destructive'">
                    <SelectValue placeholder="How often?" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="f in SESSION_FREQUENCIES" :key="f.value" :value="f.value">
                      {{ f.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="scheduleErrors.session_frequency" class="text-xs text-destructive">{{ scheduleErrors.session_frequency }}</p>
              </div>

              <div class="space-y-1.5">
                <Label>Class Days <span class="text-destructive">*</span></Label>
                <Select v-model="scheduleForm.session_day">
                  <SelectTrigger :class="scheduleErrors.session_day && 'border-destructive'">
                    <SelectValue placeholder="Which days?" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="d in SCHEDULE_DAYS" :key="d" :value="d">{{ d }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="scheduleErrors.session_day" class="text-xs text-destructive">{{ scheduleErrors.session_day }}</p>
              </div>
            </div>

            <!-- Time + Duration -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="time">Class Start Time <span class="text-destructive">*</span></Label>
                <Input id="time" v-model="scheduleForm.session_time" type="time" />
                <p class="text-xs text-muted-foreground">All times in your school's timezone (WAT/UTC+1).</p>
              </div>

              <div class="space-y-1.5">
                <Label for="session-dur">Session Duration (minutes) <span class="text-destructive">*</span></Label>
                <Input
                  id="session-dur"
                  v-model.number="scheduleForm.session_duration_minutes"
                  type="number"
                  min="15"
                  max="480"
                  :class="scheduleErrors.session_duration_minutes && 'border-destructive'"
                />
                <p v-if="scheduleErrors.session_duration_minutes" class="text-xs text-destructive">{{ scheduleErrors.session_duration_minutes }}</p>
              </div>
            </div>

            <!-- Platform -->
            <div class="space-y-1.5">
              <Label>Live Class Platform <span class="text-destructive">*</span></Label>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <button
                  v-for="p in SESSION_PLATFORMS"
                  :key="p.value"
                  type="button"
                  class="flex items-center gap-2.5 rounded-lg border p-3 text-left text-sm font-medium transition-all"
                  :class="scheduleForm.session_platform === p.value
                    ? 'border-primary bg-primary/5 text-primary'
                    : 'border-border bg-card text-foreground hover:border-primary/40 hover:bg-muted/50'"
                  @click="scheduleForm.session_platform = p.value"
                >
                  <Video class="h-4 w-4 shrink-0" />
                  {{ p.label }}
                </button>
              </div>
              <p v-if="scheduleErrors.session_platform" class="text-xs text-destructive">{{ scheduleErrors.session_platform }}</p>
              <div class="flex items-start gap-2 rounded-lg bg-muted/50 border border-border p-3">
                <Info class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <p class="text-xs text-muted-foreground">
                  Meeting links are created manually by the instructor before each class. Students will find the link in their dashboard and WhatsApp group.
                </p>
              </div>
            </div>

            <!-- Preview card -->
            <div class="rounded-lg bg-muted/30 border border-border p-4">
              <p class="text-xs font-semibold text-foreground mb-2 uppercase tracking-wide">Schedule Preview</p>
              <p class="text-sm text-foreground">
                {{ scheduleForm.session_day || '—' }} · {{ scheduleForm.session_time ? formatTime(scheduleForm.session_time) : '—' }}
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">
                {{ scheduleForm.session_duration_minutes }} min · {{ platformLabel(scheduleForm.session_platform) || '—' }}
                · {{ frequencyLabel(scheduleForm.session_frequency) || '—' }}
              </p>
            </div>

          </CardContent>
        </Card>

        <div class="flex items-center justify-between mt-4">
          <Button variant="outline" class="gap-2" @click="activeTab = 'details'">
            <ArrowLeft class="h-4 w-4" />Back
          </Button>
          <Button class="gap-2" @click="() => { if (validateSchedule()) activeTab = 'materials' }">
            Next: Materials<ArrowLeft class="h-4 w-4 rotate-180" />
          </Button>
        </div>
      </TabsContent>

      <!-- ══════════════════════════════════════════════════════
           TAB 3 — MATERIALS
      ══════════════════════════════════════════════════════════ -->
      <TabsContent value="materials" class="mt-0">
        <div class="space-y-4">

          <!-- Drop zone -->
          <div
            class="rounded-xl border-2 border-dashed transition-colors p-8 text-center cursor-pointer"
            :class="isDragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/40 hover:bg-muted/30'"
            @dragover.prevent="isDragOver = true"
            @dragleave="isDragOver = false"
            @drop="handleDrop"
            @click="openAddMaterial('pdf')"
          >
            <Upload class="h-8 w-8 text-muted-foreground/60 mx-auto mb-2" />
            <p class="text-sm font-medium text-foreground">Drop a PDF here or click to upload</p>
            <p class="text-xs text-muted-foreground mt-1">PDF, DOCX, PPTX up to 50 MB</p>
          </div>

          <!-- Add buttons row -->
          <div class="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('pdf')">
              <FileText class="h-4 w-4" />Upload File
            </Button>
            <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('link')">
              <Link2 class="h-4 w-4" />Add Link
            </Button>
            <Button variant="outline" size="sm" class="gap-2" @click="openAddMaterial('video')">
              <Video class="h-4 w-4" />Add Video
            </Button>
          </div>

          <!-- Materials grouped by module -->
          <div v-if="groupedMaterials.length === 0" class="py-10 text-center rounded-xl border border-dashed border-border">
            <FileText class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
            <p class="text-sm text-muted-foreground">No materials yet. Upload your first resource above.</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="[module, mats] in groupedMaterials" :key="module">
              <!-- Module header -->
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-semibold text-foreground uppercase tracking-wide">{{ module }}</span>
                <div class="flex-1 h-px bg-border" />
                <Badge variant="outline" class="text-[10px]">{{ mats.length }} file{{ mats.length !== 1 ? 's' : '' }}</Badge>
              </div>

              <!-- Material rows -->
              <div class="space-y-2">
                <div
                  v-for="mat in mats"
                  :key="mat.id"
                  class="group flex items-center gap-3 rounded-lg border border-border bg-card p-3 hover:bg-muted/40 transition-colors"
                >
                  <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', matTypeColor(mat.type)]">
                    <component :is="matTypeIcon(mat.type)" class="h-4 w-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground truncate">{{ mat.title }}</p>
                    <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                      <span class="capitalize">{{ mat.type }}</span>
                      <span v-if="mat.size_kb">{{ formatSize(mat.size_kb) }}</span>
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
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7 text-destructive hover:text-destructive"
                      @click="handleDeleteMaterial(mat)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between mt-4">
          <Button variant="outline" class="gap-2" @click="activeTab = 'schedule'">
            <ArrowLeft class="h-4 w-4" />Back
          </Button>
          <Button class="gap-2" @click="activeTab = 'batches'">
            Next: Batches<ArrowLeft class="h-4 w-4 rotate-180" />
          </Button>
        </div>
      </TabsContent>

      <!-- ══════════════════════════════════════════════════════
           TAB 4 — BATCHES
      ══════════════════════════════════════════════════════════ -->
      <TabsContent value="batches" class="mt-0">
        <div class="space-y-4">

          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-foreground">Batches for this course</p>
              <p class="text-xs text-muted-foreground mt-0.5">Each batch is an independent cohort students enroll into.</p>
            </div>
            <Button size="sm" class="gap-2" @click="openAddBatch">
              <Plus class="h-4 w-4" />Add Batch
            </Button>
          </div>

          <!-- No batches yet -->
          <div v-if="batches.length === 0" class="py-12 text-center rounded-xl border border-dashed border-border">
            <Users class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
            <p class="text-sm font-medium text-foreground">No batches yet</p>
            <p class="text-xs text-muted-foreground mt-1">
              {{ isEditMode ? 'Create a batch so students can enroll.' : 'Save the course first, then create batches.' }}
            </p>
          </div>

          <!-- Batch cards -->
          <div class="space-y-3">
            <div
              v-for="batch in batches"
              :key="batch.id"
              class="flex items-center gap-4 rounded-xl border border-border bg-card p-4 hover:bg-muted/30 transition-colors"
            >
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-semibold text-foreground">{{ batch.name }}</p>
                  <Badge :variant="statusConfig[batch.status]?.variant ?? 'outline'" class="text-xs">
                    {{ statusConfig[batch.status]?.label ?? batch.status }}
                  </Badge>
                </div>
                <div class="flex items-center gap-4 text-xs text-muted-foreground mt-1">
                  <span class="flex items-center gap-1">
                    <Calendar class="h-3 w-3" />{{ fmtDate(batch.start_date) }}
                  </span>
                  <span class="flex items-center gap-1">
                    <Users class="h-3 w-3" />
                    {{ batch.current_enrollment }}/{{ batch.max_students }}
                  </span>
                  <span>{{ fmtNaira(batch.price_per_student) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <Button
                  variant="outline"
                  size="sm"
                  class="text-xs h-8"
                  @click="router.visit(`/dashboard/batches/${batch.id}`)"
                >
                  <Eye class="mr-1.5 h-3.5 w-3.5" />View
                </Button>
                <Button
                  v-if="batch.current_enrollment === 0"
                  variant="ghost"
                  size="icon"
                  class="h-8 w-8 text-destructive hover:text-destructive"
                  @click="handleDeleteBatch(batch)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>

          <!-- Publish CTA -->
          <div
            v-if="course?.status === 'draft' && batches.length > 0"
            class="rounded-xl bg-primary/5 border border-primary/20 p-4 flex items-center gap-4"
          >
            <CheckCircle2 class="h-6 w-6 text-primary shrink-0" />
            <div class="flex-1">
              <p class="text-sm font-semibold text-foreground">Ready to go live?</p>
              <p class="text-xs text-muted-foreground">You have {{ batches.length }} batch{{ batches.length > 1 ? 'es' : '' }} ready. Publish the course so students can enroll.</p>
            </div>
            <Button size="sm" class="gap-2 shrink-0" :disabled="isPublishing" @click="handlePublish">
              <Globe class="h-4 w-4" />Publish
            </Button>
          </div>

        </div>

        <div class="flex items-center justify-between mt-4">
          <Button variant="outline" class="gap-2" @click="activeTab = 'materials'">
            <ArrowLeft class="h-4 w-4" />Back
          </Button>
          <Button class="gap-2" :disabled="isSaving" @click="handleSave">
            <Save class="h-4 w-4" />Save All Changes
          </Button>
        </div>
      </TabsContent>

    </Tabs>

    <!-- ══════════════════════════════════════════════════════════════════════
         ADD MATERIAL DIALOG
    ═══════════════════════════════════════════════════════════════════════ -->
    <Dialog :open="showAddMaterialDialog" @update:open="showAddMaterialDialog = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <component
              :is="materialType === 'link' ? Link2 : materialType === 'video' ? Video : FileText"
              class="h-5 w-5 text-primary"
            />
            {{ materialType === 'link' ? 'Add Link' : materialType === 'video' ? 'Add Video' : 'Upload File' }}
          </DialogTitle>
          <DialogDescription class="text-xs">
            {{ materialType === 'pdf' ? 'Upload a PDF, DOCX or PPTX file for students to download.' : 'Add a URL students can access.' }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">

          <!-- Title -->
          <div class="space-y-1.5">
            <Label>Material Title <span class="text-destructive">*</span></Label>
            <Input
              v-model="materialForm.title"
              placeholder="e.g., Week 1 – Introduction Notes"
              :class="materialErrors.title && 'border-destructive'"
            />
            <p v-if="materialErrors.title" class="text-xs text-destructive">{{ materialErrors.title }}</p>
          </div>

          <!-- File input (pdf mode) -->
          <div v-if="materialType === 'pdf'" class="space-y-1.5">
            <Label>File <span class="text-destructive">*</span></Label>
            <div
              class="flex flex-col items-center gap-2 rounded-lg border-2 border-dashed border-border p-4 text-center hover:border-primary/40 transition-colors cursor-pointer"
              @click="$refs.fileInput.click()"
            >
              <Upload class="h-5 w-5 text-muted-foreground" />
              <span class="text-xs text-muted-foreground">
                {{ materialForm.file ? materialForm.file.name : 'Click to select file' }}
              </span>
            </div>
            <input ref="fileInput" type="file" accept=".pdf,.doc,.docx,.pptx" class="hidden" @change="onFileSelect" />
            <p v-if="materialErrors.file" class="text-xs text-destructive">{{ materialErrors.file }}</p>

            <!-- Upload progress -->
            <div v-if="isUploading" class="space-y-1">
              <Progress :value="uploadProgress" class="h-1.5" />
              <p class="text-xs text-muted-foreground text-right">{{ uploadProgress }}%</p>
            </div>
          </div>

          <!-- URL input (link/video mode) -->
          <div v-else class="space-y-1.5">
            <Label>URL <span class="text-destructive">*</span></Label>
            <Input
              v-model="materialForm.url"
              :placeholder="materialType === 'video' ? 'https://youtube.com/...' : 'https://...'"
              type="url"
              :class="materialErrors.url && 'border-destructive'"
            />
            <p v-if="materialErrors.url" class="text-xs text-destructive">{{ materialErrors.url }}</p>
          </div>

          <!-- Module / Week -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label>Module Label</Label>
              <Input v-model="materialForm.module" placeholder="Week 1" />
            </div>
            <div class="space-y-1.5">
              <Label>Week Number</Label>
              <Select v-model.number="materialForm.week">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="w in weekOptions" :key="w" :value="w">Week {{ w }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

        </div>

        <DialogFooter>
          <Button variant="outline" :disabled="isUploading" @click="showAddMaterialDialog = false">Cancel</Button>
          <Button :disabled="isUploading" @click="handleAddMaterial">
            <RefreshCw v-if="isUploading" class="mr-2 h-4 w-4 animate-spin" />
            {{ isUploading ? 'Uploading...' : materialType === 'pdf' ? 'Upload' : 'Add' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- ══════════════════════════════════════════════════════════════════════
         ADD BATCH DIALOG (inline — from the Batches tab)
    ═══════════════════════════════════════════════════════════════════════ -->
    <Dialog :open="showBatchDialog" @update:open="showBatchDialog = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Users class="h-5 w-5 text-primary" />New Batch
          </DialogTitle>
          <DialogDescription class="text-xs">
            Create a new cohort for {{ detailsForm.title || 'this course' }}.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">

          <div class="space-y-1.5">
            <Label>Batch Name <span class="text-destructive">*</span></Label>
            <Input
              v-model="batchForm.name"
              placeholder="e.g., March 2026 Batch"
              :class="batchErrors.name && 'border-destructive'"
            />
            <p v-if="batchErrors.name" class="text-xs text-destructive">{{ batchErrors.name }}</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label>Start Date <span class="text-destructive">*</span></Label>
              <Input v-model="batchForm.start_date" type="date" :class="batchErrors.start_date && 'border-destructive'" />
              <p v-if="batchErrors.start_date" class="text-xs text-destructive">{{ batchErrors.start_date }}</p>
            </div>
            <div class="space-y-1.5">
              <Label>End Date <span class="text-destructive">*</span></Label>
              <Input v-model="batchForm.end_date" type="date" :min="batchForm.start_date || undefined" :class="batchErrors.end_date && 'border-destructive'" />
              <p v-if="batchErrors.end_date" class="text-xs text-destructive">{{ batchErrors.end_date }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label>Max Students <span class="text-destructive">*</span></Label>
              <Input v-model.number="batchForm.max_students" type="number" min="1" :class="batchErrors.max_students && 'border-destructive'" />
            </div>
            <div class="space-y-1.5">
              <Label>Price (₦)</Label>
              <Input v-model.number="batchForm.price_per_student" type="number" min="0" />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label>WhatsApp Group Link (Optional)</Label>
            <Input v-model="batchForm.whatsapp_link" placeholder="https://chat.whatsapp.com/..." />
          </div>

        </div>

        <DialogFooter>
          <Button variant="outline" :disabled="isSavingBatch" @click="showBatchDialog = false">Cancel</Button>
          <Button :disabled="isSavingBatch" @click="handleSaveBatch">
            <RefreshCw v-if="isSavingBatch" class="mr-2 h-4 w-4 animate-spin" />
            {{ isSavingBatch ? 'Creating...' : 'Create Batch' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
</template>