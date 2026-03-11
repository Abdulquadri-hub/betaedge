<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeft, Users, Video, PenLine, Upload,
  BookOpen, ExternalLink, TrendingUp, AlertCircle,
  Plus, Trash2, Link2, FileText, Download, RefreshCw,
  CheckCircle2, Clock, ChevronRight,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Badge }    from '@/components/ui/badge'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import { useInstructorDashboard, MOCK_INSTRUCTOR_STUDENTS } from '@/composables/useInstructorDashboard'
import { useLiveSessionsManager } from '@/composables/useLiveSessionsManager'
import CreateSessionDialog from '@/components/Dashboard/School/LiveSessions/CreateSessionDialog.vue'
import AttendanceTracker from '@/components/Dashboard/School/LiveSessions/AttendanceTracker.vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { batches, gradingQueue, formatNaira, formatTime, fmtDate, gradeColor, initials, gradeSubmission } = useInstructorDashboard()
const { sessions, goLive, endSession, createSession } = useLiveSessionsManager()


const activeTab = ref('overview')

// TODO: const props = defineProps({ batchId: String })
const batchId = ref('batch-001')
const batch   = computed(() => batches.value.find(b => b.id === batchId.value))
const batchStudents = computed(() => MOCK_INSTRUCTOR_STUDENTS.filter(s => s.batch_id === batchId.value))
const batchGrading  = computed(() => gradingQueue.value.filter(s => s.batch_id === batchId.value))
const batchSessions = computed(() => sessions.value.filter(s => s.batch_id === batchId.value))

// ── Materials ─────────────────────────────────────────────────────────────────
const materials = ref([
  { id: 'mat-001', title: 'Week 8 – Laravel API Slides', type: 'pdf',  size: '2.4MB', uploads: 24, url: '#', created_at: '2026-03-01' },
  { id: 'mat-002', title: 'Practice Project Starter',    type: 'pdf',  size: '1.1MB', uploads: 22, url: '#', created_at: '2026-03-01' },
  { id: 'mat-003', title: 'Vue 3 Composition API Docs',  type: 'link', size: null,    uploads: 19, url: 'https://vuejs.org/guide/extras/composition-api-faq', created_at: '2026-02-25' },
])

const showMaterialDialog = ref(false)
const matForm = ref({ title: '', type: 'pdf', url: '' })
const isUploadingMat = ref(false)

async function saveMaterial() {
  if (!matForm.value.title.trim()) return
  isUploadingMat.value = true
  await new Promise(r => setTimeout(r, 700))
  // TODO: router.post(route('instructor.batches.materials.store', batchId.value), matForm.value)
  materials.value.push({ id: 'mat-' + Date.now(), ...matForm.value, size: null, uploads: 0, created_at: new Date().toISOString().split('T')[0] })
  isUploadingMat.value = false
  showMaterialDialog.value = false
  toast.success('Material Added', { title: 'Material added' })
}

async function deleteMaterial(id) {
  materials.value = materials.value.filter(m => m.id !== id)
  // TODO: router.delete(route('instructor.batches.materials.destroy', id))
  toast.success({ title: 'Material removed' })
}

// ── Grading ───────────────────────────────────────────────────────────────────
const showGradeSheet  = ref(false)
const activeSubmission = ref(null)
const gradeForm = ref({ score: '', feedback: '' })
const isSavingGrade = ref(false)

function openGrade(sub) {
  activeSubmission.value = sub
  gradeForm.value = { score: sub.score ?? '', feedback: sub.feedback ?? '' }
  showGradeSheet.value = true
}

async function saveGrade() {
  if (gradeForm.value.score === '' || !activeSubmission.value) return
  isSavingGrade.value = true
  try {
    await gradeSubmission(activeSubmission.value.id, Number(gradeForm.value.score), gradeForm.value.feedback)
    toast({ title: 'Grade saved', description: `${activeSubmission.value.student_name} notified.` })
    showGradeSheet.value = false
  } finally {
    isSavingGrade.value = false
  }
}

// ── Sessions ──────────────────────────────────────────────────────────────────
const showCreateSession = ref(false)
const showAttendance    = ref(false)
const attendanceSession = ref(null)

function openAttendance(session) {
  attendanceSession.value = session
  showAttendance.value    = true
}

function timeAgo(iso) {
  const d = Math.floor((Date.now() - new Date(iso)) / 86400000)
  return d === 0 ? 'Today' : `${d}d ago`
}

const matTypeIcon = { pdf: FileText, link: Link2, video: Video }
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button variant="ghost" size="icon" class="h-9 w-9" @click="router.visit('/instructor/batches')">
        <ArrowLeft class="h-4 w-4" />
      </Button>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-bold text-foreground">{{ batch?.name }}</h1>
          <Badge variant="secondary" class="text-xs">Week {{ batch?.week }}/{{ batch?.total_weeks }}</Badge>
        </div>
        <p class="text-sm text-muted-foreground">{{ batch?.course_name }}</p>
      </div>
      <a v-if="batch?.whatsapp_link" :href="batch.whatsapp_link" target="_blank" rel="noopener noreferrer">
        <Button variant="outline" size="sm" class="gap-2 text-xs shrink-0">
          <ExternalLink class="h-3.5 w-3.5" />WhatsApp Group
        </Button>
      </a>
    </div>

    <!-- Quick stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="tile in [
        { label:'Students',    value: batch?.current_enrollment, color:'text-foreground' },
        { label:'Avg Grade',   value: batch?.avg_grade + '%',    color: gradeColor(batch?.avg_grade ?? 0) },
        { label:'Attendance',  value: batch?.attendance_rate + '%', color:'text-foreground' },
        { label:'To Grade',    value: batchGrading.filter(s=>s.status==='submitted').length, color:'text-amber-600' },
      ]" :key="tile.label"
        class="rounded-xl border border-border bg-card p-4"
      >
        <p class="text-xs text-muted-foreground font-medium">{{ tile.label }}</p>
        <p :class="['text-2xl font-bold mt-1', tile.color]">{{ tile.value }}</p>
      </div>
    </div>

    <Tabs v-model="activeTab">
      <TabsList>
        <TabsTrigger value="overview">Overview</TabsTrigger>
        <TabsTrigger value="students">Students</TabsTrigger>
        <TabsTrigger value="sessions">Sessions</TabsTrigger>
        <TabsTrigger value="grading">
          Grading
          <Badge v-if="batchGrading.filter(s=>s.status==='submitted').length" variant="destructive" class="ml-1.5 h-4 px-1 text-[10px]">
            {{ batchGrading.filter(s => s.status === 'submitted').length }}
          </Badge>
        </TabsTrigger>
        <TabsTrigger value="materials">Materials</TabsTrigger>
      </TabsList>

      <!-- Overview tab -->
      <TabsContent value="overview" class="mt-4 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle class="text-sm flex items-center gap-2"><Clock class="h-4 w-4 text-primary" />Schedule</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm pt-0">
              <div class="flex justify-between"><span class="text-muted-foreground">Days</span><span class="font-medium">{{ batch?.schedule_day }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Time</span><span class="font-medium">{{ formatTime(batch?.schedule_time) }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Start</span><span class="font-medium">{{ fmtDate(batch?.start_date) }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">End</span><span class="font-medium">{{ fmtDate(batch?.end_date) }}</span></div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm flex items-center gap-2"><TrendingUp class="h-4 w-4 text-primary" />Progress</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div>
                <div class="flex justify-between text-xs mb-1"><span>Batch progress</span><span class="font-medium">Week {{ batch?.week }}/{{ batch?.total_weeks }}</span></div>
                <Progress :value="((batch?.week ?? 0) / (batch?.total_weeks ?? 12)) * 100" class="h-2" />
              </div>
              <div>
                <div class="flex justify-between text-xs mb-1"><span>Avg grade</span><span :class="['font-medium', gradeColor(batch?.avg_grade ?? 0)]">{{ batch?.avg_grade }}%</span></div>
                <Progress :value="batch?.avg_grade" class="h-2" />
              </div>
              <div>
                <div class="flex justify-between text-xs mb-1"><span>Attendance</span><span class="font-medium">{{ batch?.attendance_rate }}%</span></div>
                <Progress :value="batch?.attendance_rate" class="h-2" />
              </div>
            </CardContent>
          </Card>
        </div>
        <!-- Payment info -->
        <Card class="border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="flex-1">
              <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Payment Agreement</p>
              <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                {{ batch?.payment?.structure === 'per_batch' ? 'Per batch' : batch?.payment?.structure }}:
                <strong>{{ formatNaira(batch?.payment?.amount ?? 0) }}</strong> ·
                Status: <strong :class="batch?.payment?.status === 'paid' ? 'text-emerald-700' : 'text-amber-700'">
                  {{ batch?.payment?.status === 'paid' ? 'Paid ✓' : 'Pending completion' }}
                </strong>
              </p>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Students tab -->
      <TabsContent value="students" class="mt-4 space-y-3">
        <div v-if="!batchStudents.length" class="py-12 text-center text-sm text-muted-foreground">No students in this batch.</div>
        <Card v-for="s in batchStudents" :key="s.id">
          <CardContent class="p-4 flex items-center gap-3">
            <Avatar class="h-9 w-9 shrink-0">
              <AvatarFallback :class="s.grade < 60 ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'" class="text-xs font-bold">{{ initials(s.name) }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-foreground">{{ s.name }}</p>
                <Badge v-if="s.grade < 60 || s.attendance_rate < 60" variant="destructive" class="text-[10px]">Needs help</Badge>
              </div>
              <p class="text-xs text-muted-foreground">{{ s.email }}</p>
              <div v-if="s.parent" class="text-[10px] text-muted-foreground mt-0.5">
                Parent: {{ s.parent.name }} · {{ s.parent.phone }}
              </div>
            </div>
            <div class="text-right shrink-0 space-y-0.5">
              <p :class="['text-sm font-bold', gradeColor(s.grade)]">{{ s.grade }}%</p>
              <p class="text-xs text-muted-foreground">{{ s.attendance_rate }}% attendance</p>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Sessions tab -->
      <TabsContent value="sessions" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button class="gap-2" size="sm" @click="showCreateSession = true">
            <Plus class="h-4 w-4" />Schedule Session
          </Button>
        </div>
        <div v-if="!batchSessions.length" class="py-12 text-center text-sm text-muted-foreground rounded-xl border border-dashed border-border">
          No sessions scheduled. Click above to add one.
        </div>
        <Card v-for="s in batchSessions" :key="s.id">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-foreground">{{ s.title }}</p>
              <p class="text-xs text-muted-foreground">{{ s.scheduled_date }} · {{ formatTime(s.scheduled_time) }} · {{ s.duration_minutes }}min</p>
            </div>
            <Badge :variant="s.status === 'completed' ? 'outline' : 'default'" class="text-xs">{{ s.status }}</Badge>
            <Button v-if="s.status === 'completed'" variant="outline" size="sm" class="text-xs h-7 gap-1" @click="openAttendance(s)">
              <Users class="h-3.5 w-3.5" />Attendance
            </Button>
            <Button v-if="s.status === 'scheduled'" size="sm" class="text-xs h-7 gap-1 bg-emerald-600 hover:bg-emerald-700 text-white" @click="goLive(s.id)">
              Go Live
            </Button>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Grading tab -->
      <TabsContent value="grading" class="mt-4 space-y-3">
        <div v-if="!batchGrading.length" class="py-12 text-center text-sm text-muted-foreground">No submissions yet.</div>
        <Card v-for="sub in batchGrading" :key="sub.id" class="group hover:border-primary/20 transition-all">
          <CardContent class="p-4">
            <div class="flex items-start gap-3">
              <Avatar class="h-8 w-8 shrink-0">
                <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ initials(sub.student_name) }}</AvatarFallback>
              </Avatar>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-foreground">{{ sub.student_name }}</p>
                <p class="text-xs text-muted-foreground">{{ sub.assignment_title }} · Due {{ fmtDate(sub.due_date) }}</p>
                <p v-if="sub.student_note" class="text-xs italic text-muted-foreground mt-1">"{{ sub.student_note }}"</p>
                <p class="text-[10px] text-muted-foreground mt-1">Submitted {{ timeAgo(sub.submitted_at) }}</p>
              </div>
              <div class="shrink-0">
                <Badge v-if="sub.status === 'graded'" variant="outline" class="text-xs text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20">
                  {{ sub.score }}/{{ sub.total_points }}
                </Badge>
                <Button v-else size="sm" class="gap-1.5 text-xs h-7" @click="openGrade(sub)">
                  <PenLine class="h-3.5 w-3.5" />Grade
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Materials tab -->
      <TabsContent value="materials" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button class="gap-2" size="sm" @click="showMaterialDialog = true">
            <Plus class="h-4 w-4" />Add Material
          </Button>
        </div>
        <div v-if="!materials.length" class="py-12 text-center text-sm text-muted-foreground rounded-xl border border-dashed border-border">No materials yet.</div>
        <Card v-for="mat in materials" :key="mat.id">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <component :is="matTypeIcon[mat.type] ?? FileText" class="h-4 w-4" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-foreground truncate">{{ mat.title }}</p>
              <p class="text-xs text-muted-foreground">
                {{ mat.type.toUpperCase() }}
                <span v-if="mat.size">· {{ mat.size }}</span>
                · {{ mat.uploads }} downloads
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <Button variant="ghost" size="icon" class="h-7 w-7" as-child><a :href="mat.url" target="_blank"><ExternalLink class="h-3.5 w-3.5" /></a></Button>
              <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:bg-destructive/10" @click="deleteMaterial(mat.id)"><Trash2 class="h-3.5 w-3.5" /></Button>
            </div>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <!-- Add material dialog -->
    <Dialog :open="showMaterialDialog" @update:open="showMaterialDialog = $event">
      <DialogContent class="max-w-sm">
        <DialogHeader><DialogTitle>Add Material</DialogTitle></DialogHeader>
        <div class="space-y-3 py-2">
          <div class="space-y-1.5"><Label>Title</Label><Input v-model="matForm.title" placeholder="e.g., Week 9 Slides" /></div>
          <div class="space-y-1.5">
            <Label>Type</Label>
            <div class="flex gap-2">
              <button v-for="t in ['pdf','link','video']" :key="t" type="button"
                class="rounded-lg border px-3 py-1.5 text-xs capitalize font-medium transition-all"
                :class="matForm.type === t ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:border-primary/40'"
                @click="matForm.type = t"
              >{{ t }}</button>
            </div>
          </div>
          <div class="space-y-1.5"><Label>{{ matForm.type === 'pdf' ? 'File URL' : 'Link URL' }}</Label><Input v-model="matForm.url" placeholder="https://..." /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showMaterialDialog = false">Cancel</Button>
          <Button :disabled="isUploadingMat || !matForm.title.trim()" @click="saveMaterial">
            <RefreshCw v-if="isUploadingMat" class="mr-2 h-4 w-4 animate-spin" />Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Grade sheet -->
    <Sheet :open="showGradeSheet" @update:open="showGradeSheet = $event">
      <SheetContent class="w-full sm:max-w-md overflow-y-auto">
        <SheetHeader class="mb-4">
          <SheetTitle>Grade Submission</SheetTitle>
        </SheetHeader>
        <div v-if="activeSubmission" class="space-y-4">
          <div class="rounded-lg border border-border bg-muted/30 p-4">
            <p class="text-sm font-semibold">{{ activeSubmission.student_name }}</p>
            <p class="text-xs text-muted-foreground">{{ activeSubmission.assignment_title }}</p>
            <p v-if="activeSubmission.student_note" class="text-xs italic text-muted-foreground mt-2">"{{ activeSubmission.student_note }}"</p>
            <Button variant="outline" size="sm" class="mt-2 gap-1.5 text-xs h-7" as-child>
              <a :href="activeSubmission.file_url" target="_blank"><ExternalLink class="h-3.5 w-3.5" />View Submission</a>
            </Button>
          </div>
          <div class="space-y-1.5">
            <Label>Score (out of {{ activeSubmission.total_points }})</Label>
            <Input v-model.number="gradeForm.score" type="number" :min="0" :max="activeSubmission.total_points" />
            <p v-if="gradeForm.score !== ''" class="text-xs font-medium" :class="gradeColor(Math.round((gradeForm.score / activeSubmission.total_points) * 100))">
              {{ Math.round((gradeForm.score / activeSubmission.total_points) * 100) }}%
            </p>
          </div>
          <div class="space-y-1.5">
            <Label>Feedback for student</Label>
            <Textarea v-model="gradeForm.feedback" placeholder="Provide constructive feedback..." :rows="5" />
          </div>
          <Button class="w-full gap-2" :disabled="gradeForm.score === '' || isSavingGrade" @click="saveGrade">
            <RefreshCw v-if="isSavingGrade" class="h-4 w-4 animate-spin" />
            <CheckCircle2 v-else class="h-4 w-4" />
            {{ isSavingGrade ? 'Saving...' : 'Save Grade' }}
          </Button>
        </div>
      </SheetContent>
    </Sheet>

    <!-- Create session dialog -->
    <CreateSessionDialog
      :open="showCreateSession"
      @update:open="showCreateSession = $event"
      @saved="s => { createSession(s); showCreateSession = false; toast({ title: 'Session scheduled' }) }"
    />

    <!-- Attendance sheet -->
    <Sheet :open="showAttendance" @update:open="showAttendance = $event">
      <SheetContent class="w-full sm:max-w-lg overflow-y-auto">
        <SheetHeader class="mb-4"><SheetTitle>Attendance</SheetTitle></SheetHeader>
        <AttendanceTracker v-if="attendanceSession" :session-id="attendanceSession.id" :readonly="attendanceSession.status === 'completed'" @saved="showAttendance = false" />
      </SheetContent>
    </Sheet>

  </div>
  </InstructorLayout>
</template>