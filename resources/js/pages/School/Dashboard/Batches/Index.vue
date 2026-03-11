<script setup>
import { ref } from 'vue'
import {  router } from '@inertiajs/vue3'
import {
  Plus, Search, MoreVertical, Eye, Edit, Trash2,
  Users, Calendar, Clock, MessageCircle, TrendingUp,
  AlertCircle, CheckCircle2, RefreshCw,
   Lock, Unlock,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  Tabs, TabsList, TabsTrigger,
} from '@/components/ui/tabs'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog, DialogContent, DialogDescription,
  DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import {
  Select, SelectContent, SelectItem,
  SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel,
  AlertDialogContent, AlertDialogDescription,
  AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from "vue-sonner"
import { useDashboardBatches }  from '@/composables/useDashboardBatches'
import { useInstructors }       from '@/composables/useInstructors'
import { useCourses }           from '@/composables/useCourses'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

/**
 * Composables
 */
const {
  filteredBatches, isLoading, error,
  search, filterStatus, statusCounts,
  enrollmentPct, isFull, formatTime, formatNaira,
  createBatch, updateBatch, deleteBatch, toggleEnrollment,
} = useDashboardBatches()

const { instructorOptions } = useInstructors()
const { publishedCourses }  = useCourses()


// ─── Status UI config ─────────────────────────────────────────────────────────
const statusConfig = {
  open:      { label: 'Enrolling',    variant: 'default',     icon: CheckCircle2, color: 'text-primary' },
  active:    { label: 'In Progress',  variant: 'secondary',   icon: TrendingUp,   color: 'text-secondary' },
  closed:    { label: 'Closed',       variant: 'outline',     icon: Lock,         color: 'text-muted-foreground' },
  completed: { label: 'Completed',    variant: 'outline',     icon: CheckCircle2, color: 'text-emerald-600' },
}

// ─── Progress color based on fill % ──────────────────────────────────────────
function progressColor(pct) {
  if (pct >= 90) return '[&>div]:bg-red-500'
  if (pct >= 70) return '[&>div]:bg-amber-500'
  return '[&>div]:bg-primary'
}

// ─── Initials helper ──────────────────────────────────────────────────────────
function initials(name) {
  return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// ─── Date formatting (no date-fns dependency for this page) ──────────────────
function fmtDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-NG', {
    day:   'numeric',
    month: 'short',
    year:  'numeric',
  })
}

// ──────────────────────────────────────────────────────────────────────────────
// CREATE / EDIT DIALOG
// ──────────────────────────────────────────────────────────────────────────────
const showDialog      = ref(false)
const isEditMode      = ref(false)
const editingBatchId  = ref(null)
const isSubmitting    = ref(false)

const defaultForm = () => ({
  name:           '',
  course_id:      '',
  instructor_id:  '',
  start_date:     '',
  end_date:       '',
  max_students:   30,
  price_per_student: 0,
  schedule_day:   '',
  schedule_time:  '',
  whatsapp_link:  '',
  description:    '',
  status:         'open',
})

const form = ref(defaultForm())
const formErrors = ref({})

function openCreate() {
  isEditMode.value    = false
  editingBatchId.value = null
  form.value          = defaultForm()
  formErrors.value    = {}
  showDialog.value    = true
}

function openEdit(batch) {
  isEditMode.value     = true
  editingBatchId.value = batch.id
  form.value = {
    name:              batch.name,
    course_id:         batch.course_id,
    instructor_id:     batch.instructor_id ?? '',
    start_date:        batch.start_date,
    end_date:          batch.end_date,
    max_students:      batch.max_students,
    price_per_student: batch.price_per_student,
    schedule_day:      batch.schedule_day ?? '',
    schedule_time:     batch.schedule_time ?? '',
    whatsapp_link:     batch.whatsapp_link ?? '',
    description:       batch.description ?? '',
    status:            batch.status,
  }
  formErrors.value  = {}
  showDialog.value  = true
}

// ─── Client-side validation (mirrors Laravel FormRequest rules) ───────────────
function validateForm() {
  const errs = {}
  if (!form.value.name.trim())      errs.name      = 'Batch name is required'
  if (!form.value.course_id)        errs.course_id = 'Course is required'
  if (!form.value.start_date)       errs.start_date = 'Start date is required'
  if (!form.value.end_date)         errs.end_date   = 'End date is required'
  if (form.value.start_date && form.value.end_date &&
      form.value.start_date >= form.value.end_date) {
    errs.end_date = 'End date must be after start date'
  }
  if (form.value.max_students < 1)  errs.max_students = 'Must allow at least 1 student'

  // WhatsApp link validation
  if (form.value.whatsapp_link && !form.value.whatsapp_link.includes('chat.whatsapp.com')) {
    errs.whatsapp_link = 'Must be a valid WhatsApp group link (chat.whatsapp.com/...)'
  }

  formErrors.value = errs
  return Object.keys(errs).length === 0
}

async function handleSubmit() {
  if (!validateForm()) return
  isSubmitting.value = true
  try {
    const result = isEditMode.value
      ? await updateBatch(editingBatchId.value, form.value)
      : await createBatch({
          ...form.value,
          course_name:     publishedCourses.value.find(c => c.id === form.value.course_id)?.title ?? '',
          instructor_name: instructorOptions.value.find(i => i.id === form.value.instructor_id)?.name ?? '',
        })

    if (result?.success !== false) {
      toast({
        title:       isEditMode.value ? 'Batch updated' : 'Batch created',
        description: `${form.value.name} has been ${isEditMode.value ? 'updated' : 'created'} successfully.`,
      })
      showDialog.value = false
    } else {
      toast({ title: 'Error', description: error.value ?? 'Something went wrong.', variant: 'destructive' })
    }
  } finally {
    isSubmitting.value = false
  }
}

// ──────────────────────────────────────────────────────────────────────────────
// DELETE DIALOG
// ──────────────────────────────────────────────────────────────────────────────
const showDeleteDialog  = ref(false)
const deletingBatch     = ref(null)
const isDeleting        = ref(false)

function confirmDelete(batch) {
  deletingBatch.value    = batch
  showDeleteDialog.value = true
}

async function handleDelete() {
  if (!deletingBatch.value) return
  isDeleting.value = true
  try {
    const result = await deleteBatch(deletingBatch.value.id)
    if (result?.success !== false) {
      toast({ title: 'Batch deleted', description: `${deletingBatch.value.name} has been removed.` })
      showDeleteDialog.value = false
      deletingBatch.value    = null
    }
  } finally {
    isDeleting.value = false
  }
}

// ──────────────────────────────────────────────────────────────────────────────
// TOGGLE ENROLLMENT (open ↔ closed)
// ──────────────────────────────────────────────────────────────────────────────
async function handleToggleEnrollment(batch) {
  const open = batch.status === 'closed'
  await toggleEnrollment(batch.id, open)
  toast({
    title: open ? 'Enrollment opened' : 'Enrollment closed',
    description: `${batch.name} is now ${open ? 'accepting enrollments' : 'closed'}.`,
  })
}

// ──────────────────────────────────────────────────────────────────────────────
// NAVIGATE TO BATCH DETAIL
// ──────────────────────────────────────────────────────────────────────────────
function viewBatch(batch) {
  /**
   * TODO (Laravel 12): This navigates to BatchDetailPage
   * Route: dashboard.batches.show
   * web.php: Route::get('/batches/{batch}', [...])
   */
  router.visit(`/dashboard/batches/${batch.id}`)
}

// ─── Schedule days options ────────────────────────────────────────────────────
const scheduleDays = [
  'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
  'Mon/Wed', 'Tue/Thu', 'Mon/Wed/Fri', 'Sat/Sun',
]
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- ── Page Header ───────────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Batches</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Manage all your cohorts — open, active, and completed.
        </p>
      </div>
      <Button class="gap-2 shrink-0" @click="openCreate">
        <Plus class="h-4 w-4" />
        Create Batch
      </Button>
    </div>

    <!-- ── Status Tabs ───────────────────────────────────────────────────── -->
    <Tabs :model-value="filterStatus" @update:model-value="filterStatus = $event">
      <TabsList class="h-auto flex-wrap">
        <TabsTrigger value="all" class="gap-2">
          All
          <Badge variant="secondary" class="h-5 px-1.5 text-xs">{{ statusCounts.all }}</Badge>
        </TabsTrigger>
        <TabsTrigger value="open" class="gap-2">
          Enrolling
          <Badge variant="default" class="h-5 px-1.5 text-xs">{{ statusCounts.open }}</Badge>
        </TabsTrigger>
        <TabsTrigger value="active" class="gap-2">
          In Progress
          <Badge variant="secondary" class="h-5 px-1.5 text-xs">{{ statusCounts.active }}</Badge>
        </TabsTrigger>
        <TabsTrigger value="completed" class="gap-2">
          Completed
          <Badge variant="outline" class="h-5 px-1.5 text-xs">{{ statusCounts.completed }}</Badge>
        </TabsTrigger>
      </TabsList>
    </Tabs>

    <!-- ── Search + Filter Bar ──────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="search"
          placeholder="Search batches, courses, instructors..."
          class="pl-9"
        />
      </div>
    </div>

    <!-- ── Error Banner ──────────────────────────────────────────────────── -->
    <div
      v-if="error"
      class="flex items-center gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3"
    >
      <AlertCircle class="h-4 w-4 text-destructive shrink-0" />
      <p class="text-sm text-destructive">{{ error }}</p>
    </div>

    <!-- ── Batch Grid ─────────────────────────────────────────────────────── -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

      <!-- Loading skeletons -->
      <template v-if="isLoading">
        <Card v-for="i in 6" :key="i" class="animate-pulse">
          <CardContent class="p-5 space-y-3">
            <div class="h-4 bg-muted rounded w-3/4" />
            <div class="h-3 bg-muted rounded w-1/2" />
            <div class="h-2 bg-muted rounded w-full mt-4" />
          </CardContent>
        </Card>
      </template>

      <!-- Empty state -->
      <div
        v-else-if="filteredBatches.length === 0"
        class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center"
      >
        <Users class="h-10 w-10 text-muted-foreground/40 mb-3" />
        <p class="text-sm font-medium text-foreground">No batches found</p>
        <p class="text-xs text-muted-foreground mt-1">
          {{ search ? 'Try a different search term.' : 'Create your first batch to get started.' }}
        </p>
        <Button class="mt-4 gap-2" size="sm" @click="openCreate">
          <Plus class="h-4 w-4" />Create Batch
        </Button>
      </div>

      <!-- Batch cards -->
      <Card
        v-for="batch in filteredBatches"
        :key="batch.id"
        class="group transition-all duration-200 hover:shadow-md hover:border-primary/30 cursor-pointer"
        @click="viewBatch(batch)"
      >
        <CardContent class="p-5">

          <!-- Header row -->
          <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-foreground truncate text-sm group-hover:text-primary transition-colors">
                {{ batch.name }}
              </h3>
              <p class="text-xs text-muted-foreground truncate mt-0.5">{{ batch.course_name }}</p>
            </div>

            <!-- Actions dropdown (stop propagation so click doesn't navigate) -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                  @click.stop
                >
                  <MoreVertical class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" @click.stop>
                <DropdownMenuItem @click.stop="viewBatch(batch)">
                  <Eye class="mr-2 h-4 w-4" />View Details
                </DropdownMenuItem>
                <DropdownMenuItem @click.stop="openEdit(batch)">
                  <Edit class="mr-2 h-4 w-4" />Edit Batch
                </DropdownMenuItem>
                <DropdownMenuItem
                  v-if="batch.status === 'open' || batch.status === 'closed'"
                  @click.stop="handleToggleEnrollment(batch)"
                >
                  <component :is="batch.status === 'open' ? Lock : Unlock" class="mr-2 h-4 w-4" />
                  {{ batch.status === 'open' ? 'Close Enrollment' : 'Open Enrollment' }}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  class="text-destructive focus:text-destructive"
                  @click.stop="confirmDelete(batch)"
                >
                  <Trash2 class="mr-2 h-4 w-4" />Delete Batch
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <!-- Status badge -->
          <div class="flex items-center gap-2 mb-4">
            <Badge :variant="statusConfig[batch.status]?.variant ?? 'outline'" class="text-xs gap-1">
              <component :is="statusConfig[batch.status]?.icon" class="h-3 w-3" />
              {{ statusConfig[batch.status]?.label ?? batch.status }}
            </Badge>
            <Badge v-if="isFull(batch)" variant="destructive" class="text-xs">Full</Badge>
          </div>

          <!-- Date range -->
          <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2">
            <Calendar class="h-3.5 w-3.5 shrink-0" />
            <span>{{ fmtDate(batch.start_date) }} → {{ fmtDate(batch.end_date) }}</span>
          </div>

          <!-- Schedule -->
          <div v-if="batch.schedule_day" class="flex items-center gap-2 text-xs text-muted-foreground mb-3">
            <Clock class="h-3.5 w-3.5 shrink-0" />
            <span>{{ batch.schedule_day }} · {{ formatTime(batch.schedule_time) }}</span>
          </div>

          <!-- Instructor -->
          <div class="flex items-center gap-2 mb-4">
            <Avatar class="h-5 w-5">
              <AvatarImage v-if="batch.instructor_avatar" :src="batch.instructor_avatar" />
              <AvatarFallback class="text-[8px] bg-primary/10 text-primary font-bold">
                {{ initials(batch.instructor_name) }}
              </AvatarFallback>
            </Avatar>
            <span class="text-xs text-muted-foreground truncate">{{ batch.instructor_name }}</span>
          </div>

          <!-- Enrollment progress -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-1 text-muted-foreground">
                <Users class="h-3.5 w-3.5" />
                <span>Students</span>
              </div>
              <span class="font-semibold" :class="isFull(batch) ? 'text-destructive' : 'text-foreground'">
                {{ batch.current_enrollment }}/{{ batch.max_students }}
              </span>
            </div>
            <Progress
              :value="enrollmentPct(batch)"
              :class="['h-1.5', progressColor(enrollmentPct(batch))]"
            />
            <p class="text-[10px] text-muted-foreground text-right">
              {{ enrollmentPct(batch) }}% filled
            </p>
          </div>

          <!-- WhatsApp indicator -->
          <div v-if="batch.whatsapp_link" class="flex items-center gap-1.5 mt-3 text-xs text-emerald-600">
            <MessageCircle class="h-3.5 w-3.5" />
            <span>WhatsApp group linked</span>
          </div>

          <!-- Price -->
          <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">
            <span class="text-xs text-muted-foreground">Price per student</span>
            <span class="text-xs font-semibold text-foreground">{{ formatNaira(batch.price_per_student) }}</span>
          </div>

        </CardContent>
      </Card>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         CREATE / EDIT DIALOG
    ═══════════════════════════════════════════════════════════════════════════ -->
    <Dialog :open="showDialog" @update:open="showDialog = $event">
      <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Users class="h-5 w-5 text-primary" />
            {{ isEditMode ? 'Edit Batch' : 'Create New Batch' }}
          </DialogTitle>
          <DialogDescription>
            {{ isEditMode ? 'Update the details for this batch.' : 'Create a new cohort for students to enroll in.' }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">

          <!-- Batch name -->
          <div class="space-y-1.5">
            <Label for="batch-name">Batch Name <span class="text-destructive">*</span></Label>
            <Input
              id="batch-name"
              v-model="form.name"
              placeholder="e.g., January 2026 Batch"
              :class="formErrors.name && 'border-destructive'"
            />
            <p v-if="formErrors.name" class="text-xs text-destructive">{{ formErrors.name }}</p>
          </div>

          <!-- Course -->
          <div class="space-y-1.5">
            <Label>Course <span class="text-destructive">*</span></Label>
            <Select v-model="form.course_id">
              <SelectTrigger :class="formErrors.course_id && 'border-destructive'">
                <SelectValue placeholder="Select a course" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="c in publishedCourses" :key="c.id" :value="c.id">
                  {{ c.title }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="formErrors.course_id" class="text-xs text-destructive">{{ formErrors.course_id }}</p>
          </div>

          <!-- Instructor -->
          <div class="space-y-1.5">
            <Label>Instructor</Label>
            <Select v-model="form.instructor_id">
              <SelectTrigger>
                <SelectValue placeholder="Assign instructor (optional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="i in instructorOptions" :key="i.id" :value="i.id">
                  {{ i.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="start-date">Start Date <span class="text-destructive">*</span></Label>
              <Input
                id="start-date"
                v-model="form.start_date"
                type="date"
                :class="formErrors.start_date && 'border-destructive'"
              />
              <p v-if="formErrors.start_date" class="text-xs text-destructive">{{ formErrors.start_date }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="end-date">End Date <span class="text-destructive">*</span></Label>
              <Input
                id="end-date"
                v-model="form.end_date"
                type="date"
                :min="form.start_date || undefined"
                :class="formErrors.end_date && 'border-destructive'"
              />
              <p v-if="formErrors.end_date" class="text-xs text-destructive">{{ formErrors.end_date }}</p>
            </div>
          </div>

          <!-- Schedule -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label>Schedule Day</Label>
              <Select v-model="form.schedule_day">
                <SelectTrigger>
                  <SelectValue placeholder="Select days" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="d in scheduleDays" :key="d" :value="d">{{ d }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label for="schedule-time">Class Time</Label>
              <Input id="schedule-time" v-model="form.schedule_time" type="time" />
            </div>
          </div>

          <!-- Max students + price -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="max-students">Max Students <span class="text-destructive">*</span></Label>
              <Input
                id="max-students"
                v-model.number="form.max_students"
                type="number"
                min="1"
                max="500"
                :class="formErrors.max_students && 'border-destructive'"
              />
              <p v-if="formErrors.max_students" class="text-xs text-destructive">{{ formErrors.max_students }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="price">Price per Student (₦)</Label>
              <Input
                id="price"
                v-model.number="form.price_per_student"
                type="number"
                min="0"
                placeholder="0"
              />
            </div>
          </div>

          <!-- WhatsApp link -->
          <div class="space-y-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 p-4">
            <div class="flex items-center gap-2">
              <MessageCircle class="h-4 w-4 text-emerald-600" />
              <Label class="text-emerald-700 dark:text-emerald-400 text-sm font-medium">
                WhatsApp Group Link (Optional)
              </Label>
            </div>
            <Input
              v-model="form.whatsapp_link"
              placeholder="https://chat.whatsapp.com/..."
              class="bg-background"
              :class="formErrors.whatsapp_link && 'border-destructive'"
            />
            <p v-if="formErrors.whatsapp_link" class="text-xs text-destructive">{{ formErrors.whatsapp_link }}</p>
            <p class="text-xs text-muted-foreground">
              Only visible to enrolled students and assigned instructors.
            </p>
          </div>

          <!-- Description -->
          <div class="space-y-1.5">
            <Label for="description">Description (Optional)</Label>
            <Textarea
              id="description"
              v-model="form.description"
              placeholder="Brief description of this batch..."
              :rows="3"
            />
          </div>

        </div>

        <DialogFooter>
          <Button variant="outline" :disabled="isSubmitting" @click="showDialog = false">
            Cancel
          </Button>
          <Button :disabled="isSubmitting" @click="handleSubmit">
            <RefreshCw v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
            {{ isSubmitting ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update Batch' : 'Create Batch') }}
          </Button>
        </DialogFooter>

      </DialogContent>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════════════════════
         DELETE CONFIRM DIALOG
    ═══════════════════════════════════════════════════════════════════════════ -->
    <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete this batch?</AlertDialogTitle>
          <AlertDialogDescription>
            <span class="font-medium text-foreground">{{ deletingBatch?.name }}</span> will be permanently deleted.
            All {{ deletingBatch?.current_enrollment }} enrolled students will be notified.
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel :disabled="isDeleting">Cancel</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="isDeleting"
            @click="handleDelete"
          >
            <RefreshCw v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
            {{ isDeleting ? 'Deleting...' : 'Delete Batch' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

  </div>
  </DashboardLayout>
</template>