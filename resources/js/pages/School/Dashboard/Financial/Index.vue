<script setup>

import { ref, computed } from 'vue'
import {
  Wallet, 
  //TrendingUp, 
  ArrowUpRight, ArrowDownRight,
  DollarSign, Users, 
  //BarChart2, 
  Calendar, 
  //BookOpen,
  CheckCircle2, Clock, Download, AlertCircle,
  //ChevronDown, 
  ChevronRight, 
  //RefreshCw, FileText,
  User, 
  //Award,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }  from '@/components/ui/button'
import { Badge }   from '@/components/ui/badge'
// import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const activeTab    = ref('overview')
const periodFilter = ref('6months')

// ─── Mock data ────────────────────────────────────────────────────────────────
// TODO: replace all refs below with defineProps({ summary, monthly, by_course, by_batch, instructor_payments, payouts })

const summary = ref({
  gross: 3560000, platform_fee: 356000, net: 3204000,
  this_month_gross: 560000, this_month_net: 504000,
  last_month_net: 432000,
  total_students: 173, active_students: 67,
  total_batches: 7, completed_batches: 2,
})

const monthly = ref([
  { month:'Oct',  gross:260000, net:234000, platform_fee:26000, students:13, enrollments:13 },
  { month:'Nov',  gross:440000, net:396000, platform_fee:44000, students:22, enrollments:22 },
  { month:'Dec',  gross:340000, net:306000, platform_fee:34000, students:17, enrollments:17 },
  { month:'Jan',  gross:480000, net:432000, platform_fee:48000, students:24, enrollments:24 },
  { month:'Feb',  gross:480000, net:432000, platform_fee:48000, students:24, enrollments:24 },
  { month:'Mar',  gross:560000, net:504000, platform_fee:56000, students:28, enrollments:28 },
])

const byCourse = ref([
  { course:'Full Stack Web Development', batches:3, students:72,  gross:1440000, net:1296000, platform_fee:144000 },
  { course:'Data Science & Analytics',   batches:2, students:43,  gross: 860000, net: 774000, platform_fee: 86000 },
  { course:'UI/UX Design Fundamentals',  batches:2, students:35,  gross: 700000, net: 630000, platform_fee: 70000 },
  { course:'Microsoft Excel Mastery',    batches:1, students:33,  gross: 660000, net: 594000, platform_fee: 66000 },
])

const byBatch = ref([
  { batch:'Web Dev Batch 3',      course:'Full Stack',   status:'active',    students:24, gross:480000, net:432000, platform_fee:48000, start_date:'2026-01-12', end_date:'2026-04-06' },
  { batch:'Data Science Batch 1', course:'Data Science', status:'active',    students:25, gross:500000, net:450000, platform_fee:50000, start_date:'2026-01-20', end_date:'2026-04-14' },
  { batch:'UI/UX Batch 2',        course:'UI/UX',        status:'active',    students:18, gross:360000, net:324000, platform_fee:36000, start_date:'2026-02-03', end_date:'2026-05-05' },
  { batch:'Excel Mastery Batch 1',course:'Excel',        status:'completed', students:33, gross:660000, net:594000, platform_fee:66000, start_date:'2025-10-01', end_date:'2025-11-26' },
  { batch:'Web Dev Batch 2',      course:'Full Stack',   status:'completed', students:22, gross:440000, net:396000, platform_fee:44000, start_date:'2025-09-01', end_date:'2025-12-01' },
])

const instructorPayments = ref([
  { instructor:'Mr. Chidi Okeke',  batches:'Web Dev Batch 2, Web Dev Batch 3', total_owed:80000,  total_paid:40000, pending:40000 },
  { instructor:'Ms. Kemi Adebayo', batches:'Data Science Batch 1',             total_owed:50000,  total_paid:0,     pending:50000 },
  { instructor:'Mr. Tayo Bello',   batches:'UI/UX Batch 1, UI/UX Batch 2',     total_owed:90000,  total_paid:90000, pending:0     },
  { instructor:'Mrs. Ada Johnson', batches:'Excel Mastery Batch 1',            total_owed:50000,  total_paid:50000, pending:0     },
])

const payouts = ref([
  { id:'pyo-001', date:'2026-03-01', amount:432000, status:'completed', reference:'PAY-BSA-2603001', batch:'Web Dev Batch 2 (completed)'    },
  { id:'pyo-002', date:'2026-02-01', amount:594000, status:'completed', reference:'PAY-BSA-2602001', batch:'Excel Mastery Batch 1 (completed)' },
  { id:'pyo-003', date:'2026-04-06', amount:432000, status:'pending',   reference:'PAY-BSA-2604001', batch:'Web Dev Batch 3 (active)'        },
  { id:'pyo-004', date:'2026-04-14', amount:450000, status:'pending',   reference:'PAY-BSA-2604002', batch:'Data Science Batch 1 (active)'   },
])

// ─── Computed ─────────────────────────────────────────────────────────────────
const monthGrowth = computed(() => {
  if (!summary.value.last_month_net) return 0
  return Math.round(((summary.value.this_month_net - summary.value.last_month_net) / summary.value.last_month_net) * 100)
})
const maxNet = computed(() => Math.max(...monthly.value.map(m => m.net)))
const totalInstructorPending = computed(() => instructorPayments.value.reduce((s, i) => s + i.pending, 0))

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fmt(v) {
  if (!v) return '₦0'
  if (v >= 1000000) return '₦' + (v / 1000000).toFixed(2) + 'M'
  return '₦' + v.toLocaleString('en-NG')
}
function fmtDate(iso) {
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}
function barPct(val) {
  return maxNet.value ? Math.round((val / maxNet.value) * 100) : 0
}

async function exportCSV(type) {
  // TODO: window.location.href = route('dashboard.financials.export', { type, period: periodFilter.value })
  toast({ title: 'Export started', description: `${type} report CSV is downloading...` })
}
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Revenue & Financials</h1>
        <p class="text-sm text-muted-foreground mt-1">Complete income breakdown, payout history, and instructor payment tracking.</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="periodFilter">
          <SelectTrigger class="w-36 h-9 text-sm"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="3months">Last 3 months</SelectItem>
            <SelectItem value="6months">Last 6 months</SelectItem>
            <SelectItem value="12months">Last year</SelectItem>
            <SelectItem value="all">All time</SelectItem>
          </SelectContent>
        </Select>
        <Button variant="outline" size="sm" class="gap-2" @click="exportCSV('full')">
          <Download class="h-4 w-4" />Export CSV
        </Button>
      </div>
    </div>

    <!-- Revenue model explainer -->
    <div class="flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
      <DollarSign class="h-5 w-5 text-primary shrink-0 mt-0.5" />
      <div class="text-xs">
        <p class="font-semibold text-foreground">How your revenue works</p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-muted-foreground">
          <span class="font-medium text-foreground">Student pays</span>
          <ChevronRight class="h-3 w-3 shrink-0" />
          <span>Paystack processes</span>
          <ChevronRight class="h-3 w-3 shrink-0" />
          <span>Platform receives gross</span>
          <ChevronRight class="h-3 w-3 shrink-0" />
          <span class="text-destructive font-medium">Platform keeps 10%</span>
          <ChevronRight class="h-3 w-3 shrink-0" />
          <span class="text-emerald-600 font-bold">Your school gets 90%</span>
        </div>
        <p class="mt-1 text-muted-foreground">Instructor salaries are separate — you pay them directly and track agreements on this page.</p>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Wallet class="h-3.5 w-3.5 text-emerald-600" />Total Net Revenue
          </p>
          <p class="text-2xl font-black text-emerald-600 mt-1">{{ fmt(summary.net) }}</p>
          <p class="text-xs text-muted-foreground">after {{ fmt(summary.platform_fee) }} platform fee</p>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Calendar class="h-3.5 w-3.5" />This Month (Net)
          </p>
          <p class="text-2xl font-black text-foreground mt-1">{{ fmt(summary.this_month_net) }}</p>
          <div class="flex items-center gap-1 text-xs mt-0.5" :class="monthGrowth >= 0 ? 'text-emerald-600' : 'text-destructive'">
            <component :is="monthGrowth >= 0 ? ArrowUpRight : ArrowDownRight" class="h-3.5 w-3.5" />
            {{ Math.abs(monthGrowth) }}% vs last month
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Users class="h-3.5 w-3.5" />Paying Students
          </p>
          <p class="text-2xl font-black text-foreground mt-1">{{ summary.total_students }}</p>
          <p class="text-xs text-muted-foreground">{{ summary.active_students }} currently active</p>
        </CardContent>
      </Card>

      <Card :class="totalInstructorPending > 0 ? 'border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800' : ''">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <User class="h-3.5 w-3.5" :class="totalInstructorPending > 0 ? 'text-amber-600' : ''" />Instructor Pay Pending
          </p>
          <p class="text-2xl font-black mt-1" :class="totalInstructorPending > 0 ? 'text-amber-600' : 'text-foreground'">
            {{ fmt(totalInstructorPending) }}
          </p>
          <p class="text-xs text-muted-foreground">owed to instructors</p>
        </CardContent>
      </Card>
    </div>

    <Tabs v-model="activeTab">
      <TabsList class="flex-wrap h-auto gap-1">
        <TabsTrigger value="overview">Overview</TabsTrigger>
        <TabsTrigger value="by-course">By Course</TabsTrigger>
        <TabsTrigger value="by-batch">By Batch</TabsTrigger>
        <TabsTrigger value="instructors">Instructor Pay</TabsTrigger>
        <TabsTrigger value="payouts">Payouts</TabsTrigger>
      </TabsList>

      <!-- ═══════ OVERVIEW ═══════ -->
      <TabsContent value="overview" class="mt-4 space-y-4">
        <div class="grid lg:grid-cols-3 gap-4">

          <!-- Bar chart + table -->
          <Card class="lg:col-span-2">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="text-sm">Monthly Net Revenue</CardTitle>
              <Button variant="ghost" size="sm" class="gap-1.5 text-xs h-7" @click="exportCSV('monthly')">
                <Download class="h-3.5 w-3.5" />CSV
              </Button>
            </CardHeader>
            <CardContent class="pt-0">
              <!-- Bar chart -->
              <div class="flex items-end gap-2 h-36">
                <div v-for="m in monthly" :key="m.month" class="flex flex-col items-center gap-1 flex-1 min-w-0">
                  <p class="text-[10px] font-bold text-foreground">{{ fmt(m.net).replace('₦','') }}</p>
                  <!-- Gross background bar -->
                  <div class="w-full rounded-t-md bg-primary/15 relative overflow-hidden" :style="`height:${barPct(m.gross) * 0.9}px; max-height:88px`">
                    <!-- Net fill -->
                    <div class="absolute bottom-0 left-0 right-0 bg-primary rounded-t-md" :style="`height:${(m.net/m.gross)*100}%`" />
                  </div>
                  <p class="text-[10px] text-muted-foreground truncate w-full text-center">{{ m.month }}</p>
                </div>
              </div>
              <!-- Legend -->
              <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary inline-block" />Net (90%)</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary/15 inline-block" />Gross</span>
              </div>
              <!-- Monthly table -->
              <div class="mt-4 rounded-lg border border-border overflow-hidden">
                <table class="w-full text-xs">
                  <thead class="bg-muted/50">
                    <tr>
                      <th class="text-left px-3 py-2 font-semibold text-muted-foreground">Month</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground">Gross</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground text-destructive">−Fee (10%)</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground text-emerald-600">Net</th>
                      <th class="text-right px-3 py-2 font-semibold text-muted-foreground">Students</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-border">
                    <tr v-for="m in monthly" :key="m.month" class="hover:bg-muted/30">
                      <td class="px-3 py-2 font-medium text-foreground">{{ m.month }}</td>
                      <td class="px-3 py-2 text-right text-foreground">{{ fmt(m.gross) }}</td>
                      <td class="px-3 py-2 text-right text-destructive">−{{ fmt(m.platform_fee) }}</td>
                      <td class="px-3 py-2 text-right font-bold text-emerald-600">{{ fmt(m.net) }}</td>
                      <td class="px-3 py-2 text-right text-muted-foreground">{{ m.students }}</td>
                    </tr>
                    <!-- Totals row -->
                    <tr class="bg-muted/50 font-semibold">
                      <td class="px-3 py-2 text-foreground">Total</td>
                      <td class="px-3 py-2 text-right text-foreground">{{ fmt(monthly.reduce((s,m)=>s+m.gross,0)) }}</td>
                      <td class="px-3 py-2 text-right text-destructive">−{{ fmt(monthly.reduce((s,m)=>s+m.platform_fee,0)) }}</td>
                      <td class="px-3 py-2 text-right text-emerald-600">{{ fmt(monthly.reduce((s,m)=>s+m.net,0)) }}</td>
                      <td class="px-3 py-2 text-right text-muted-foreground">{{ monthly.reduce((s,m)=>s+m.students,0) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          <!-- Summary sidebar -->
          <div class="space-y-4">
            <Card>
              <CardHeader><CardTitle class="text-sm">Revenue Split</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-3">
                <div>
                  <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-muted-foreground">Your school (90%)</span>
                    <span class="font-bold text-emerald-600">{{ fmt(summary.net) }}</span>
                  </div>
                  <div class="h-3 rounded-full bg-muted overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500" style="width:90%" />
                  </div>
                </div>
                <div>
                  <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-muted-foreground">Platform fee (10%)</span>
                    <span class="font-bold text-destructive">{{ fmt(summary.platform_fee) }}</span>
                  </div>
                  <div class="h-3 rounded-full bg-muted overflow-hidden">
                    <div class="h-full rounded-full bg-destructive/60" style="width:10%" />
                  </div>
                </div>
                <div class="border-t border-border pt-2">
                  <div class="flex justify-between text-xs">
                    <span class="font-semibold text-foreground">Total Gross</span>
                    <span class="font-bold text-foreground">{{ fmt(summary.gross) }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle class="text-sm">Batch Status</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-muted-foreground">Active batches</span><span class="font-bold text-primary">{{ summary.total_batches - summary.completed_batches }}</span></div>
                <div class="flex justify-between"><span class="text-muted-foreground">Completed</span><span class="font-bold text-emerald-600">{{ summary.completed_batches }}</span></div>
                <div class="flex justify-between"><span class="text-muted-foreground">Active students</span><span class="font-bold">{{ summary.active_students }}</span></div>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <!-- ═══════ BY COURSE ═══════ -->
      <TabsContent value="by-course" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button variant="outline" size="sm" class="gap-2" @click="exportCSV('by-course')"><Download class="h-4 w-4" />Export</Button>
        </div>
        <Card>
          <CardContent class="p-0">
            <table class="w-full text-sm">
              <thead class="bg-muted/50 border-b border-border">
                <tr>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground text-xs">Course</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs">Batches</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs">Students</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs">Gross</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs text-destructive">Fee (10%)</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs text-emerald-600">Net</th>
                  <th class="px-4 py-3 text-xs text-muted-foreground font-semibold">Share</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="c in byCourse" :key="c.course" class="hover:bg-muted/30 transition-colors">
                  <td class="px-4 py-3">
                    <p class="font-medium text-foreground text-sm">{{ c.course }}</p>
                  </td>
                  <td class="px-4 py-3 text-right text-muted-foreground">{{ c.batches }}</td>
                  <td class="px-4 py-3 text-right text-muted-foreground">{{ c.students }}</td>
                  <td class="px-4 py-3 text-right text-foreground font-medium">{{ fmt(c.gross) }}</td>
                  <td class="px-4 py-3 text-right text-destructive">−{{ fmt(c.platform_fee) }}</td>
                  <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ fmt(c.net) }}</td>
                  <td class="px-4 py-3 w-32">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
                        <div class="h-full rounded-full bg-primary" :style="`width:${Math.round(c.net/summary.net*100)}%`" />
                      </div>
                      <span class="text-xs text-muted-foreground w-7 text-right">{{ Math.round(c.net/summary.net*100) }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- ═══════ BY BATCH ═══════ -->
      <TabsContent value="by-batch" class="mt-4 space-y-4">
        <div class="flex justify-end">
          <Button variant="outline" size="sm" class="gap-2" @click="exportCSV('by-batch')"><Download class="h-4 w-4" />Export</Button>
        </div>
        <Card>
          <CardContent class="p-0">
            <table class="w-full text-sm">
              <thead class="bg-muted/50 border-b border-border">
                <tr>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground text-xs">Batch</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground text-xs hidden md:table-cell">Dates</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs">Students</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs">Gross</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs text-destructive">Fee</th>
                  <th class="text-right px-4 py-3 font-semibold text-muted-foreground text-xs text-emerald-600">Net</th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground text-xs">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="b in byBatch" :key="b.batch" class="hover:bg-muted/30 transition-colors">
                  <td class="px-4 py-3">
                    <p class="font-medium text-foreground">{{ b.batch }}</p>
                    <p class="text-xs text-muted-foreground">{{ b.course }}</p>
                  </td>
                  <td class="px-4 py-3 text-xs text-muted-foreground hidden md:table-cell whitespace-nowrap">
                    {{ fmtDate(b.start_date) }} → {{ fmtDate(b.end_date) }}
                  </td>
                  <td class="px-4 py-3 text-right text-muted-foreground">{{ b.students }}</td>
                  <td class="px-4 py-3 text-right font-medium text-foreground">{{ fmt(b.gross) }}</td>
                  <td class="px-4 py-3 text-right text-destructive text-xs">−{{ fmt(b.platform_fee) }}</td>
                  <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ fmt(b.net) }}</td>
                  <td class="px-4 py-3 text-center">
                    <Badge :variant="b.status==='active'?'default':'outline'" class="text-xs">{{ b.status }}</Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- ═══════ INSTRUCTOR PAYMENTS ═══════ -->
      <TabsContent value="instructors" class="mt-4 space-y-4">
        <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4 text-xs flex items-start gap-3">
          <AlertCircle class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300">Instructor payments are outside the platform</p>
            <p class="text-amber-700 dark:text-amber-400 mt-0.5">You pay instructors directly via bank transfer. Use this tracker to record what's been paid and what's still owed. Mark payments as done on the Instructors page.</p>
          </div>
        </div>

        <!-- Summary row -->
        <div class="grid grid-cols-3 gap-4">
          <Card>
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium">Total Owed (All Time)</p>
              <p class="text-xl font-black text-foreground mt-1">{{ fmt(instructorPayments.reduce((s,i)=>s+i.total_owed,0)) }}</p>
            </CardContent>
          </Card>
          <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium">Total Paid</p>
              <p class="text-xl font-black text-emerald-600 mt-1">{{ fmt(instructorPayments.reduce((s,i)=>s+i.total_paid,0)) }}</p>
            </CardContent>
          </Card>
          <Card class="border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium">Still Pending</p>
              <p class="text-xl font-black text-amber-600 mt-1">{{ fmt(totalInstructorPending) }}</p>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardContent class="p-0">
            <table class="w-full text-sm">
              <thead class="bg-muted/50 border-b border-border">
                <tr>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Instructor</th>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-muted-foreground hidden md:table-cell">Batches</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-muted-foreground">Total Owed</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-emerald-600 font-semibold">Paid</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-amber-600 font-semibold">Pending</th>
                  <th class="px-4 py-3 text-xs font-semibold text-muted-foreground">Progress</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="inst in instructorPayments" :key="inst.instructor" class="hover:bg-muted/30">
                  <td class="px-4 py-3">
                    <p class="font-semibold text-foreground">{{ inst.instructor }}</p>
                  </td>
                  <td class="px-4 py-3 text-xs text-muted-foreground hidden md:table-cell max-w-xs truncate">{{ inst.batches }}</td>
                  <td class="px-4 py-3 text-right font-medium text-foreground">{{ fmt(inst.total_owed) }}</td>
                  <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ fmt(inst.total_paid) }}</td>
                  <td class="px-4 py-3 text-right font-bold" :class="inst.pending > 0 ? 'text-amber-600' : 'text-muted-foreground'">
                    {{ inst.pending > 0 ? fmt(inst.pending) : '—' }}
                  </td>
                  <td class="px-4 py-3 w-28">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
                        <div class="h-full rounded-full"
                          :class="inst.total_paid >= inst.total_owed ? 'bg-emerald-500' : 'bg-amber-400'"
                          :style="`width:${inst.total_owed ? Math.round(inst.total_paid/inst.total_owed*100) : 0}%`"
                        />
                      </div>
                      <span class="text-xs text-muted-foreground w-8 text-right shrink-0">
                        {{ inst.total_owed ? Math.round(inst.total_paid/inst.total_owed*100) : 0 }}%
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- ═══════ PAYOUTS ═══════ -->
      <TabsContent value="payouts" class="mt-4 space-y-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="rounded-lg border border-border bg-muted/30 p-3 text-xs text-muted-foreground max-w-lg">
            Payouts represent the 90% net revenue transferred to your school bank account after each batch completes. The platform processes these automatically after batch close.
          </div>
          <Button variant="outline" size="sm" class="gap-2" @click="exportCSV('payouts')">
            <Download class="h-4 w-4" />Export
          </Button>
        </div>

        <!-- Payout summary -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1"><CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />Completed</p>
              <p class="text-xl font-black text-emerald-600 mt-1">{{ fmt(payouts.filter(p=>p.status==='completed').reduce((s,p)=>s+p.amount,0)) }}</p>
              <p class="text-xs text-muted-foreground">{{ payouts.filter(p=>p.status==='completed').length }} payouts</p>
            </CardContent>
          </Card>
          <Card class="border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium flex items-center gap-1"><Clock class="h-3.5 w-3.5 text-amber-600" />Pending</p>
              <p class="text-xl font-black text-amber-600 mt-1">{{ fmt(payouts.filter(p=>p.status==='pending').reduce((s,p)=>s+p.amount,0)) }}</p>
              <p class="text-xs text-muted-foreground">{{ payouts.filter(p=>p.status==='pending').length }} batches active</p>
            </CardContent>
          </Card>
          <Card class="col-span-2 sm:col-span-1">
            <CardContent class="p-4">
              <p class="text-xs text-muted-foreground font-medium">Total Expected</p>
              <p class="text-xl font-black text-foreground mt-1">{{ fmt(payouts.reduce((s,p)=>s+p.amount,0)) }}</p>
              <p class="text-xs text-muted-foreground">all batches combined</p>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Batch</TableHead>
                  <TableHead class="hidden sm:table-cell">Reference</TableHead>
                  <TableHead class="text-right">Amount</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="p in payouts" :key="p.id">
                  <TableCell class="text-sm whitespace-nowrap">{{ fmtDate(p.date) }}</TableCell>
                  <TableCell class="text-sm text-muted-foreground">{{ p.batch }}</TableCell>
                  <TableCell class="text-xs font-mono text-muted-foreground hidden sm:table-cell">{{ p.reference }}</TableCell>
                  <TableCell class="text-right font-bold" :class="p.status==='completed'?'text-emerald-600':'text-amber-600'">{{ fmt(p.amount) }}</TableCell>
                  <TableCell>
                    <Badge :variant="p.status==='completed'?'outline':'secondary'"
                      :class="p.status==='completed'?'text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 gap-1':'text-amber-600 gap-1'"
                      class="text-xs gap-1"
                    >
                      <CheckCircle2 v-if="p.status==='completed'" class="h-3 w-3" />
                      <Clock v-else class="h-3 w-3" />
                      {{ p.status==='completed'?'Paid out':'Pending' }}
                    </Badge>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </TabsContent>

    </Tabs>
  </div>
  </DashboardLayout>
</template>