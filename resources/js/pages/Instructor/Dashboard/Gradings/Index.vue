<script setup>

import { ref, computed } from 'vue'
import {
  PenLine, CheckCircle2, Clock, Search,
  ExternalLink, RefreshCw, Download, BarChart2,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Badge }    from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription,
} from '@/components/ui/sheet'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { gradingQueue, batches, gradeColor, initials, fmtDate, gradeSubmission } = useInstructorDashboard()

const search      = ref('')
const batchFilter = ref('all')
const statusFilter = ref('pending')

const filtered = computed(() => {
  let list = gradingQueue.value
  if (batchFilter.value !== 'all')   list = list.filter(s => s.batch_id === batchFilter.value)
  if (statusFilter.value !== 'all')  list = list.filter(s => s.status === statusFilter.value)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(s =>
    s.student_name.toLowerCase().includes(q) ||
    s.assignment_title.toLowerCase().includes(q)
  )
  return list.sort((a, b) => new Date(a.submitted_at) - new Date(b.submitted_at))
})

const stats = computed(() => ({
  pending: gradingQueue.value.filter(s => s.status === 'submitted').length,
  graded:  gradingQueue.value.filter(s => s.status === 'graded').length,
  total:   gradingQueue.value.length,
  completion: gradingQueue.value.length
    ? Math.round((gradingQueue.value.filter(s => s.status === 'graded').length / gradingQueue.value.length) * 100)
    : 100,
}))

// ── Group by assignment ───────────────────────────────────────────────────────
const byAssignment = computed(() => {
  const groups = {}
  gradingQueue.value.forEach(s => {
    const key = s.assignment_id
    if (!groups[key]) groups[key] = { title: s.assignment_title, batch: s.batch_name, total: 0, graded: 0 }
    groups[key].total++
    if (s.status === 'graded') groups[key].graded++
  })
  return Object.values(groups)
})

// ── Grade sheet ───────────────────────────────────────────────────────────────
const showGrade    = ref(false)
const activeSub    = ref(null)
const gradeForm    = ref({ score: '', feedback: '' })
const isSaving     = ref(false)
const gradeError   = ref('')

function openGrade(sub) {
  activeSub.value    = sub
  gradeForm.value    = { score: sub.score ?? '', feedback: sub.feedback ?? '' }
  gradeError.value   = ''
  showGrade.value    = true
}

async function saveGrade() {
  const score = Number(gradeForm.value.score)
  if (gradeForm.value.score === '' || isNaN(score)) { gradeError.value = 'Enter a valid score'; return }
  if (score < 0 || score > activeSub.value.total_points) {
    gradeError.value = `Score must be 0–${activeSub.value.total_points}`; return
  }
  isSaving.value = true
  try {
    const result = await gradeSubmission(activeSub.value.id, score, gradeForm.value.feedback)
    if (result.success) {
      toast({ title: 'Grade saved', description: `${activeSub.value.student_name} has been notified.` })
      showGrade.value = false
    }
  } finally {
    isSaving.value = false
  }
}

function exportGrades() {
  // TODO: window.location.href = route('instructor.grading.export', { batch_id: batchFilter.value })
  toast({ title: 'Export started', description: 'Grade report CSV downloading...' })
}

function timeAgo(iso) {
  const d = Math.floor((Date.now() - new Date(iso)) / 86400000)
  const h = Math.floor((Date.now() - new Date(iso)) / 3600000)
  if (h < 1) return 'Just now'
  if (h < 24) return `${h}h ago`
  return `${d}d ago`
}

const pct = (s) => s.total_points ? Math.round((s.score / s.total_points) * 100) : 0
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Grading</h1>
        <p class="text-sm text-muted-foreground mt-1">Review and grade student submissions across all your batches.</p>
      </div>
      <Button variant="outline" class="gap-2" @click="exportGrades">
        <Download class="h-4 w-4" />Export Grades
      </Button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Clock class="h-3.5 w-3.5 text-amber-600" />Pending</p>
          <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.pending }}</p>
          <p class="text-xs text-muted-foreground">awaiting review</p>
        </CardContent>
      </Card>
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />Graded</p>
          <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.graded }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><BarChart2 class="h-3.5 w-3.5" />Total</p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ stats.total }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium mb-2">Completion</p>
          <p class="text-2xl font-bold text-primary">{{ stats.completion }}%</p>
          <Progress :value="stats.completion" class="h-1.5 mt-1" />
        </CardContent>
      </Card>
    </div>

    <!-- Assignment progress overview -->
    <Card>
      <CardHeader><CardTitle class="text-sm">Progress by Assignment</CardTitle></CardHeader>
      <CardContent class="pt-0 space-y-3">
        <div v-for="(asg, i) in byAssignment" :key="i" class="space-y-1">
          <div class="flex items-center justify-between text-xs">
            <div class="flex-1 min-w-0">
              <span class="font-medium text-foreground truncate">{{ asg.title }}</span>
              <span class="text-muted-foreground ml-2">· {{ asg.batch }}</span>
            </div>
            <span :class="['font-bold ml-4 shrink-0', asg.graded === asg.total ? 'text-emerald-600' : 'text-amber-600']">
              {{ asg.graded }}/{{ asg.total }}
            </span>
          </div>
          <Progress :value="asg.total ? (asg.graded / asg.total) * 100 : 0" class="h-1.5" />
        </div>
      </CardContent>
    </Card>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
      <div class="relative flex-1 max-w-sm min-w-48">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search submissions..." class="pl-9" />
      </div>
      <Select v-model="batchFilter">
        <SelectTrigger class="w-48 h-9 text-sm"><SelectValue placeholder="All Batches" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All Batches</SelectItem>
          <SelectItem v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</SelectItem>
        </SelectContent>
      </Select>
      <Tabs :model-value="statusFilter" @update:model-value="statusFilter = $event">
        <TabsList>
          <TabsTrigger value="pending">
            Pending
            <Badge v-if="stats.pending" variant="destructive" class="ml-1.5 h-4 px-1 text-[10px]">{{ stats.pending }}</Badge>
          </TabsTrigger>
          <TabsTrigger value="graded">Graded</TabsTrigger>
          <TabsTrigger value="all">All</TabsTrigger>
        </TabsList>
      </Tabs>
    </div>

    <!-- Empty -->
    <div v-if="!filtered.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <CheckCircle2 class="h-10 w-10 text-emerald-500/50 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">
        {{ statusFilter === 'pending' ? 'No pending submissions — all caught up! ' : 'No submissions found.' }}
      </p>
    </div>

    <!-- Submissions list -->
    <div v-else class="space-y-3">
      <Card
        v-for="sub in filtered" :key="sub.id"
        class="group hover:border-primary/20 hover:shadow-sm transition-all"
      >
        <CardContent class="p-4">
          <div class="flex items-start gap-3">
            <Avatar class="h-9 w-9 shrink-0 mt-0.5">
              <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ initials(sub.student_name) }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2 flex-wrap">
                <div>
                  <p class="text-sm font-semibold text-foreground">{{ sub.student_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ sub.assignment_title }}</p>
                  <p class="text-xs text-muted-foreground">{{ sub.batch_name }} · Due {{ fmtDate(sub.due_date) }}</p>
                </div>
                <!-- Grade display (if graded) -->
                <div v-if="sub.status === 'graded'" class="text-right shrink-0">
                  <p :class="['text-lg font-black', gradeColor(pct(sub))]">{{ sub.score }}/{{ sub.total_points }}</p>
                  <p class="text-xs text-muted-foreground">{{ pct(sub) }}%</p>
                </div>
              </div>
              <!-- Student note -->
              <p v-if="sub.student_note" class="text-xs italic text-muted-foreground bg-muted/50 rounded p-2 mt-2">
                "{{ sub.student_note }}"
              </p>
              <!-- Feedback preview (if graded) -->
              <p v-if="sub.status === 'graded' && sub.feedback" class="text-xs text-muted-foreground mt-2 line-clamp-2">
                📝 {{ sub.feedback }}
              </p>
              <div class="flex items-center justify-between mt-3 flex-wrap gap-2">
                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                  <span>Submitted {{ timeAgo(sub.submitted_at) }}</span>
                  <a :href="sub.file_url" target="_blank" class="flex items-center gap-1 text-primary hover:underline">
                    <ExternalLink class="h-3 w-3" />View file
                  </a>
                </div>
                <Button
                  size="sm"
                  :variant="sub.status === 'graded' ? 'outline' : 'default'"
                  class="gap-1.5 text-xs h-7"
                  @click="openGrade(sub)"
                >
                  <PenLine class="h-3.5 w-3.5" />
                  {{ sub.status === 'graded' ? 'Edit Grade' : 'Grade Now' }}
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Grade sheet -->
    <Sheet :open="showGrade" @update:open="showGrade = $event">
      <SheetContent class="w-full sm:max-w-md overflow-y-auto">
        <SheetHeader class="mb-5">
          <SheetTitle class="text-base">Grade Submission</SheetTitle>
          <SheetDescription class="text-xs">
            {{ activeSub?.student_name }} · {{ activeSub?.assignment_title }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="activeSub" class="space-y-5">
          <!-- Submission info -->
          <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted-foreground">Total points</span>
              <span class="font-bold">{{ activeSub.total_points }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted-foreground">Due date</span>
              <span>{{ fmtDate(activeSub.due_date) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted-foreground">Submitted</span>
              <span>{{ timeAgo(activeSub.submitted_at) }}</span>
            </div>
            <p v-if="activeSub.student_note" class="text-xs italic text-muted-foreground pt-1 border-t border-border">
              "{{ activeSub.student_note }}"
            </p>
            <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7 w-full mt-1" as-child>
              <a :href="activeSub.file_url" target="_blank"><ExternalLink class="h-3.5 w-3.5" />Open Submission</a>
            </Button>
          </div>

          <!-- Score input -->
          <div class="space-y-1.5">
            <Label class="font-semibold">Score <span class="text-muted-foreground font-normal">(out of {{ activeSub.total_points }})</span></Label>
            <div class="relative">
              <Input
                v-model="gradeForm.score"
                type="number"
                :min="0"
                :max="activeSub.total_points"
                placeholder="Enter score"
                class="text-lg font-bold h-12"
                :class="gradeError && 'border-destructive'"
                @input="gradeError = ''"
              />
              <div v-if="gradeForm.score !== ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-right">
                <p :class="['text-sm font-bold', gradeColor(pct({ score: Number(gradeForm.score), total_points: activeSub.total_points }))]">
                  {{ pct({ score: Number(gradeForm.score), total_points: activeSub.total_points }) }}%
                </p>
              </div>
            </div>
            <p v-if="gradeError" class="text-xs text-destructive">{{ gradeError }}</p>
          </div>

          <!-- Feedback -->
          <div class="space-y-1.5">
            <Label class="font-semibold">Feedback <span class="text-muted-foreground font-normal">(shown to student)</span></Label>
            <Textarea
              v-model="gradeForm.feedback"
              placeholder="Provide constructive feedback on the submission. Be specific about what was done well and what can be improved."
              :rows="6"
            />
          </div>

          <!-- Save -->
          <Button
            class="w-full gap-2 h-11"
            :disabled="gradeForm.score === '' || isSaving"
            @click="saveGrade"
          >
            <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin" />
            <CheckCircle2 v-else class="h-4 w-4" />
            {{ isSaving ? 'Saving grade...' : 'Save Grade & Notify Student' }}
          </Button>
        </div>
      </SheetContent>
    </Sheet>

  </div>
  </InstructorLayout>
</template>