<script setup>

import { ref } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
  Plus, Video, Radio, Clock, 
  Users, ExternalLink, Zap, RefreshCw, Search,
} from 'lucide-vue-next'
import { Card, CardContent }           from '@/components/ui/card'
import { Button }                      from '@/components/ui/button'
import { Input }                       from '@/components/ui/input'
import { Badge }                       from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription,
} from '@/components/ui/sheet'
import { toast } from 'vue-sonner'
import { useLiveSessionsManager, SESSION_STATUS } from '@/composables/useLiveSessionsManager'
import CreateSessionDialog from '@/components/Dashboard/School/LiveSessions/CreateSessionDialog.vue'
import AttendanceTracker from '@/components/Dashboard/School/LiveSessions/AttendanceTracker.vue'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const {
  filteredSessions, liveNow, statusCounts,
  search, filterStatus,
  formatTime, attendanceRate,
  createSession, goLive, endSession,
} = useLiveSessionsManager()


const showCreate     = ref(false)
const showAttendance = ref(false)
const attendanceSess = ref(null)
const isGoingLive    = ref(null)

async function handleGoLive(session) {
  isGoingLive.value = session.id
  await goLive(session.id)
  toast({ title: 'Session is LIVE!', description: 'Students can now join.' })
  isGoingLive.value = null
}

async function handleEnd(session) {
  await endSession(session.id, null)
  toast({ title: 'Session ended' })
}

function openAttendance(session) {
  attendanceSess.value = session
  showAttendance.value = true
}

// const statusIcon = { scheduled: Clock, live: Radio, completed: CheckCircle2, cancelled: XCircle }
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Live Sessions</h1>
        <p class="text-sm text-muted-foreground mt-1">Manage your scheduled classes and mark attendance.</p>
      </div>
      <Button class="gap-2" @click="showCreate = true">
        <Plus class="h-4 w-4" />Schedule Session
      </Button>
    </div>

    <!-- Live now banner -->
    <div v-if="liveNow" class="flex items-center gap-4 rounded-xl border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30 p-4">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500">
        <Radio class="h-5 w-5 text-white animate-pulse" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-400">🔴 LIVE NOW</p>
        <p class="text-xs text-emerald-700 dark:text-emerald-500 truncate">{{ liveNow.title }}</p>
      </div>
      <div class="flex gap-2 shrink-0">
        <Button size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 text-xs" as-child>
          <a :href="liveNow.meet_link" target="_blank"><ExternalLink class="h-3.5 w-3.5" />Join</a>
        </Button>
        <Button variant="outline" size="sm" class="gap-1.5 text-xs" @click="openAttendance(liveNow)">
          <Users class="h-3.5 w-3.5" />Attendance
        </Button>
        <Button variant="outline" size="sm" class="text-xs" @click="handleEnd(liveNow)">End</Button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3">
      <Tabs :model-value="filterStatus" @update:model-value="filterStatus = $event">
        <TabsList>
          <TabsTrigger value="all">All <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ statusCounts.all }}</Badge></TabsTrigger>
          <TabsTrigger value="scheduled">Upcoming</TabsTrigger>
          <TabsTrigger value="completed">Past</TabsTrigger>
        </TabsList>
      </Tabs>
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search sessions..." class="pl-9" />
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!filteredSessions.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <Video class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">No sessions found</p>
      <Button class="mt-4 gap-2" size="sm" @click="showCreate = true"><Plus class="h-4 w-4" />Schedule Session</Button>
    </div>

    <!-- Sessions list -->
    <div v-else class="space-y-3">
      <Card
        v-for="session in filteredSessions"
        :key="session.id"
        class="group hover:border-primary/20 hover:shadow-sm transition-all"
        :class="session.status === 'live' && 'border-emerald-400'"
      >
        <CardContent class="p-4">
          <div class="flex items-start gap-4">
            <!-- Date block -->
            <div class="flex flex-col items-center w-12 shrink-0 rounded-xl border border-border bg-muted/30 py-2 text-center">
              <p class="text-[10px] text-muted-foreground font-medium uppercase">{{ new Date(session.scheduled_date).toLocaleString('en',{month:'short'}) }}</p>
              <p class="text-xl font-black text-foreground leading-none">{{ new Date(session.scheduled_date).getDate() }}</p>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <span v-if="session.status==='live'" class="flex items-center gap-1 text-xs font-bold text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"/>LIVE</span>
                <p class="text-sm font-semibold text-foreground">{{ session.title }}</p>
                <Badge :variant="SESSION_STATUS[session.status]?.variant" class="text-xs">{{ SESSION_STATUS[session.status]?.label }}</Badge>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                <span class="flex items-center gap-1"><Clock class="h-3 w-3"/>{{ formatTime(session.scheduled_time) }} · {{ session.duration_minutes }}min</span>
                <span class="flex items-center gap-1"><Users class="h-3 w-3"/>{{ session.batch_name }}</span>
              </div>
              <div v-if="session.status==='completed'" class="mt-1 text-xs text-muted-foreground">
                {{ session.total_attendees }}/{{ session.total_enrolled }} attended ({{ attendanceRate(session) }}%)
              </div>
            </div>
            <div class="flex gap-2 shrink-0">
              <Button v-if="session.meet_link && (session.status==='scheduled'||session.status==='live')" variant="outline" size="sm" class="gap-1.5 text-xs h-8" as-child>
                <a :href="session.meet_link" target="_blank"><ExternalLink class="h-3.5 w-3.5"/>Join</a>
              </Button>
              <Button v-if="session.status==='scheduled'" size="sm" class="gap-1.5 text-xs h-8 bg-emerald-600 hover:bg-emerald-700 text-white" :disabled="isGoingLive===session.id" @click="handleGoLive(session)">
                <RefreshCw v-if="isGoingLive===session.id" class="h-3.5 w-3.5 animate-spin"/>
                <Zap v-else class="h-3.5 w-3.5"/>
                {{ isGoingLive===session.id ? '...' : 'Go Live' }}
              </Button>
              <Button v-if="session.status==='completed'" variant="outline" size="sm" class="gap-1.5 text-xs h-8" @click="openAttendance(session)">
                <Users class="h-3.5 w-3.5"/>Attendance
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Create dialog -->
    <CreateSessionDialog :open="showCreate" @update:open="showCreate=$event" @saved="s=>{ createSession(s); showCreate=false; toast({title:'Session scheduled'}) }" />

    <!-- Attendance sheet -->
    <Sheet :open="showAttendance" @update:open="showAttendance=$event">
      <SheetContent class="w-full sm:max-w-lg overflow-y-auto">
        <SheetHeader class="mb-4">
          <SheetTitle>Attendance</SheetTitle>
          <SheetDescription class="text-xs">{{ attendanceSess?.status==='completed' ? 'View records' : 'Mark attendance' }}</SheetDescription>
        </SheetHeader>
        <AttendanceTracker v-if="attendanceSess" :session-id="attendanceSess.id" :readonly="attendanceSess.status==='completed'" @saved="showAttendance=false" />
      </SheetContent>
    </Sheet>

  </div>
  </InstructorLayout>
</template>