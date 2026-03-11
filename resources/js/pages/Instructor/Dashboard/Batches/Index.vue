<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  BookOpen, Users, Clock, ChevronRight, TrendingUp,
  CheckCircle2, Calendar, ExternalLink, Search,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { batches, formatNaira, formatTime, fmtDate, gradeColor } = useInstructorDashboard()

const search    = ref('')
const filterTab = ref('all')

const filtered = computed(() => {
  let list = batches.value
  if (filterTab.value !== 'all') list = list.filter(b => b.status === filterTab.value)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(b => b.name.toLowerCase().includes(q) || b.course_name.toLowerCase().includes(q))
  return list
})

const counts = computed(() => ({
  all:       batches.value.length,
  active:    batches.value.filter(b => b.status === 'active').length,
  completed: batches.value.filter(b => b.status === 'completed').length,
}))

const batchStatusCfg = {
  open:      { label: 'Enrolling',   variant: 'default'   },
  active:    { label: 'In Progress', variant: 'secondary' },
  completed: { label: 'Completed',   variant: 'outline'   },
  closed:    { label: 'Closed',      variant: 'outline'   },
}
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">My Batches</h1>
        <p class="text-sm text-muted-foreground mt-1">All batches assigned to you at this school.</p>
      </div>
    </div>

    <!-- Summary tiles -->
    <div class="grid grid-cols-3 gap-4">
      <div v-for="tile in [
        { label:'Active', value:counts.active, color:'text-primary' },
        { label:'Completed', value:counts.completed, color:'text-emerald-600' },
        { label:'Total', value:counts.all, color:'text-foreground' },
      ]" :key="tile.label"
        class="rounded-xl border border-border bg-card p-4"
      >
        <p class="text-xs text-muted-foreground font-medium">{{ tile.label }}</p>
        <p :class="['text-2xl font-bold mt-1', tile.color]">{{ tile.value }}</p>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3">
      <Tabs :model-value="filterTab" @update:model-value="filterTab = $event">
        <TabsList>
          <TabsTrigger value="all">All <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ counts.all }}</Badge></TabsTrigger>
          <TabsTrigger value="active">Active</TabsTrigger>
          <TabsTrigger value="completed">Completed</TabsTrigger>
        </TabsList>
      </Tabs>
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search batches..." class="pl-9" />
      </div>
    </div>

    <div v-if="!filtered.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <BookOpen class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">No batches found</p>
    </div>

    <div v-else class="space-y-4">
      <Card
        v-for="batch in filtered" :key="batch.id"
        class="group cursor-pointer hover:border-primary/20 hover:shadow-sm transition-all"
        @click="router.visit(`/instructor/batches/${batch.id}`)"
      >
        <CardContent class="p-5">
          <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <p class="text-sm font-bold text-foreground group-hover:text-primary transition-colors">{{ batch.name }}</p>
                <Badge :variant="batchStatusCfg[batch.status]?.variant ?? 'outline'" class="text-xs">{{ batchStatusCfg[batch.status]?.label }}</Badge>
              </div>
              <p class="text-xs text-muted-foreground mb-2">{{ batch.course_name }}</p>

              <!-- Progress for active -->
              <div v-if="batch.status === 'active'" class="mb-3">
                <div class="flex justify-between text-xs mb-1">
                  <span class="text-muted-foreground">Week {{ batch.week }}/{{ batch.total_weeks }}</span>
                  <span class="font-medium">{{ Math.round((batch.week / batch.total_weeks) * 100) }}%</span>
                </div>
                <Progress :value="(batch.week / batch.total_weeks) * 100" class="h-1.5" />
              </div>

              <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span class="flex items-center gap-1"><Users class="h-3 w-3" />{{ batch.current_enrollment }} students</span>
                <span class="flex items-center gap-1"><Calendar class="h-3 w-3" />{{ batch.schedule_day }} · {{ formatTime(batch.schedule_time) }}</span>
                <span class="flex items-center gap-1"><Clock class="h-3 w-3" />{{ fmtDate(batch.start_date) }} → {{ fmtDate(batch.end_date) }}</span>
              </div>

              <!-- Pending tasks -->
              <div v-if="batch.pending_grading > 0 || batch.pending_assignments > 0" class="flex gap-2 mt-3">
                <Badge v-if="batch.pending_grading > 0" variant="destructive" class="text-[10px]">{{ batch.pending_grading }} to grade</Badge>
                <Badge v-if="batch.pending_assignments > 0" variant="secondary" class="text-[10px]">{{ batch.pending_assignments }} assignments due</Badge>
              </div>
            </div>

            <!-- Right stats -->
            <div class="text-right shrink-0 space-y-1">
              <div>
                <p :class="['text-lg font-black', gradeColor(batch.avg_grade)]">{{ batch.avg_grade }}%</p>
                <p class="text-[10px] text-muted-foreground">Avg grade</p>
              </div>
              <div>
                <p class="text-sm font-bold text-foreground">{{ batch.attendance_rate }}%</p>
                <p class="text-[10px] text-muted-foreground">Attendance</p>
              </div>
              <ChevronRight class="h-4 w-4 text-muted-foreground ml-auto" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

  </div>
  </InstructorLayout>
</template>