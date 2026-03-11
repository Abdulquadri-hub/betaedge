<script setup>
import { ref, computed } from 'vue'
import {
  BarChart2, 
  //TrendingUp, 
  Users, BookOpen, 
  //Award, 
  Download,
  GraduationCap, CheckCircle2, Clock, AlertCircle,
  ArrowUpRight, ArrowDownRight, Star, UserCheck, UserX,
  Activity, 
  //ChevronRight,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Badge }    from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const activeTab = ref('overview')
const period    = ref('6months')

const kpis = ref({
  avg_completion:   89,
  avg_grade:        80,
  avg_attendance:   87,
  retention_rate:   84,
  total_enrolled:  173,
  total_certificates: 115,
  dropout_count:    28,
})

const enrollmentTrend = ref([
  { month: 'Oct 25', new_students: 13, total_active: 46 },
  { month: 'Nov 25', new_students: 22, total_active: 62 },
  { month: 'Dec 25', new_students: 17, total_active: 71 },
  { month: 'Jan 26', new_students: 24, total_active: 82 },
  { month: 'Feb 26', new_students: 24, total_active: 78 },
  { month: 'Mar 26', new_students: 28, total_active: 67 },
])

const batchPerformance = ref([
  { batch: 'Web Dev Batch 3',       course: 'Full Stack Web Dev',  status: 'active',
    students: 24, avg_grade: 74, attendance_rate: 87, completion_rate: null,
    sessions_done: 18, sessions_total: 24, start_date: '2026-01-12', end_date: '2026-04-06' },
  { batch: 'Data Science Batch 1',  course: 'Data Science',        status: 'active',
    students: 25, avg_grade: 81, attendance_rate: 91, completion_rate: null,
    sessions_done: 16, sessions_total: 24, start_date: '2026-01-20', end_date: '2026-04-14' },
  { batch: 'UI/UX Design Batch 2',  course: 'UI/UX Design',        status: 'active',
    students: 18, avg_grade: 79, attendance_rate: 83, completion_rate: null,
    sessions_done: 12, sessions_total: 20, start_date: '2026-02-03', end_date: '2026-05-05' },
  { batch: 'Web Dev Batch 2',       course: 'Full Stack Web Dev',  status: 'completed',
    students: 22, avg_grade: 83, attendance_rate: 90, completion_rate: 87,
    sessions_done: 24, sessions_total: 24, start_date: '2025-09-01', end_date: '2025-12-01' },
  { batch: 'Excel Mastery Batch 1', course: 'Excel Mastery',       status: 'completed',
    students: 33, avg_grade: 86, attendance_rate: 94, completion_rate: 92,
    sessions_done: 16, sessions_total: 16, start_date: '2025-10-01', end_date: '2025-11-26' },
])

const courseComparison = ref([
  { course: 'Full Stack Web Dev', batches: 3, students: 72, avg_grade: 79, avg_attendance: 89, avg_completion: 87  },
  { course: 'Data Science',       batches: 2, students: 43, avg_grade: 81, avg_attendance: 91, avg_completion: null },
  { course: 'UI/UX Design',       batches: 2, students: 35, avg_grade: 79, avg_attendance: 83, avg_completion: 85  },
  { course: 'Excel Mastery',      batches: 1, students: 33, avg_grade: 86, avg_attendance: 94, avg_completion: 92  },
])

const instructorMetrics = ref([
  { name: 'Mr. Chidi Okeke',  course: 'Full Stack Web Dev', students: 46, active_batches: 1, avg_grade: 79, avg_attendance: 89, satisfaction: 4.8, batches_completed: 1, assignments_graded: 84  },
  { name: 'Ms. Kemi Adebayo', course: 'Data Science',       students: 25, active_batches: 1, avg_grade: 81, avg_attendance: 91, satisfaction: 4.9, batches_completed: 0, assignments_graded: 47  },
  { name: 'Mr. Tayo Bello',   course: 'UI/UX Design',       students: 53, active_batches: 1, avg_grade: 79, avg_attendance: 83, satisfaction: 4.7, batches_completed: 2, assignments_graded: 102 },
  { name: 'Mrs. Ada Johnson', course: 'Excel Mastery',       students: 33, active_batches: 0, avg_grade: 86, avg_attendance: 94, satisfaction: 4.9, batches_completed: 1, assignments_graded: 66  },
])

const retention = ref({
  enrolled: 173, completed: 115, still_active: 30, dropped: 28, rate: 84,
  grade_dist: { a: 41, b: 38, c: 10, fail: 11 },
  dropout_reasons: [
    { reason: 'No reason given',      count: 11 },
    { reason: 'Schedule conflict',    count:  8 },
    { reason: 'Financial difficulty', count:  5 },
    { reason: 'Found alternative',    count:  4 },
  ],
})

// ═══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

function gradeColor(g) {
  if (g >= 80) return 'text-emerald-600'
  if (g >= 60) return 'text-amber-600'
  return 'text-destructive'
}
function gradeBg(g) {
  if (g >= 80) return 'bg-emerald-500'
  if (g >= 60) return 'bg-amber-400'
  return 'bg-destructive'
}
function initials(name) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function fmtDate(iso) {
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: '2-digit' })
}

const maxNewStudents = computed(() => Math.max(...enrollmentTrend.value.map(m => m.new_students)))
const maxTotalActive = computed(() => Math.max(...enrollmentTrend.value.map(m => m.total_active)))
const rankedInstructors = computed(() =>
  [...instructorMetrics.value].sort((a, b) => b.avg_grade - a.avg_grade)
)
const maxGraded = computed(() => Math.max(...instructorMetrics.value.map(i => i.assignments_graded)))
const maxCourseStudents = computed(() => Math.max(...courseComparison.value.map(c => c.students)))

function exportReport(type) {
  // TODO: window.location.href = route('dashboard.reports.export', { type, period: period.value })
  toast({ title: 'Export started', description: `${type} report CSV is downloading...` })
}
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Analytics & Reports</h1>
        <p class="text-sm text-muted-foreground mt-1">Enrollment trends, batch performance, instructor leaderboard, and student retention.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="period">
          <SelectTrigger class="w-36 h-9 text-sm"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="3months">Last 3 months</SelectItem>
            <SelectItem value="6months">Last 6 months</SelectItem>
            <SelectItem value="12months">Last year</SelectItem>
            <SelectItem value="all">All time</SelectItem>
          </SelectContent>
        </Select>
        <Button variant="outline" size="sm" class="gap-2" @click="exportReport('full')">
          <Download class="h-4 w-4" />Export CSV
        </Button>
      </div>
    </div>

    <!-- Top KPI row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <Card class="border-primary/20 bg-primary/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <CheckCircle2 class="h-3.5 w-3.5 text-primary" />Avg Completion
          </p>
          <p class="text-3xl font-black text-primary mt-1">{{ kpis.avg_completion }}%</p>
          <p class="text-xs text-muted-foreground">completed batches</p>
        </CardContent>
      </Card>
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <BarChart2 class="h-3.5 w-3.5 text-emerald-600" />Avg Grade
          </p>
          <p class="text-3xl font-black text-emerald-600 mt-1">{{ kpis.avg_grade }}%</p>
          <p class="text-xs text-muted-foreground">across all batches</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Activity class="h-3.5 w-3.5" />Avg Attendance
          </p>
          <p class="text-3xl font-black text-foreground mt-1">{{ kpis.avg_attendance }}%</p>
          <p class="text-xs text-muted-foreground">all recorded sessions</p>
        </CardContent>
      </Card>
      <Card class="border-secondary/20 bg-secondary/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <UserCheck class="h-3.5 w-3.5 text-secondary" />Retention Rate
          </p>
          <p class="text-3xl font-black text-secondary mt-1">{{ kpis.retention_rate }}%</p>
          <p class="text-xs text-muted-foreground">complete their batch</p>
        </CardContent>
      </Card>
    </div>

    <Tabs v-model="activeTab">
      <TabsList class="flex-wrap h-auto gap-1">
        <TabsTrigger value="overview">Overview</TabsTrigger>
        <TabsTrigger value="batches">Batch Performance</TabsTrigger>
        <TabsTrigger value="courses">Course Comparison</TabsTrigger>
        <TabsTrigger value="instructors">Instructors</TabsTrigger>
        <TabsTrigger value="retention">Retention</TabsTrigger>
      </TabsList>

      <!-- ──────────────────────────── OVERVIEW ──────────────────────────── -->
      <TabsContent value="overview" class="mt-4 space-y-4">
        <div class="grid lg:grid-cols-3 gap-4">

          <!-- Enrollment bar chart + table -->
          <Card class="lg:col-span-2">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm">Monthly New Enrollments</CardTitle>
              <Button variant="ghost" size="sm" class="gap-1.5 text-xs h-7" @click="exportReport('enrollment')">
                <Download class="h-3.5 w-3.5" />CSV
              </Button>
            </CardHeader>
            <CardContent class="pt-0">
              <!-- Bar chart -->
              <div class="flex items-end gap-2 h-36 mt-2">
                <div
                  v-for="(m, i) in enrollmentTrend"
                  :key="m.month"
                  class="flex flex-col items-center gap-0.5 flex-1 min-w-0"
                >
                  <p class="text-[10px] font-bold text-primary">{{ m.new_students }}</p>
                  <div
                    class="w-full rounded-t-sm bg-primary"
                    :style="`height:${Math.round((m.new_students / maxNewStudents) * 100)}px`"
                  />
                  <p class="text-[10px] text-muted-foreground truncate w-full text-center leading-tight">{{ m.month }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                <span class="flex items-center gap-1.5">
                  <span class="h-2.5 w-2.5 rounded-sm bg-primary inline-block" />New enrollments per month
                </span>
              </div>
              <!-- Data table -->
              <div class="mt-4 rounded-lg border border-border overflow-hidden">
                <table class="w-full text-xs">
                  <thead class="bg-muted/50">
                    <tr>
                      <th class="text-left px-3 py-2 font-semibold text-muted-foreground">Month</th>
                      <th class="text-right px-3 py-2 font-semibold text-primary">New Students</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground">Total Active</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground">Change</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-border">
                    <tr v-for="(m, i) in enrollmentTrend" :key="m.month" class="hover:bg-muted/30">
                      <td class="px-3 py-2 font-medium text-foreground">{{ m.month }}</td>
                      <td class="px-3 py-2 text-right font-bold text-primary">{{ m.new_students }}</td>
                      <td class="px-3 py-2 text-right text-foreground">{{ m.total_active }}</td>
                      <td class="px-3 py-2 text-right">
                        <span
                          v-if="i > 0"
                          class="flex items-center justify-end gap-0.5 font-medium text-xs"
                          :class="m.new_students >= enrollmentTrend[i-1].new_students ? 'text-emerald-600' : 'text-destructive'"
                        >
                          <component :is="m.new_students >= enrollmentTrend[i-1].new_students ? ArrowUpRight : ArrowDownRight" class="h-3 w-3" />
                          {{ Math.abs(m.new_students - enrollmentTrend[i-1].new_students) }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          <!-- Stats sidebar -->
          <div class="space-y-4">
            <Card>
              <CardHeader><CardTitle class="text-sm">All-Time Totals</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-2">
                <div v-for="stat in [
                  { icon: Users,          label: 'Total Students',       value: kpis.total_enrolled,   color: 'text-foreground' },
                  { icon: GraduationCap,  label: 'Certificates Issued',  value: kpis.total_certificates, color: 'text-emerald-600' },
                  { icon: UserX,          label: 'Dropped Out',          value: kpis.dropout_count,    color: 'text-destructive' },
                  { icon: BookOpen,       label: 'Active Batches',        value: batchPerformance.filter(b=>b.status==='active').length, color: 'text-primary' },
                ]" :key="stat.label" class="flex items-center justify-between py-2 border-b border-border/50 last:border-0">
                  <span class="text-xs text-muted-foreground flex items-center gap-1.5">
                    <component :is="stat.icon" class="h-3.5 w-3.5" />{{ stat.label }}
                  </span>
                  <span :class="['text-sm font-bold', stat.color]">{{ stat.value }}</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle class="text-sm">Grade Distribution</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-2.5">
                <div v-for="item in [
                  { label: 'A (80–100%)', pct: retention.grade_dist.a,    bg: 'bg-emerald-500', text: 'text-emerald-600' },
                  { label: 'B (60–79%)',  pct: retention.grade_dist.b,    bg: 'bg-primary',     text: 'text-primary'    },
                  { label: 'C (50–59%)',  pct: retention.grade_dist.c,    bg: 'bg-amber-400',   text: 'text-amber-600'  },
                  { label: 'Fail (<50%)', pct: retention.grade_dist.fail, bg: 'bg-destructive', text: 'text-destructive' },
                ]" :key="item.label">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-muted-foreground">{{ item.label }}</span>
                    <span :class="['font-bold', item.text]">{{ item.pct }}%</span>
                  </div>
                  <div class="h-2 rounded-full bg-muted overflow-hidden">
                    <div :class="['h-full rounded-full', item.bg]" :style="`width:${item.pct}%`" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <!-- ──────────────────────── BATCH PERFORMANCE ──────────────────────── -->
      <TabsContent value="batches" class="mt-4 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <p class="text-sm text-muted-foreground">
            {{ batchPerformance.length }} total · {{ batchPerformance.filter(b=>b.status==='active').length }} active · {{ batchPerformance.filter(b=>b.status==='completed').length }} completed
          </p>
          <Button variant="outline" size="sm" class="gap-2" @click="exportReport('batches')">
            <Download class="h-4 w-4" />Export
          </Button>
        </div>

        <!-- Full data table -->
        <Card>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <table class="w-full text-sm min-w-[680px]">
                <thead class="bg-muted/50 border-b border-border">
                  <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Batch</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Students</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Grade</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Attendance</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Sessions</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Completion</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <tr v-for="b in batchPerformance" :key="b.batch" class="hover:bg-muted/30 transition-colors">
                    <td class="px-4 py-3">
                      <p class="font-semibold text-foreground">{{ b.batch }}</p>
                      <p class="text-xs text-muted-foreground">{{ b.course }}</p>
                      <p class="text-[10px] text-muted-foreground">{{ fmtDate(b.start_date) }} → {{ fmtDate(b.end_date) }}</p>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-foreground">{{ b.students }}</td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span :class="['text-base font-black', gradeColor(b.avg_grade)]">{{ b.avg_grade }}%</span>
                        <div class="w-12 h-1.5 rounded-full bg-muted overflow-hidden">
                          <div :class="['h-full rounded-full', gradeBg(b.avg_grade)]" :style="`width:${b.avg_grade}%`" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span :class="['text-sm font-bold', b.attendance_rate >= 80 ? 'text-emerald-600' : 'text-amber-600']">{{ b.attendance_rate }}%</span>
                        <div class="w-12 h-1.5 rounded-full bg-muted overflow-hidden">
                          <div :class="['h-full rounded-full', b.attendance_rate >= 80 ? 'bg-emerald-500' : 'bg-amber-400']" :style="`width:${b.attendance_rate}%`" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center text-xs">
                      <span class="font-bold text-foreground">{{ b.sessions_done }}</span>
                      <span class="text-muted-foreground">/{{ b.sessions_total }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span v-if="b.completion_rate != null" :class="['font-bold text-sm', b.completion_rate >= 80 ? 'text-emerald-600' : 'text-amber-600']">
                        {{ b.completion_rate }}%
                      </span>
                      <span v-else class="text-xs text-muted-foreground italic">In progress</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <Badge :variant="b.status === 'active' ? 'default' : 'outline'" class="text-xs">{{ b.status }}</Badge>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <!-- Visual bar comparisons -->
        <div class="grid sm:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle class="text-sm">Attendance by Batch</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="b in [...batchPerformance].sort((a,b)=>b.attendance_rate-a.attendance_rate)" :key="b.batch">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ b.batch }}</span>
                  <span :class="['font-bold shrink-0', b.attendance_rate >= 80 ? 'text-emerald-600' : 'text-amber-600']">{{ b.attendance_rate }}%</span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div :class="['h-full rounded-full', b.attendance_rate >= 80 ? 'bg-emerald-500' : 'bg-amber-400']" :style="`width:${b.attendance_rate}%`" />
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm">Average Grade by Batch</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="b in [...batchPerformance].sort((a,b)=>b.avg_grade-a.avg_grade)" :key="b.batch">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ b.batch }}</span>
                  <span :class="['font-bold shrink-0', gradeColor(b.avg_grade)]">{{ b.avg_grade }}%</span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div :class="['h-full rounded-full', gradeBg(b.avg_grade)]" :style="`width:${b.avg_grade}%`" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

      <!-- ──────────────────────── COURSE COMPARISON ──────────────────────── -->
      <TabsContent value="courses" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button variant="outline" size="sm" class="gap-2" @click="exportReport('courses')">
            <Download class="h-4 w-4" />Export
          </Button>
        </div>

        <Card>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <table class="w-full text-sm min-w-[560px]">
                <thead class="bg-muted/50 border-b border-border">
                  <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Course</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Batches</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Students</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Grade</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Attendance</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Completion</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <tr v-for="c in courseComparison" :key="c.course" class="hover:bg-muted/30 transition-colors">
                    <td class="px-4 py-3">
                      <p class="font-semibold text-foreground">{{ c.course }}</p>
                      <p class="text-xs text-muted-foreground">{{ c.batches }} batch{{ c.batches !== 1 ? 'es' : '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-foreground">{{ c.batches }}</td>
                    <td class="px-4 py-3 text-center font-bold text-foreground">{{ c.students }}</td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span :class="['font-black', gradeColor(c.avg_grade)]">{{ c.avg_grade }}%</span>
                        <div class="w-14 h-1.5 rounded-full bg-muted overflow-hidden">
                          <div :class="['h-full rounded-full', gradeBg(c.avg_grade)]" :style="`width:${c.avg_grade}%`" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span :class="['font-bold text-sm', c.avg_attendance >= 80 ? 'text-emerald-600' : 'text-amber-600']">{{ c.avg_attendance }}%</span>
                        <div class="w-14 h-1.5 rounded-full bg-muted overflow-hidden">
                          <div :class="['h-full rounded-full', c.avg_attendance >= 80 ? 'bg-emerald-500' : 'bg-amber-400']" :style="`width:${c.avg_attendance}%`" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span v-if="c.avg_completion != null" :class="['font-bold text-sm', c.avg_completion >= 80 ? 'text-emerald-600' : 'text-amber-600']">
                        {{ c.avg_completion }}%
                      </span>
                      <span v-else class="text-xs text-muted-foreground italic">Active only</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <div class="grid sm:grid-cols-3 gap-4">
          <Card v-for="metric in [
            { label: 'Avg Grade by Course',      key: 'avg_grade',      bg: 'bg-primary'     },
            { label: 'Avg Attendance by Course', key: 'avg_attendance', bg: 'bg-emerald-500' },
          ]" :key="metric.label">
            <CardHeader><CardTitle class="text-sm">{{ metric.label }}</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="c in [...courseComparison].sort((a,b)=>b[metric.key]-a[metric.key])" :key="c.course">
                <div class="flex justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ c.course }}</span>
                  <span class="font-bold shrink-0" :class="c[metric.key] >= 80 ? 'text-emerald-600' : 'text-amber-600'">{{ c[metric.key] }}%</span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div :class="['h-full rounded-full', metric.bg]" :style="`width:${c[metric.key]}%`" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle class="text-sm">Students per Course</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="c in [...courseComparison].sort((a,b)=>b.students-a.students)" :key="c.course">
                <div class="flex justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ c.course }}</span>
                  <span class="font-bold shrink-0 text-secondary">{{ c.students }}</span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div class="h-full rounded-full bg-secondary" :style="`width:${Math.round(c.students/maxCourseStudents*100)}%`" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

      <!-- ──────────────────────── INSTRUCTORS ──────────────────────── -->
      <TabsContent value="instructors" class="mt-4 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <p class="text-sm text-muted-foreground">Ranked by student average grade</p>
          <Button variant="outline" size="sm" class="gap-2" @click="exportReport('instructors')">
            <Download class="h-4 w-4" />Export
          </Button>
        </div>

        <!-- Leaderboard table -->
        <Card>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <table class="w-full text-sm min-w-[660px]">
                <thead class="bg-muted/50 border-b border-border">
                  <tr>
                    <th class="px-4 py-3 text-xs font-semibold text-muted-foreground text-center w-10">#</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Instructor</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Students</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Grade</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Avg Attendance</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-amber-600">Satisfaction</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Graded</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Batches Done</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <tr
                    v-for="(inst, i) in rankedInstructors"
                    :key="inst.name"
                    class="hover:bg-muted/30 transition-colors"
                    :class="i === 0 ? 'bg-amber-50/60 dark:bg-amber-950/10' : ''"
                  >
                    <td class="px-4 py-3 text-center">
                      <span v-if="i === 0" class="text-lg">🥇</span>
                      <span v-else-if="i === 1" class="text-lg">🥈</span>
                      <span v-else-if="i === 2" class="text-lg">🥉</span>
                      <span v-else class="text-xs text-muted-foreground font-bold">{{ i + 1 }}</span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-3">
                        <Avatar class="h-8 w-8 shrink-0">
                          <AvatarFallback class="text-xs font-bold bg-secondary/10 text-secondary">
                            {{ initials(inst.name) }}
                          </AvatarFallback>
                        </Avatar>
                        <div>
                          <p class="font-semibold text-foreground">{{ inst.name }}</p>
                          <p class="text-xs text-muted-foreground">{{ inst.course }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-foreground">{{ inst.students }}</td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <span :class="['text-base font-black', gradeColor(inst.avg_grade)]">{{ inst.avg_grade }}%</span>
                        <div class="w-12 h-1.5 rounded-full bg-muted overflow-hidden">
                          <div :class="['h-full rounded-full', gradeBg(inst.avg_grade)]" :style="`width:${inst.avg_grade}%`" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span :class="['font-bold text-sm', inst.avg_attendance >= 80 ? 'text-emerald-600' : 'text-amber-600']">
                        {{ inst.avg_attendance }}%
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex items-center justify-center gap-1">
                        <Star class="h-3.5 w-3.5 text-amber-400 fill-amber-400" />
                        <span class="font-bold text-amber-600 text-sm">{{ inst.satisfaction }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center text-foreground font-medium">{{ inst.assignments_graded }}</td>
                    <td class="px-4 py-3 text-center">
                      <span class="font-bold text-foreground">{{ inst.batches_completed }}</span>
                      <span v-if="inst.active_batches > 0" class="text-xs text-muted-foreground ml-1">(+{{ inst.active_batches }})</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <div class="grid sm:grid-cols-2 gap-4">
          <Card>
            <CardHeader><CardTitle class="text-sm">Satisfaction Scores</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="inst in [...instructorMetrics].sort((a,b)=>b.satisfaction-a.satisfaction)" :key="inst.name">
                <div class="flex justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ inst.name }}</span>
                  <span class="font-bold text-amber-600 shrink-0 flex items-center gap-0.5">
                    <Star class="h-3 w-3 fill-amber-400 text-amber-400" />{{ inst.satisfaction }}
                  </span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div class="h-full rounded-full bg-amber-400" :style="`width:${(inst.satisfaction/5)*100}%`" />
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm">Assignments Graded</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="inst in [...instructorMetrics].sort((a,b)=>b.assignments_graded-a.assignments_graded)" :key="inst.name">
                <div class="flex justify-between text-xs mb-1">
                  <span class="font-medium text-foreground truncate pr-2">{{ inst.name }}</span>
                  <span class="font-bold text-secondary shrink-0">{{ inst.assignments_graded }}</span>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                  <div class="h-full rounded-full bg-secondary" :style="`width:${Math.round(inst.assignments_graded/maxGraded*100)}%`" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

      <!-- ──────────────────────── RETENTION ──────────────────────── -->
      <TabsContent value="retention" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button variant="outline" size="sm" class="gap-2" @click="exportReport('retention')">
            <Download class="h-4 w-4" />Export
          </Button>
        </div>

        <!-- Retention KPIs -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <Card>
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Users class="h-3.5 w-3.5" />Enrolled</p>
              <p class="text-3xl font-black text-foreground mt-1">{{ retention.enrolled }}</p>
            </CardContent>
          </Card>
          <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />Completed</p>
              <p class="text-3xl font-black text-emerald-600 mt-1">{{ retention.completed }}</p>
              <p class="text-xs text-muted-foreground">{{ Math.round(retention.completed/retention.enrolled*100) }}% of enrolled</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Clock class="h-3.5 w-3.5" />Still Active</p>
              <p class="text-3xl font-black text-foreground mt-1">{{ retention.still_active }}</p>
            </CardContent>
          </Card>
          <Card class="border-destructive/20 bg-destructive/5">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><UserX class="h-3.5 w-3.5 text-destructive" />Dropped Out</p>
              <p class="text-3xl font-black text-destructive mt-1">{{ retention.dropped }}</p>
              <p class="text-xs text-muted-foreground">{{ Math.round(retention.dropped/retention.enrolled*100) }}% dropout rate</p>
            </CardContent>
          </Card>
        </div>

        <!-- Funnel -->
        <Card>
          <CardHeader><CardTitle class="text-sm">Student Journey Funnel</CardTitle></CardHeader>
          <CardContent class="pt-0 space-y-2 max-w-2xl">
            <div v-for="step in [
              { label: 'Enrolled',     count: retention.enrolled,     pct: 100,  bg: 'bg-secondary' },
              { label: 'Still Active', count: retention.still_active, pct: Math.round(retention.still_active/retention.enrolled*100), bg: 'bg-primary'    },
              { label: 'Completed',    count: retention.completed,    pct: Math.round(retention.completed/retention.enrolled*100),    bg: 'bg-emerald-500' },
              { label: 'Dropped Out',  count: retention.dropped,      pct: Math.round(retention.dropped/retention.enrolled*100),      bg: 'bg-destructive/70' },
            ]" :key="step.label" class="flex items-center gap-3">
              <span class="w-24 text-right text-xs text-muted-foreground shrink-0">{{ step.label }}</span>
              <div class="flex-1 h-7 rounded-md bg-muted overflow-hidden relative">
                <div :class="['h-full rounded-md transition-all', step.bg]" :style="`width:${step.pct}%`" />
                <div class="absolute inset-0 flex items-center justify-between px-2.5">
                  <span class="text-xs font-bold text-white drop-shadow">{{ step.count }}</span>
                  <span class="text-xs font-bold text-white drop-shadow">{{ step.pct }}%</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="grid sm:grid-cols-2 gap-4">
          <!-- Grade dist -->
          <Card>
            <CardHeader><CardTitle class="text-sm">Grade Distribution</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-3">
              <div v-for="item in [
                { label: 'A  (80–100%)', pct: retention.grade_dist.a,    bg: 'bg-emerald-500', text: 'text-emerald-600' },
                { label: 'B  (60–79%)',  pct: retention.grade_dist.b,    bg: 'bg-primary',     text: 'text-primary'    },
                { label: 'C  (50–59%)',  pct: retention.grade_dist.c,    bg: 'bg-amber-400',   text: 'text-amber-600'  },
                { label: 'Fail (<50%)',  pct: retention.grade_dist.fail, bg: 'bg-destructive', text: 'text-destructive' },
              ]" :key="item.label">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="text-muted-foreground font-mono w-24">{{ item.label }}</span>
                  <span :class="['font-bold', item.text]">{{ item.pct }}% of students</span>
                </div>
                <div class="h-2.5 rounded-full bg-muted overflow-hidden">
                  <div :class="['h-full rounded-full', item.bg]" :style="`width:${item.pct}%`" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Dropout reasons -->
          <Card>
            <CardHeader>
              <CardTitle class="text-sm flex items-center gap-2">
                <AlertCircle class="h-4 w-4 text-destructive" />Dropout Reasons
              </CardTitle>
            </CardHeader>
            <CardContent class="pt-0 space-y-1">
              <div
                v-for="r in retention.dropout_reasons"
                :key="r.reason"
                class="flex items-center justify-between py-2 border-b border-border/50 last:border-0"
              >
                <span class="text-sm text-muted-foreground">{{ r.reason }}</span>
                <div class="flex items-center gap-3 shrink-0">
                  <div class="w-20 h-1.5 rounded-full bg-muted overflow-hidden">
                    <div
                      class="h-full rounded-full bg-destructive/60"
                      :style="`width:${Math.round(r.count/retention.dropped*100)}%`"
                    />
                  </div>
                  <span class="text-xs font-bold text-destructive w-5 text-right">{{ r.count }}</span>
                </div>
              </div>
              <p class="text-xs text-muted-foreground pt-2 border-t border-border">
                Total dropouts: <strong class="text-destructive">{{ retention.dropped }}</strong> out of {{ retention.enrolled }} enrolled
              </p>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

    </Tabs>
  </div>
  </DashboardLayout>
</template>