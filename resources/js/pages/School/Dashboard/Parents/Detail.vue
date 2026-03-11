<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Baby, Mail, Phone, MessageCircle, ArrowLeft,
  CheckCircle2, AlertCircle, Clock, Calendar,
  Award, BookOpen, TrendingUp, TrendingDown,
  Send, RefreshCw, Bell, Settings2,
  ExternalLink, User, MessageSquare,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Badge }    from '@/components/ui/badge'
import { Label }    from '@/components/ui/label'
import { Input }    from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useParents } from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const {
  getParentById, initials, fmtDate, formatNaira,
  gradeColor, attendanceColor, sendMessage, updateAlertThresholds, isLoading,
} = useParents()

// ── Load parent (mock: take from URL param; Inertia passes as prop) ───────────
// TODO (Inertia): defineProps({ parent: Object })
// For mock, read id from window.location
const parentId = window.location.pathname.split('/').pop()
const parent   = ref(getParentById(parentId) ?? {
  id: 'par-001', name: 'Mrs. Blessing Eze', email: 'blessing.eze@yahoo.com',
  phone: '+234 803 999 0000', whatsapp: '+234 803 999 0000',
  joined_at: '2026-01-20', children: [], message_history: [],
})

const activeChildTab  = ref(parent.value.children[0]?.id ?? '')
const activeSection   = ref('performance')

// ── Per-child computed ────────────────────────────────────────────────────────
const activeChild = computed(() =>
  parent.value.children.find(c => c.id === activeChildTab.value) ?? parent.value.children[0]
)

function childHasConcern(c) {
  return c.overall_grade < 60 || c.attendance_rate < 60
}

// ── Message thread ────────────────────────────────────────────────────────────
const newMessage  = ref('')
const isSending   = ref(false)

async function submitMessage() {
  if (!newMessage.value.trim()) return
  isSending.value = true
  const result = await sendMessage(parent.value.id, newMessage.value)
  isSending.value = false
  if (result.success) {
    newMessage.value = ''
    toast({ title: 'Message sent', description: `${parent.value.name} has been notified by email.` })
  }
}

function fmtTime(iso) {
  return new Date(iso).toLocaleString('en-NG', {
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
  })
}

// ── Alert thresholds ──────────────────────────────────────────────────────────
const thresholds = ref({
  grade_drop:          true,
  grade_threshold:     70,
  attendance_drop:     true,
  attendance_threshold: 75,
  weekly_digest:       true,
  assignment_graded:   true,
  session_missed:      true,
})

const isSavingThresholds = ref(false)

async function saveThresholds() {
  isSavingThresholds.value = true
  const result = await updateAlertThresholds(parent.value.id, thresholds.value)
  isSavingThresholds.value = false
  if (result.success) {
    toast({ title: 'Alert settings saved', description: 'Parent will be notified based on these thresholds.' })
  }
}
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Back button -->
    <Button variant="ghost" size="sm" class="gap-2 text-muted-foreground -ml-2" @click="router.visit('/dashboard/parents')">
      <ArrowLeft class="h-4 w-4" />All Parents
    </Button>

    <!-- Parent header card -->
    <Card>
      <CardContent class="p-5">
        <div class="flex items-start gap-4 flex-wrap">
          <Avatar class="h-14 w-14 shrink-0">
            <AvatarFallback class="text-lg font-black bg-secondary/10 text-secondary">
              {{ initials(parent.name) }}
            </AvatarFallback>
          </Avatar>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <h1 class="text-xl font-bold text-foreground">{{ parent.name }}</h1>
              <Badge variant="secondary" class="text-xs gap-1">
                <Baby class="h-3 w-3" />Parent Account
              </Badge>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted-foreground mt-1">
              <span class="flex items-center gap-1.5"><Mail class="h-3.5 w-3.5" />{{ parent.email }}</span>
              <span class="flex items-center gap-1.5"><Phone class="h-3.5 w-3.5" />{{ parent.phone }}</span>
              <span class="flex items-center gap-1.5"><Calendar class="h-3.5 w-3.5" />Joined {{ fmtDate(parent.joined_at) }}</span>
              <span class="flex items-center gap-1.5">
                <Baby class="h-3.5 w-3.5 text-secondary" />
                {{ parent.children.length }} child{{ parent.children.length !== 1 ? 'ren' : '' }} linked
              </span>
            </div>
          </div>
          <div class="flex gap-2 shrink-0">
            <Button variant="outline" size="sm" class="gap-2"
              @click="activeSection = 'messages'; $nextTick(() => document.getElementById('msg-input')?.focus())"
            >
              <MessageCircle class="h-4 w-4" />Message
            </Button>
            <a :href="`https://wa.me/${parent.whatsapp?.replace(/\D/g,'')}`" target="_blank">
              <Button variant="outline" size="sm" class="gap-2 text-emerald-600 border-emerald-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/20">
                <ExternalLink class="h-4 w-4" />WhatsApp
              </Button>
            </a>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Children tabs (if multiple) -->
    <div v-if="parent.children.length > 1">
      <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Linked Children</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="child in parent.children"
          :key="child.id"
          class="flex items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-medium transition-all focus:outline-none"
          :class="activeChildTab === child.id
            ? 'border-secondary bg-secondary/10 text-secondary shadow-sm'
            : 'border-border text-muted-foreground hover:border-secondary/30 hover:bg-secondary/5'"
          @click="activeChildTab = child.id"
        >
          <Baby class="h-3.5 w-3.5 shrink-0" />
          {{ child.name }}
          <Badge
            v-if="childHasConcern(child)"
            variant="destructive"
            class="text-[9px] h-4 px-1"
          >⚠</Badge>
        </button>
      </div>
    </div>

    <!-- Child detail -->
    <div v-if="activeChild" class="space-y-4">

      <!-- Child header -->
      <Card :class="childHasConcern(activeChild) ? 'border-destructive/20 bg-destructive/5' : ''">
        <CardContent class="p-4">
          <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
              <Avatar class="h-10 w-10 shrink-0">
                <AvatarFallback
                  :class="childHasConcern(activeChild)
                    ? 'bg-destructive/10 text-destructive'
                    : activeChild.overall_grade >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                    : 'bg-primary/10 text-primary'"
                  class="font-bold text-sm"
                >{{ initials(activeChild.name) }}</AvatarFallback>
              </Avatar>
              <div>
                <div class="flex items-center gap-2">
                  <p class="text-sm font-bold text-foreground">{{ activeChild.name }}</p>
                  <Badge variant="outline" class="text-xs text-secondary border-secondary/30">
                    Age {{ activeChild.age }}
                  </Badge>
                  <Badge
                    v-if="childHasConcern(activeChild)"
                    variant="destructive"
                    class="text-xs gap-1"
                  ><AlertCircle class="h-3 w-3" />Needs Attention</Badge>
                </div>
                <p class="text-xs text-muted-foreground">
                  {{ activeChild.relationship }} · {{ activeChild.email || 'No student email' }}
                  <span v-if="activeChild.dob"> · DOB: {{ fmtDate(activeChild.dob) }}</span>
                </p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-center shrink-0">
              <div>
                <p :class="['text-xl font-black', gradeColor(activeChild.overall_grade)]">{{ activeChild.overall_grade }}%</p>
                <p class="text-[10px] text-muted-foreground">overall grade</p>
              </div>
              <div>
                <p :class="['text-xl font-black', attendanceColor(activeChild.attendance_rate)]">{{ activeChild.attendance_rate }}%</p>
                <p class="text-[10px] text-muted-foreground">attendance</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Child section tabs -->
      <Tabs v-model="activeSection">
        <TabsList class="flex-wrap h-auto gap-1">
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="enrollments">Enrollments</TabsTrigger>
          <TabsTrigger value="certificates">Certificates</TabsTrigger>
        </TabsList>

        <!-- ── Performance ── -->
        <TabsContent value="performance" class="mt-4 space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <!-- Grade breakdown -->
            <Card>
              <CardHeader><CardTitle class="text-sm">Recent Grades</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-3">
                <div v-if="activeChild.recent_grades?.length" class="divide-y divide-border">
                  <div
                    v-for="g in activeChild.recent_grades"
                    :key="g.title"
                    class="flex items-center justify-between py-2.5"
                  >
                    <div class="min-w-0 pr-3">
                      <p class="text-sm font-medium text-foreground truncate">{{ g.title }}</p>
                      <p class="text-xs text-muted-foreground">{{ fmtDate(g.date) }}</p>
                    </div>
                    <div class="text-right shrink-0">
                      <p :class="['text-sm font-bold', gradeColor(Math.round(g.score/g.out_of*100))]">
                        {{ g.score }}/{{ g.out_of }}
                      </p>
                      <p class="text-xs text-muted-foreground">{{ Math.round(g.score/g.out_of*100) }}%</p>
                    </div>
                  </div>
                </div>
                <p v-else class="text-xs text-muted-foreground text-center py-4">No grades recorded yet.</p>
              </CardContent>
            </Card>

            <!-- Performance summary -->
            <Card>
              <CardHeader><CardTitle class="text-sm">Summary</CardTitle></CardHeader>
              <CardContent class="pt-0 space-y-4">
                <div>
                  <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-muted-foreground">Overall Grade</span>
                    <span :class="['font-bold', gradeColor(activeChild.overall_grade)]">{{ activeChild.overall_grade }}%</span>
                  </div>
                  <Progress :value="activeChild.overall_grade" class="h-2" />
                </div>
                <div>
                  <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-muted-foreground">Attendance Rate</span>
                    <span :class="['font-bold', attendanceColor(activeChild.attendance_rate)]">{{ activeChild.attendance_rate }}%</span>
                  </div>
                  <Progress :value="activeChild.attendance_rate" class="h-2" />
                </div>

                <div v-if="childHasConcern(activeChild)" class="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-xs">
                  <p class="font-semibold text-destructive flex items-center gap-1.5">
                    <AlertCircle class="h-3.5 w-3.5" />Action recommended
                  </p>
                  <ul class="mt-1 space-y-0.5 text-muted-foreground list-disc list-inside">
                    <li v-if="activeChild.overall_grade < 60">Grade below 60% — consider extra tutoring sessions.</li>
                    <li v-if="activeChild.attendance_rate < 60">Attendance below 60% — parent should be notified urgently.</li>
                  </ul>
                </div>
                <div v-else class="rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 p-3 text-xs">
                  <p class="font-semibold text-emerald-600 flex items-center gap-1.5">
                    <CheckCircle2 class="h-3.5 w-3.5" />Performing well
                  </p>
                  <p class="text-muted-foreground mt-0.5">{{ activeChild.name }} is on track in this batch.</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- ── Attendance ── -->
        <TabsContent value="attendance" class="mt-4 space-y-4">
          <Card>
            <CardHeader><CardTitle class="text-sm">Live Class Attendance</CardTitle></CardHeader>
            <CardContent class="pt-0">
              <div v-if="activeChild.recent_attendance?.length" class="divide-y divide-border">
                <div
                  v-for="(a, i) in activeChild.recent_attendance"
                  :key="i"
                  class="flex items-center justify-between py-3"
                >
                  <div>
                    <p class="text-sm font-medium text-foreground">{{ a.session }}</p>
                    <p class="text-xs text-muted-foreground">{{ fmtDate(a.date) }}</p>
                  </div>
                  <Badge
                    :variant="a.status === 'present' ? 'outline' : a.status === 'late' ? 'secondary' : 'destructive'"
                    :class="a.status === 'present'
                      ? 'text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 gap-1'
                      : a.status === 'late'
                      ? 'text-amber-600 gap-1'
                      : 'gap-1'"
                    class="text-xs capitalize gap-1"
                  >
                    <CheckCircle2 v-if="a.status === 'present'" class="h-3 w-3" />
                    <Clock v-else-if="a.status === 'late'" class="h-3 w-3" />
                    <AlertCircle v-else class="h-3 w-3" />
                    {{ a.status }}
                  </Badge>
                </div>
              </div>
              <div v-else class="py-8 text-center text-xs text-muted-foreground">
                No attendance records yet.
              </div>
            </CardContent>
          </Card>

          <!-- Attendance rate card -->
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold">Overall Attendance Rate</p>
                <span :class="['text-xl font-black', attendanceColor(activeChild.attendance_rate)]">
                  {{ activeChild.attendance_rate }}%
                </span>
              </div>
              <Progress :value="activeChild.attendance_rate" class="h-3" />
              <div class="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/20 p-2">
                  <p class="font-bold text-emerald-600">{{ activeChild.recent_attendance?.filter(a=>a.status==='present').length ?? 0 }}</p>
                  <p class="text-muted-foreground">Present</p>
                </div>
                <div class="rounded-lg bg-amber-50 dark:bg-amber-950/20 p-2">
                  <p class="font-bold text-amber-600">{{ activeChild.recent_attendance?.filter(a=>a.status==='late').length ?? 0 }}</p>
                  <p class="text-muted-foreground">Late</p>
                </div>
                <div class="rounded-lg bg-destructive/5 p-2">
                  <p class="font-bold text-destructive">{{ activeChild.recent_attendance?.filter(a=>a.status==='absent').length ?? 0 }}</p>
                  <p class="text-muted-foreground">Absent</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- ── Enrollments ── -->
        <TabsContent value="enrollments" class="mt-4 space-y-3">
          <div v-if="activeChild.enrollments?.length">
            <Card v-for="(e, i) in activeChild.enrollments" :key="i">
              <CardContent class="p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                  <div>
                    <p class="text-sm font-bold text-foreground">{{ e.course }}</p>
                    <p class="text-xs text-muted-foreground">{{ e.batch }}</p>
                  </div>
                  <Badge :variant="e.status === 'active' ? 'default' : 'outline'" class="text-xs shrink-0">
                    {{ e.status }}
                  </Badge>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3 text-xs">
                  <div class="rounded-lg bg-muted/50 p-2">
                    <p class="text-muted-foreground">Paid</p>
                    <p class="font-bold text-emerald-600">{{ formatNaira(e.paid) }}</p>
                  </div>
                  <div class="rounded-lg bg-muted/50 p-2">
                    <p class="text-muted-foreground">Schedule</p>
                    <p class="font-medium text-foreground">{{ e.schedule }}</p>
                  </div>
                  <div class="rounded-lg bg-muted/50 p-2">
                    <p class="text-muted-foreground">Start date</p>
                    <p class="font-medium text-foreground">{{ fmtDate(e.start_date) }}</p>
                  </div>
                  <div class="rounded-lg bg-muted/50 p-2">
                    <p class="text-muted-foreground">End date</p>
                    <p class="font-medium text-foreground">{{ fmtDate(e.end_date) }}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
          <div v-else class="py-10 text-center text-xs text-muted-foreground rounded-xl border border-dashed border-border">
            No enrollments found.
          </div>
        </TabsContent>

        <!-- ── Certificates ── -->
        <TabsContent value="certificates" class="mt-4 space-y-3">
          <div v-if="activeChild.certificates?.length" class="space-y-3">
            <Card v-for="cert in activeChild.certificates" :key="cert.certificate_id">
              <CardContent class="p-4 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/30 shrink-0">
                  <Award class="h-6 w-6" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-foreground">{{ cert.title }}</p>
                  <p class="text-xs text-muted-foreground">{{ cert.batch }} · Grade: {{ cert.grade }}%</p>
                  <p class="text-xs text-muted-foreground">Issued {{ fmtDate(cert.issued_at) }} · ID: {{ cert.certificate_id }}</p>
                </div>
                <Badge variant="outline" class="text-xs text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 shrink-0">
                  Verified
                </Badge>
              </CardContent>
            </Card>
          </div>
          <div v-else class="py-10 text-center rounded-xl border border-dashed border-border">
            <Award class="h-8 w-8 text-muted-foreground/30 mx-auto mb-2" />
            <p class="text-xs text-muted-foreground">No certificates earned yet.</p>
            <p class="text-[10px] text-muted-foreground mt-1">Certificates are issued when a student completes a batch with grade ≥ 60%.</p>
          </div>
        </TabsContent>
      </Tabs>
    </div>

    <!-- ── Message thread + Alert settings ──────────────────────────────────── -->
    <div class="grid lg:grid-cols-2 gap-6">

      <!-- Message thread -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-sm flex items-center gap-2">
            <MessageSquare class="h-4 w-4 text-secondary" />Message Thread
          </CardTitle>
          <p class="text-xs text-muted-foreground">Messages are sent to {{ parent.email }}</p>
        </CardHeader>
        <CardContent class="pt-0 space-y-3">
          <!-- Thread -->
          <div
            v-if="parent.message_history?.length"
            class="space-y-2 max-h-64 overflow-y-auto rounded-lg border border-border p-3 bg-muted/20"
          >
            <div
              v-for="(msg, i) in parent.message_history"
              :key="i"
              class="flex gap-2"
              :class="msg.from === 'school' ? 'flex-row-reverse' : ''"
            >
              <Avatar class="h-7 w-7 shrink-0">
                <AvatarFallback
                  :class="msg.from === 'school'
                    ? 'bg-primary/10 text-primary text-[10px] font-bold'
                    : 'bg-secondary/10 text-secondary text-[10px] font-bold'"
                >
                  {{ msg.from === 'school' ? 'S' : initials(parent.name) }}
                </AvatarFallback>
              </Avatar>
              <div
                class="max-w-[75%] rounded-xl px-3 py-2 text-xs"
                :class="msg.from === 'school'
                  ? 'bg-primary text-primary-foreground rounded-tr-sm'
                  : 'bg-muted text-foreground rounded-tl-sm'"
              >
                <p class="leading-relaxed">{{ msg.text }}</p>
                <p class="text-[10px] mt-1 opacity-70">{{ fmtTime(msg.sent_at) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="rounded-lg border border-dashed border-border p-4 text-center text-xs text-muted-foreground">
            No messages yet. Start the conversation below.
          </div>

          <!-- Compose -->
          <div class="space-y-2">
            <Textarea
              id="msg-input"
              v-model="newMessage"
              placeholder="Write a message to the parent..."
              :rows="3"
            />
            <Button
              class="w-full gap-2"
              :disabled="!newMessage.trim() || isSending"
              @click="submitMessage"
            >
              <RefreshCw v-if="isSending" class="h-4 w-4 animate-spin" />
              <Send v-else class="h-4 w-4" />
              {{ isSending ? 'Sending...' : 'Send Message' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Alert thresholds -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-sm flex items-center gap-2">
            <Bell class="h-4 w-4 text-secondary" />Parent Alert Settings
          </CardTitle>
          <p class="text-xs text-muted-foreground">Configure what notifications {{ parent.name }} receives about their children.</p>
        </CardHeader>
        <CardContent class="pt-0 space-y-4">

          <!-- Grade threshold -->
          <div class="space-y-2">
            <label class="flex items-center justify-between cursor-pointer">
              <div>
                <p class="text-sm font-medium text-foreground">Grade drop alert</p>
                <p class="text-xs text-muted-foreground">Notify when grade falls below threshold</p>
              </div>
              <Checkbox v-model:checked="thresholds.grade_drop" />
            </label>
            <div v-if="thresholds.grade_drop" class="flex items-center gap-2 pl-0">
              <Label class="text-xs text-muted-foreground whitespace-nowrap">Alert if grade drops below</Label>
              <Input
                v-model.number="thresholds.grade_threshold"
                type="number" min="0" max="100"
                class="h-7 w-16 text-xs px-2"
              />
              <span class="text-xs text-muted-foreground">%</span>
            </div>
          </div>

          <!-- Attendance threshold -->
          <div class="space-y-2">
            <label class="flex items-center justify-between cursor-pointer">
              <div>
                <p class="text-sm font-medium text-foreground">Low attendance alert</p>
                <p class="text-xs text-muted-foreground">Notify when attendance falls below threshold</p>
              </div>
              <Checkbox v-model:checked="thresholds.attendance_drop" />
            </label>
            <div v-if="thresholds.attendance_drop" class="flex items-center gap-2 pl-0">
              <Label class="text-xs text-muted-foreground whitespace-nowrap">Alert if attendance drops below</Label>
              <Input
                v-model.number="thresholds.attendance_threshold"
                type="number" min="0" max="100"
                class="h-7 w-16 text-xs px-2"
              />
              <span class="text-xs text-muted-foreground">%</span>
            </div>
          </div>

          <div class="space-y-3 pt-2 border-t border-border">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Email Notifications</p>
            <label
              v-for="(label, key) in {
                weekly_digest:     'Weekly progress digest',
                assignment_graded: 'When assignment is graded',
                session_missed:    'When child misses a session',
              }"
              :key="key"
              class="flex items-center justify-between py-1.5 cursor-pointer"
            >
              <span class="text-sm text-foreground">{{ label }}</span>
              <Checkbox v-model:checked="thresholds[key]" />
            </label>
          </div>

          <Button class="w-full gap-2" :disabled="isSavingThresholds" @click="saveThresholds">
            <RefreshCw v-if="isSavingThresholds" class="h-4 w-4 animate-spin" />
            <Settings2 v-else class="h-4 w-4" />
            {{ isSavingThresholds ? 'Saving...' : 'Save Alert Settings' }}
          </Button>
        </CardContent>
      </Card>

    </div>
  </div>
  </DashboardLayout>
</template>