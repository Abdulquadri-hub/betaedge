<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Users, Search, AlertCircle, TrendingUp,
  Phone, Mail, Baby, ChevronDown, Filter,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription,
} from '@/components/ui/sheet'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useInstructorDashboard, MOCK_INSTRUCTOR_STUDENTS } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const {
  batches, formatTime, fmtDate, gradeColor, initials,
} = useInstructorDashboard()

const students = ref(MOCK_INSTRUCTOR_STUDENTS)
const search      = ref('')
const batchFilter = ref('all')
const perfFilter  = ref('all')

const filtered = computed(() => {
  let list = students.value
  if (batchFilter.value !== 'all') list = list.filter(s => s.batch_id === batchFilter.value)
  if (perfFilter.value === 'struggling') list = list.filter(s => s.grade < 60 || s.attendance_rate < 60)
  if (perfFilter.value === 'top')        list = list.filter(s => s.grade >= 80 && s.attendance_rate >= 80)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(s => s.name.toLowerCase().includes(q) || s.email.toLowerCase().includes(q))
  return list.sort((a, b) => a.name.localeCompare(b.name))
})

const counts = computed(() => ({
  all:       students.value.length,
  struggling: students.value.filter(s => s.grade < 60 || s.attendance_rate < 60).length,
  top:       students.value.filter(s => s.grade >= 80 && s.attendance_rate >= 80).length,
}))

// ── Detail sheet ──────────────────────────────────────────────────────────────
const showDetail  = ref(false)
const activeStudent = ref(null)

function openStudent(s) {
  activeStudent.value = s
  showDetail.value    = true
}
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">My Students</h1>
      <p class="text-sm text-muted-foreground mt-1">Students enrolled in your assigned batches.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Users class="h-3.5 w-3.5" />Total Students</p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ counts.all }}</p>
        </CardContent>
      </Card>
      <Card class="border-destructive/20 bg-destructive/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><AlertCircle class="h-3.5 w-3.5 text-destructive" />Need Support</p>
          <p class="text-2xl font-bold text-destructive mt-1">{{ counts.struggling }}</p>
          <p class="text-xs text-muted-foreground">grade or attendance &lt;60%</p>
        </CardContent>
      </Card>
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><TrendingUp class="h-3.5 w-3.5 text-emerald-600" />Top Performers</p>
          <p class="text-2xl font-bold text-emerald-600 mt-1">{{ counts.top }}</p>
          <p class="text-xs text-muted-foreground">grade &amp; attendance ≥80%</p>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
      <div class="relative flex-1 max-w-sm min-w-48">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search students..." class="pl-9" />
      </div>
      <Select v-model="batchFilter">
        <SelectTrigger class="w-48 h-9 text-sm"><SelectValue placeholder="All Batches" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All Batches</SelectItem>
          <SelectItem v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</SelectItem>
        </SelectContent>
      </Select>
      <Tabs :model-value="perfFilter" @update:model-value="perfFilter = $event">
        <TabsList>
          <TabsTrigger value="all">All</TabsTrigger>
          <TabsTrigger value="struggling">
            Struggling
            <Badge v-if="counts.struggling" variant="destructive" class="ml-1.5 h-4 px-1 text-[10px]">{{ counts.struggling }}</Badge>
          </TabsTrigger>
          <TabsTrigger value="top">Top</TabsTrigger>
        </TabsList>
      </Tabs>
    </div>

    <!-- Empty -->
    <div v-if="!filtered.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <Users class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">No students found</p>
    </div>

    <!-- Student cards -->
    <div v-else class="space-y-2">
      <Card
        v-for="s in filtered" :key="s.id"
        class="group cursor-pointer hover:border-primary/20 hover:shadow-sm transition-all"
        @click="openStudent(s)"
      >
        <CardContent class="p-4 flex items-center gap-3">
          <Avatar class="h-10 w-10 shrink-0">
            <AvatarFallback
              :class="s.grade < 60 || s.attendance_rate < 60
                ? 'bg-destructive/10 text-destructive'
                : s.grade >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                : 'bg-primary/10 text-primary'"
              class="font-bold text-sm"
            >{{ initials(s.name) }}</AvatarFallback>
          </Avatar>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-semibold text-foreground">{{ s.name }}</p>
              <Badge v-if="s.grade < 60 || s.attendance_rate < 60" variant="destructive" class="text-[10px]">Needs Help</Badge>
              <Badge v-else-if="s.grade >= 80 && s.attendance_rate >= 80" variant="outline" class="text-[10px] text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20">Top</Badge>
            </div>
            <p class="text-xs text-muted-foreground">{{ s.batch_name }}</p>
            <div v-if="s.parent" class="flex items-center gap-1 text-[10px] text-muted-foreground mt-0.5">
              <Baby class="h-2.5 w-2.5" />Parent: {{ s.parent.name }}
            </div>
          </div>
          <div class="text-right shrink-0 space-y-0.5">
            <p :class="['text-sm font-bold', gradeColor(s.grade)]">{{ s.grade }}%</p>
            <p class="text-xs text-muted-foreground">{{ s.attendance_rate }}% attend.</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Detail sheet -->
    <Sheet :open="showDetail" @update:open="showDetail = $event">
      <SheetContent class="w-full sm:max-w-sm overflow-y-auto">
        <SheetHeader class="mb-5">
          <SheetTitle class="flex items-center gap-3">
            <Avatar class="h-10 w-10">
              <AvatarFallback :class="activeStudent?.grade < 60 ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'" class="font-bold">
                {{ initials(activeStudent?.name) }}
              </AvatarFallback>
            </Avatar>
            <div>
              <p class="text-base font-bold">{{ activeStudent?.name }}</p>
              <p class="text-xs text-muted-foreground font-normal">{{ activeStudent?.batch_name }}</p>
            </div>
          </SheetTitle>
        </SheetHeader>
        <div v-if="activeStudent" class="space-y-5">

          <!-- Performance -->
          <Card>
            <CardHeader><CardTitle class="text-sm">Performance</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Current Grade</span>
                <span :class="['font-bold', gradeColor(activeStudent.grade)]">{{ activeStudent.grade }}%</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Attendance</span>
                <span :class="['font-bold', activeStudent.attendance_rate < 60 ? 'text-destructive' : 'text-foreground']">{{ activeStudent.attendance_rate }}%</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Status</span>
                <Badge :variant="activeStudent.grade < 60 || activeStudent.attendance_rate < 60 ? 'destructive' : 'default'" class="text-xs">
                  {{ activeStudent.grade < 60 || activeStudent.attendance_rate < 60 ? 'Needs Support' : 'On Track' }}
                </Badge>
              </div>
            </CardContent>
          </Card>

          <!-- Contact -->
          <Card>
            <CardHeader><CardTitle class="text-sm">Contact</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-2 text-sm">
              <div class="flex items-center gap-2 text-muted-foreground">
                <Mail class="h-3.5 w-3.5 shrink-0" />
                <a :href="`mailto:${activeStudent.email}`" class="hover:text-foreground hover:underline truncate">{{ activeStudent.email }}</a>
              </div>
            </CardContent>
          </Card>

          <!-- Parent info -->
          <Card v-if="activeStudent.parent">
            <CardHeader><CardTitle class="text-sm flex items-center gap-2"><Baby class="h-4 w-4 text-secondary" />Parent / Guardian</CardTitle></CardHeader>
            <CardContent class="pt-0 space-y-2 text-sm">
              <p class="font-medium text-foreground">{{ activeStudent.parent.name }}</p>
              <div class="flex items-center gap-2 text-muted-foreground">
                <Phone class="h-3.5 w-3.5 shrink-0" />
                <a :href="`tel:${activeStudent.parent.phone}`" class="hover:text-foreground hover:underline">{{ activeStudent.parent.phone }}</a>
              </div>
              <div class="flex items-center gap-2 text-muted-foreground">
                <Mail class="h-3.5 w-3.5 shrink-0" />
                <a :href="`mailto:${activeStudent.parent.email}`" class="hover:text-foreground hover:underline truncate">{{ activeStudent.parent.email }}</a>
              </div>
            </CardContent>
          </Card>

        </div>
      </SheetContent>
    </Sheet>

  </div>
  </InstructorLayout>
</template>