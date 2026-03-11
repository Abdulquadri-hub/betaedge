<script setup>

import { ref, computed } from 'vue'
import { router }        from '@inertiajs/vue3'
import {
  School, BookOpen, Users, Clock, Zap,
  ChevronRight, Upload, PenLine, Video,
  MessageCircle, BarChart2, CheckCircle2,
  AlertCircle, Star, TrendingUp, ExternalLink,
  RefreshCw,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Badge }    from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { toast } from 'vue-sonner'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const {
  currentSchool, schools, activeBatches, pendingGrading, strugglingStudents,
  batches, students, totalEarned,
  formatNaira, formatTime, fmtDate, initials, gradeColor,
  switchSchool,
} = useInstructorDashboard()

const isSwitching = ref(null)

async function handleSwitch(school) {
  if (school.active) return
  isSwitching.value = school.tenant_id
  try {
    await switchSchool(school.tenant_id)
    toast({ title: `Switched to ${school.name}`, description: 'Your dashboard now shows this school.' })
  } finally {
    isSwitching.value = null
  }
}

const nextSession = computed(() =>
  batches.value
    .filter(b => b.next_session && b.status === 'active')
    .sort((a,b) => new Date(a.next_session.date) - new Date(b.next_session.date))[0]?.next_session ?? null
)

const urgentTasks = computed(() => {
  const tasks = []
  batches.value.forEach(b => {
    if (b.pending_grading > 0)
      tasks.push({ label:`Grade ${b.pending_grading} submission${b.pending_grading>1?'s':''} – ${b.name}`, type:'grading', route:'/instructor/grading' })
    if (b.pending_assignments > 0)
      tasks.push({ label:`${b.pending_assignments} assignment${b.pending_assignments>1?'s':''} due soon – ${b.name}`, type:'assignments', route:`/instructor/batches/${b.id}` })
  })
  if (strugglingStudents.value.length > 0)
    tasks.push({ label:`${strugglingStudents.value.length} student${strugglingStudents.value.length>1?'s':''} need attention (low grade/attendance)`, type:'students', route:'/instructor/students' })
  return tasks
})
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">
        Welcome back, Adebayo! 
      </h1>
      <p class="text-sm text-muted-foreground mt-1">
        Currently viewing: <span class="font-medium text-foreground">{{ currentSchool?.name }}</span>
      </p>
    </div>

    <!-- ── School Selector (V3 spec: card switcher) ─────────────────────── -->
    <div>
      <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Your Schools</p>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="school in schools"
          :key="school.tenant_id"
          type="button"
          class="relative flex flex-col gap-2 rounded-xl border p-4 text-left transition-all"
          :class="school.active
            ? 'border-primary bg-primary/5 shadow-sm'
            : 'border-border bg-card hover:border-primary/40 hover:bg-muted/30'"
          :disabled="isSwitching === school.tenant_id"
          @click="handleSwitch(school)"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                <School class="h-4 w-4" />
              </div>
              <div>
                <p class="text-sm font-semibold text-foreground">{{ school.name }}</p>
                <p class="text-[10px] text-muted-foreground">{{ school.subdomain }}.teach.com</p>
              </div>
            </div>
            <Badge v-if="school.active" variant="default" class="text-[10px] h-4 px-1.5 shrink-0">Active</Badge>
            <RefreshCw v-else-if="isSwitching === school.tenant_id" class="h-4 w-4 animate-spin text-muted-foreground" />
          </div>
          <div class="grid grid-cols-3 gap-1 text-center">
            <div>
              <p class="text-sm font-bold text-foreground">{{ school.active_batches }}</p>
              <p class="text-[10px] text-muted-foreground">Batches</p>
            </div>
            <div>
              <p class="text-sm font-bold text-foreground">{{ school.total_students }}</p>
              <p class="text-[10px] text-muted-foreground">Students</p>
            </div>
            <div>
              <p class="text-xs font-medium text-foreground truncate">{{ school.next_class.split(',')[0] }}</p>
              <p class="text-[10px] text-muted-foreground">Next class</p>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- ── Stat cards ─────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="cursor-pointer hover:border-primary/30 transition-colors" @click="router.visit('/instructor/batches')">
        <CardContent class="p-4">
          <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2"><BookOpen class="h-3.5 w-3.5" />Active Batches</div>
          <p class="text-2xl font-bold text-foreground">{{ activeBatches.length }}</p>
          <p class="text-xs text-muted-foreground">at {{ currentSchool?.name }}</p>
        </CardContent>
      </Card>
      <Card class="cursor-pointer hover:border-primary/30 transition-colors" @click="router.visit('/instructor/students')">
        <CardContent class="p-4">
          <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2"><Users class="h-3.5 w-3.5" />Total Students</div>
          <p class="text-2xl font-bold text-foreground">{{ students.length }}</p>
          <p class="text-xs text-muted-foreground">across all batches</p>
        </CardContent>
      </Card>
      <Card class="cursor-pointer hover:border-amber-400/30 transition-colors" @click="router.visit('/instructor/grading')">
        <CardContent class="p-4">
          <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2"><PenLine class="h-3.5 w-3.5" />Pending Grading</div>
          <p class="text-2xl font-bold" :class="pendingGrading.length > 0 ? 'text-amber-600' : 'text-foreground'">
            {{ pendingGrading.length }}
          </p>
          <p class="text-xs text-muted-foreground">submissions awaiting</p>
        </CardContent>
      </Card>
      <Card class="cursor-pointer hover:border-emerald-400/30 transition-colors" @click="router.visit('/instructor/earnings')">
        <CardContent class="p-4">
          <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2"><BarChart2 class="h-3.5 w-3.5" />Total Earned</div>
          <p class="text-2xl font-bold text-emerald-600">{{ formatNaira(totalEarned) }}</p>
          <p class="text-xs text-muted-foreground">across all schools</p>
        </CardContent>
      </Card>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

      <!-- Left: batches + next session -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Next class countdown -->
        <Card v-if="nextSession" class="border-primary/20 bg-primary/5">
          <CardContent class="p-4">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shrink-0">
                <Video class="h-5 w-5" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-muted-foreground font-medium mb-0.5">Next Live Class</p>
                <p class="text-sm font-bold text-foreground">{{ nextSession.title }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">
                  {{ fmtDate(nextSession.date) }} · {{ formatTime(nextSession.time) }}
                </p>
              </div>
              <Button size="sm" class="gap-2 shrink-0 text-xs" @click="router.visit('/instructor/sessions')">
                Prepare <ChevronRight class="h-3.5 w-3.5" />
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Active batches -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-semibold text-foreground">Active Batches</p>
            <Button variant="ghost" size="sm" class="text-xs gap-1" @click="router.visit('/instructor/batches')">
              View all <ChevronRight class="h-3.5 w-3.5" />
            </Button>
          </div>
          <div class="space-y-3">
            <Card
              v-for="batch in activeBatches"
              :key="batch.id"
              class="cursor-pointer hover:border-primary/30 transition-all hover:shadow-sm"
              @click="router.visit(`/instructor/batches/${batch.id}`)"
            >
              <CardContent class="p-4">
                <div class="flex items-start justify-between gap-2 mb-3">
                  <div>
                    <p class="text-sm font-semibold text-foreground">{{ batch.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ batch.course_name }}</p>
                  </div>
                  <Badge variant="default" class="text-xs shrink-0">Week {{ batch.week }}/{{ batch.total_weeks }}</Badge>
                </div>
                <!-- Progress bar -->
                <div class="mb-3">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="text-muted-foreground">Progress</span>
                    <span class="font-medium">{{ Math.round((batch.week/batch.total_weeks)*100) }}%</span>
                  </div>
                  <Progress :value="(batch.week/batch.total_weeks)*100" class="h-1.5" />
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                  <div>
                    <p class="text-sm font-bold text-foreground">{{ batch.current_enrollment }}</p>
                    <p class="text-[10px] text-muted-foreground">Students</p>
                  </div>
                  <div>
                    <p class="text-sm font-bold" :class="gradeColor(batch.avg_grade)">{{ batch.avg_grade }}%</p>
                    <p class="text-[10px] text-muted-foreground">Avg Grade</p>
                  </div>
                  <div>
                    <p class="text-sm font-bold text-foreground">{{ batch.attendance_rate }}%</p>
                    <p class="text-[10px] text-muted-foreground">Attendance</p>
                  </div>
                </div>
                <!-- Pending tasks badges -->
                <div v-if="batch.pending_grading > 0 || batch.pending_assignments > 0" class="flex gap-2 mt-3 pt-2 border-t border-border">
                  <Badge v-if="batch.pending_grading > 0" variant="secondary" class="text-[10px] gap-1">
                    <PenLine class="h-2.5 w-2.5" />{{ batch.pending_grading }} to grade
                  </Badge>
                  <Badge v-if="batch.pending_assignments > 0" variant="outline" class="text-[10px] gap-1">
                    <Clock class="h-2.5 w-2.5" />{{ batch.pending_assignments }} due soon
                  </Badge>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>

      <!-- Right sidebar -->
      <div class="space-y-4">

        <!-- Quick actions (V3 spec) -->
        <Card>
          <CardHeader><CardTitle class="text-sm">Quick Actions</CardTitle></CardHeader>
          <CardContent class="pt-0 space-y-2">
            <Button variant="outline" class="w-full justify-start gap-2 text-sm h-9" @click="router.visit('/instructor/grading')">
              <PenLine class="h-4 w-4 text-primary" />Grade Assignments
              <Badge v-if="pendingGrading.length" variant="destructive" class="ml-auto text-[10px] h-4 px-1">{{ pendingGrading.length }}</Badge>
            </Button>
            <Button variant="outline" class="w-full justify-start gap-2 text-sm h-9" @click="router.visit('/instructor/sessions')">
              <Video class="h-4 w-4 text-primary" />Schedule / Go Live
            </Button>
            <Button variant="outline" class="w-full justify-start gap-2 text-sm h-9" @click="router.visit('/instructor/batches')">
              <Upload class="h-4 w-4 text-primary" />Upload Materials
            </Button>
            <Button variant="outline" class="w-full justify-start gap-2 text-sm h-9" @click="router.visit('/instructor/students')">
              <Users class="h-4 w-4 text-primary" />View Students
            </Button>
          </CardContent>
        </Card>

        <!-- Urgent tasks -->
        <Card v-if="urgentTasks.length > 0">
          <CardHeader><CardTitle class="text-sm flex items-center gap-2"><AlertCircle class="h-4 w-4 text-amber-600" />Needs Attention</CardTitle></CardHeader>
          <CardContent class="pt-0 space-y-2">
            <button
              v-for="(task,i) in urgentTasks"
              :key="i"
              type="button"
              class="w-full flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-3 text-left hover:bg-amber-100 dark:hover:bg-amber-950/40 transition-colors"
              @click="router.visit(task.route)"
            >
              <AlertCircle class="h-3.5 w-3.5 text-amber-600 shrink-0 mt-0.5" />
              <p class="text-xs text-amber-800 dark:text-amber-300 font-medium">{{ task.label }}</p>
            </button>
          </CardContent>
        </Card>

        <!-- Struggling students -->
        <Card v-if="strugglingStudents.length > 0">
          <CardHeader><CardTitle class="text-sm flex items-center gap-2"><TrendingUp class="h-4 w-4 text-destructive" />Students Needing Help</CardTitle></CardHeader>
          <CardContent class="pt-0 space-y-2">
            <div v-for="s in strugglingStudents" :key="s.id"
              class="flex items-center gap-2 rounded-lg border border-border p-2 cursor-pointer hover:bg-muted/40"
              @click="router.visit('/instructor/students')"
            >
              <Avatar class="h-7 w-7">
                <AvatarFallback class="text-[10px] font-bold bg-destructive/10 text-destructive">{{ initials(s.name) }}</AvatarFallback>
              </Avatar>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-foreground truncate">{{ s.name }}</p>
                <p class="text-[10px] text-muted-foreground">
                  Grade: <span :class="gradeColor(s.grade)">{{ s.grade }}%</span>
                  · Attendance: {{ s.attendance_rate }}%
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

      </div>
    </div>
  </div>
  </InstructorLayout>
</template>