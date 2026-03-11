<script setup>

import { ref, computed } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
  MessageSquare, CheckCircle2, 
  //Clock, 
  AlertCircle,
  Search, 
  //ChevronDown, 
  RefreshCw, Send, 
  //Flag,
  User, Baby,
  // Shield, MoreVertical, XCircle,
} from 'lucide-vue-next'
import { Card, CardContent,
  //CardHeader, CardTitle 
  } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Badge }    from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription,
} from '@/components/ui/sheet'
// import {
//   DropdownMenu, DropdownMenuContent, DropdownMenuItem,
//   DropdownMenuSeparator, DropdownMenuTrigger,
// } from '@/components/ui/dropdown-menu'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const search    = ref('')
const filterTab = ref('open')

// 
const complaints = ref([
  {
    id: 'cmp-001', status: 'open', priority: 'high',
    from_type: 'parent',
    from_name: 'Mrs. Grace Nwosu', from_email: 'grace@yahoo.com',
    student_name: 'Emeka Nwosu', batch: 'Web Dev Batch 3',
    subject: 'Instructor not responding to messages',
    message: 'My son Emeka has been sending messages to Mr. Chidi for 3 days without a response. The assignment deadline is tomorrow and he needs clarification on question 4. This is very frustrating.',
    created_at: new Date(Date.now() - 2 * 86400000).toISOString(),
    responses: [],
  },
  {
    id: 'cmp-002', status: 'open', priority: 'medium',
    from_type: 'student',
    from_name: 'Ada Okonkwo', from_email: 'ada@gmail.com',
    student_name: 'Ada Okonkwo', batch: 'Web Dev Batch 3',
    subject: 'Grade seems incorrect for Assignment 2',
    message: 'I believe my grade for Assignment 2 was marked incorrectly. I answered all 10 questions correctly but received 6/10. I have attached my submission for review.',
    created_at: new Date(Date.now() - 5 * 86400000).toISOString(),
    responses: [
      { id: 'r-001', from: 'School Admin', message: "Hi Ada, we have noted your complaint and forwarded it to the instructor. Expect a response within 24 hours.", created_at: new Date(Date.now() - 4 * 86400000).toISOString() }
    ],
  },
  {
    id: 'cmp-003', status: 'resolved', priority: 'low',
    from_type: 'student',
    from_name: 'Timi Adeleke', from_email: 'timi@gmail.com',
    student_name: 'Timi Adeleke', batch: 'Data Science Batch 1',
    subject: 'Cannot access course materials for Module 3',
    message: 'The Module 3 materials are not showing in my dashboard even though we are in week 7.',
    created_at: new Date(Date.now() - 10 * 86400000).toISOString(),
    resolved_at: new Date(Date.now() - 8 * 86400000).toISOString(),
    resolution: 'Module 3 has been unlocked manually. There was a scheduling error in the batch settings which has been corrected.',
    responses: [],
  },
])

// ── Filter
const filtered = computed(() => {
  let list = complaints.value
  if (filterTab.value !== 'all')
    list = list.filter(c => c.status === filterTab.value)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(c =>
    c.from_name.toLowerCase().includes(q) ||
    c.subject.toLowerCase().includes(q) ||
    c.student_name.toLowerCase().includes(q)
  )
  return list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const counts = computed(() => ({
  open:     complaints.value.filter(c => c.status === 'open').length,
  resolved: complaints.value.filter(c => c.status === 'resolved').length,
  all:      complaints.value.length,
}))

// ── Detail sheet ──────────────────────────────────────────────────────────────
const showSheet     = ref(false)
const activeComplaint = ref(null)
const replyText     = ref('')
const isReplying    = ref(false)
const isResolving   = ref(false)

function openComplaint(c) {
  activeComplaint.value = c
  replyText.value       = ''
  showSheet.value       = true
}

async function sendReply() {
  if (!replyText.value.trim() || !activeComplaint.value) return
  isReplying.value = true
  try {
    await new Promise(r => setTimeout(r, 600))
    /**
     * TODO (Laravel 12):
     * router.post(route('dashboard.complaints.respond', activeComplaint.value.id), {
     *   message: replyText.value,
     * }, { preserveScroll: true })
     */
    activeComplaint.value.responses.push({
      id: 'r-' + Date.now(),
      from: 'School Admin',
      message: replyText.value,
      created_at: new Date().toISOString(),
    })
    replyText.value = ''
    toast({ title: 'Reply sent', description: 'The student/parent has been notified by email.' })
  } finally {
    isReplying.value = false
  }
}

async function resolveComplaint() {
  if (!activeComplaint.value) return
  isResolving.value = true
  try {
    await new Promise(r => setTimeout(r, 600))
    // TODO: router.patch(route('dashboard.complaints.resolve', activeComplaint.value.id))
    activeComplaint.value.status = 'resolved'
    activeComplaint.value.resolved_at = new Date().toISOString()
    toast({ title: 'Complaint resolved', description: 'Marked as resolved and student notified.' })
    showSheet.value = false
  } finally {
    isResolving.value = false
  }
}

async function reopenComplaint(c) {
  // TODO: router.patch(route('dashboard.complaints.reopen', c.id))
  c.status = 'open'
  toast({ title: 'Complaint reopened' })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function timeAgo(iso) {
  const diff = Date.now() - new Date(iso).getTime()
  const h = Math.floor(diff / 3600000)
  const d = Math.floor(diff / 86400000)
  if (h < 1) return 'Just now'
  if (h < 24) return `${h}h ago`
  return `${d}d ago`
}
function fmtDate(iso) {
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function initials(name) {
  return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const priorityConfig = {
  high:   { label: 'High',   variant: 'destructive', class: 'text-destructive border-destructive/30 bg-destructive/5' },
  medium: { label: 'Medium', variant: 'secondary',   class: 'text-amber-600 border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800' },
  low:    { label: 'Low',    variant: 'outline',      class: 'text-muted-foreground' },
}
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">Complaints & Support</h1>
      <p class="text-sm text-muted-foreground mt-1">Respond to and resolve student and parent complaints.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
      <Card class="border-destructive/20 bg-destructive/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><AlertCircle class="h-3.5 w-3.5 text-destructive" />Open</p>
          <p class="text-2xl font-bold text-destructive mt-1">{{ counts.open }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />Resolved</p>
          <p class="text-2xl font-bold text-emerald-600 mt-1">{{ counts.resolved }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><MessageSquare class="h-3.5 w-3.5" />Total</p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ counts.all }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3">
      <Tabs :model-value="filterTab" @update:model-value="filterTab = $event">
        <TabsList>
          <TabsTrigger value="open">
            Open
            <Badge v-if="counts.open" variant="destructive" class="ml-1.5 h-4 px-1 text-[10px]">{{ counts.open }}</Badge>
          </TabsTrigger>
          <TabsTrigger value="resolved">Resolved</TabsTrigger>
          <TabsTrigger value="all">All</TabsTrigger>
        </TabsList>
      </Tabs>
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search complaints..." class="pl-9" />
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!filtered.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <MessageSquare class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">No complaints here</p>
      <p class="text-xs text-muted-foreground mt-1">All issues are resolved. 🎉</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <Card
        v-for="c in filtered" :key="c.id"
        class="group cursor-pointer hover:border-primary/20 hover:shadow-sm transition-all"
        @click="openComplaint(c)"
      >
        <CardContent class="p-4">
          <div class="flex items-start gap-3">
            <Avatar class="h-9 w-9 shrink-0">
              <AvatarFallback :class="c.from_type === 'parent' ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary'" class="text-xs font-bold">
                {{ initials(c.from_name) }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-0.5">
                <p class="text-sm font-semibold text-foreground">{{ c.subject }}</p>
                <Badge :class="['text-[10px]', priorityConfig[c.priority].class]">{{ priorityConfig[c.priority].label }}</Badge>
                <Badge v-if="c.status === 'resolved'" variant="outline" class="text-[10px] gap-1 text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20">
                  <CheckCircle2 class="h-2.5 w-2.5" />Resolved
                </Badge>
              </div>
              <div class="flex items-center gap-2 text-xs text-muted-foreground mb-1">
                <component :is="c.from_type === 'parent' ? Baby : User" class="h-3 w-3" />
                <span>{{ c.from_name }}</span>
                <span v-if="c.from_type === 'parent'">· re: {{ c.student_name }}</span>
                <span>· {{ c.batch }}</span>
              </div>
              <p class="text-xs text-muted-foreground line-clamp-2">{{ c.message }}</p>
              <div class="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                <span>{{ timeAgo(c.created_at) }}</span>
                <span v-if="c.responses.length" class="flex items-center gap-1">
                  <MessageSquare class="h-3 w-3" />{{ c.responses.length }} repl{{ c.responses.length === 1 ? 'y' : 'ies' }}
                </span>
              </div>
            </div>
            <Badge v-if="c.status === 'open'" variant="destructive" class="text-[10px] shrink-0">Open</Badge>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Detail sheet -->
    <Sheet :open="showSheet" @update:open="showSheet = $event">
      <SheetContent class="w-full sm:max-w-xl overflow-y-auto flex flex-col gap-0 p-0">
        <SheetHeader class="p-5 border-b border-border">
          <SheetTitle class="text-base">{{ activeComplaint?.subject }}</SheetTitle>
          <SheetDescription class="text-xs">
            From {{ activeComplaint?.from_name }} · {{ activeComplaint?.batch }} · {{ activeComplaint ? timeAgo(activeComplaint.created_at) : '' }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="activeComplaint" class="flex-1 overflow-y-auto p-5 space-y-4">

          <!-- Original message -->
          <div class="rounded-lg border border-border bg-muted/30 p-4">
            <div class="flex items-center gap-2 mb-2">
              <Avatar class="h-7 w-7">
                <AvatarFallback class="text-[10px] font-bold bg-primary/10 text-primary">{{ initials(activeComplaint.from_name) }}</AvatarFallback>
              </Avatar>
              <div>
                <p class="text-xs font-semibold text-foreground">{{ activeComplaint.from_name }}</p>
                <p class="text-[10px] text-muted-foreground">{{ fmtDate(activeComplaint.created_at) }}</p>
              </div>
            </div>
            <p class="text-sm text-muted-foreground leading-relaxed">{{ activeComplaint.message }}</p>
          </div>

          <!-- Thread -->
          <div v-for="r in activeComplaint.responses" :key="r.id" class="rounded-lg border border-primary/20 bg-primary/5 p-4">
            <div class="flex items-center gap-2 mb-2">
              <Avatar class="h-7 w-7">
                <AvatarFallback class="text-[10px] font-bold bg-secondary/10 text-secondary">SA</AvatarFallback>
              </Avatar>
              <div>
                <p class="text-xs font-semibold text-foreground">{{ r.from }}</p>
                <p class="text-[10px] text-muted-foreground">{{ fmtDate(r.created_at) }}</p>
              </div>
            </div>
            <p class="text-sm text-muted-foreground leading-relaxed">{{ r.message }}</p>
          </div>

          <!-- Resolution note -->
          <div v-if="activeComplaint.resolution" class="rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 p-4">
            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 mb-1 flex items-center gap-1.5">
              <CheckCircle2 class="h-3.5 w-3.5" />Resolution
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ activeComplaint.resolution }}</p>
          </div>

          <!-- Reply box (open complaints only) -->
          <div v-if="activeComplaint.status === 'open'" class="space-y-3">
            <Label class="text-sm font-semibold">Reply</Label>
            <Textarea
              v-model="replyText"
              placeholder="Type your response to the student or parent..."
              :rows="4"
            />
            <div class="flex gap-2">
              <Button class="gap-2 flex-1" :disabled="!replyText.trim() || isReplying" @click="sendReply">
                <RefreshCw v-if="isReplying" class="h-4 w-4 animate-spin" />
                <Send v-else class="h-4 w-4" />
                {{ isReplying ? 'Sending...' : 'Send Reply' }}
              </Button>
              <Button variant="outline" class="gap-2" :disabled="isResolving" @click="resolveComplaint">
                <RefreshCw v-if="isResolving" class="h-4 w-4 animate-spin" />
                <CheckCircle2 v-else class="h-4 w-4 text-emerald-600" />
                {{ isResolving ? 'Resolving...' : 'Mark Resolved' }}
              </Button>
            </div>
          </div>

          <!-- Reopen button for resolved -->
          <div v-else class="flex justify-end">
            <Button variant="outline" size="sm" class="gap-2 text-xs" @click="reopenComplaint(activeComplaint); showSheet = false">
              <RefreshCw class="h-3.5 w-3.5" />Reopen Complaint
            </Button>
          </div>

        </div>
      </SheetContent>
    </Sheet>

  </div>
  </DashboardLayout>
</template>