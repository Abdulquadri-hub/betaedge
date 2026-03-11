<script setup>

import { ref, computed } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
  CheckCircle2, XCircle, Clock, Baby, Shield, Wallet, 
  //Search,
  RefreshCw, 
  MessageCircle, CalendarCheck,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
// import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog, DialogContent, DialogDescription,
  DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { useEnrollmentRequests } from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const {
  filteredRequests, isLoading, pendingCount,
  filterStatus, formatNaira, timeAgo,
  approveRequest, rejectRequest,
} = useEnrollmentRequests()


// ─── Status config ────────────────────────────────────────────────────────────
const statusCfg = {
  pending:  { label: 'Pending',  variant: 'secondary',   icon: Clock         },
  approved: { label: 'Approved', variant: 'default',     icon: CheckCircle2  },
  rejected: { label: 'Rejected', variant: 'destructive', icon: XCircle       },
}

const paymentStatusCfg = {
  paid:    { label: 'Paid',    variant: 'default',   icon: CheckCircle2 },
  pending: { label: 'Unpaid',  variant: 'secondary', icon: Clock        },
}

function initials(name) {
  return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// function fmtDate(iso) {
//   if (!iso) return '—'
//   return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
// }

// ─── Approve flow ─────────────────────────────────────────────────────────────
const actioning      = ref(null) // request id being actioned
const showRejectDialog = ref(false)
const rejectTarget     = ref(null)
const rejectReason     = ref('')
const rejectError      = ref('')

async function handleApprove(req) {
  actioning.value = req.id
  try {
    const result = await approveRequest(req.id)
    if (result?.success !== false) {
      toast({
        title: '✅ Enrollment approved',
        description: `${req.student_name} is now enrolled in ${req.batch_name}. Welcome email sent.`,
      })
    }
  } finally {
    actioning.value = null
  }
}

function openReject(req) {
  rejectTarget.value    = req
  rejectReason.value    = ''
  rejectError.value     = ''
  showRejectDialog.value = true
}

async function handleReject() {
  if (!rejectTarget.value) return
  if (!rejectReason.value.trim()) {
    rejectError.value = 'Please provide a reason for rejection.'
    return
  }
  actioning.value = rejectTarget.value.id
  try {
    const result = await rejectRequest(rejectTarget.value.id, rejectReason.value)
    if (result?.success !== false) {
      toast({
        title: 'Enrollment rejected',
        description: rejectTarget.value.payment_status === 'paid'
          ? 'A Paystack refund has been initiated.'
          : 'The student has been notified.',
      })
      showRejectDialog.value = false
    }
  } finally {
    actioning.value = null
  }
}

// Summary counts
const approvedCount = computed(() =>
  filteredRequests.value.filter(r => r.status === 'approved').length
)
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-2xl font-bold text-foreground tracking-tight">Enrollment Requests</h1>
          <Badge v-if="pendingCount > 0" variant="destructive" class="text-xs animate-pulse">
            {{ pendingCount }} pending
          </Badge>
        </div>
        <p class="text-sm text-muted-foreground mt-1">Review and approve or reject student enrollment requests.</p>
      </div>
    </div>

    <!-- Info banner -->
    <div class="flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
      <CalendarCheck class="h-5 w-5 text-primary shrink-0 mt-0.5" />
      <div class="text-xs text-foreground">
        <p class="font-semibold mb-0.5">How enrollment requests work</p>
        <p class="text-muted-foreground">When manual approval is enabled for a course, students who pay will appear here. Approve to confirm their spot, or reject to initiate a refund through Paystack.</p>
      </div>
    </div>

    <!-- Summary stats -->
    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-xl border border-border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-amber-600">{{ pendingCount }}</p>
        <p class="text-xs text-muted-foreground mt-1">Pending</p>
      </div>
      <div class="rounded-xl border border-border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-primary">{{ approvedCount }}</p>
        <p class="text-xs text-muted-foreground mt-1">Approved</p>
      </div>
      <div class="rounded-xl border border-border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-foreground">{{ filteredRequests.length }}</p>
        <p class="text-xs text-muted-foreground mt-1">Total</p>
      </div>
    </div>

    <!-- Tabs -->
    <Tabs :model-value="filterStatus" @update:model-value="filterStatus = $event">
      <TabsList>
        <TabsTrigger value="pending">
          Pending
          <Badge variant="destructive" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ pendingCount }}</Badge>
        </TabsTrigger>
        <TabsTrigger value="approved">Approved</TabsTrigger>
        <TabsTrigger value="rejected">Rejected</TabsTrigger>
        <TabsTrigger value="all">All</TabsTrigger>
      </TabsList>
    </Tabs>

    <!-- Requests list -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-24 bg-muted rounded-xl animate-pulse" />
    </div>

    <div v-else-if="filteredRequests.length === 0" class="py-16 text-center rounded-xl border border-dashed border-border">
      <CheckCircle2 class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">
        {{ filterStatus === 'pending' ? 'No pending requests' : 'No requests found' }}
      </p>
      <p class="text-xs text-muted-foreground mt-1">
        {{ filterStatus === 'pending' ? 'All enrollment requests have been processed.' : 'Try switching tabs.' }}
      </p>
    </div>

    <div v-else class="space-y-3">
      <Card v-for="req in filteredRequests" :key="req.id"
        :class="['transition-all', req.status === 'pending' && 'border-amber-200 dark:border-amber-800']"
      >
        <CardContent class="p-5">
          <div class="flex items-start gap-4">

            <!-- Avatar -->
            <Avatar class="h-10 w-10 shrink-0">
              <AvatarFallback :class="['text-xs font-bold', req.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400' : 'bg-primary/10 text-primary']">
                {{ initials(req.student_name) }}
              </AvatarFallback>
            </Avatar>

            <!-- Main info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <p class="text-sm font-semibold text-foreground">{{ req.student_name }}</p>

                <!-- Student type badge -->
                <Badge :variant="req.student_type === 'child' ? 'secondary' : 'outline'" class="text-[10px] h-4 px-1.5 gap-1">
                  <Baby v-if="req.student_type === 'child'" class="h-2.5 w-2.5" />
                  {{ req.student_type === 'child' ? 'Minor' : 'Adult' }}
                </Badge>

                <!-- Request status -->
                <Badge :variant="statusCfg[req.status]?.variant" class="text-[10px] h-4 px-1.5 gap-1">
                  <component :is="statusCfg[req.status]?.icon" class="h-2.5 w-2.5" />
                  {{ statusCfg[req.status]?.label }}
                </Badge>

                <!-- Payment status -->
                <Badge :variant="paymentStatusCfg[req.payment_status]?.variant" class="text-[10px] h-4 px-1.5 gap-1">
                  <component :is="paymentStatusCfg[req.payment_status]?.icon" class="h-2.5 w-2.5" />
                  {{ paymentStatusCfg[req.payment_status]?.label }}
                </Badge>
              </div>

              <p class="text-xs text-muted-foreground">{{ req.email }}</p>

              <!-- Parent info for minors -->
              <div v-if="req.parent_name" class="flex items-center gap-1.5 text-xs text-secondary mt-1">
                <Shield class="h-3 w-3" />
                Parent: {{ req.parent_name }} · {{ req.parent_email }}
              </div>

              <!-- Course + batch -->
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs text-muted-foreground">
                <span class="font-medium text-foreground">{{ req.course_name }}</span>
                <span>→ {{ req.batch_name }}</span>
                <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                  <Wallet class="h-3 w-3" />{{ formatNaira(req.amount) }}
                </span>
                <span>Ref: {{ req.payment_reference }}</span>
                <span class="text-muted-foreground/60">{{ timeAgo(req.requested_at) }}</span>
              </div>

              <!-- Note from student -->
              <div v-if="req.note" class="mt-2 flex items-start gap-1.5 text-xs text-muted-foreground bg-muted/50 rounded p-2">
                <MessageCircle class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                <span>{{ req.note }}</span>
              </div>
            </div>

            <!-- Actions (only for pending) -->
            <div v-if="req.status === 'pending'" class="flex flex-col gap-2 shrink-0">
              <Button
                size="sm"
                class="gap-2 text-xs h-8 bg-emerald-600 hover:bg-emerald-700 text-white"
                :disabled="actioning === req.id"
                @click="handleApprove(req)"
              >
                <RefreshCw v-if="actioning === req.id" class="h-3.5 w-3.5 animate-spin" />
                <CheckCircle2 v-else class="h-3.5 w-3.5" />
                Approve
              </Button>
              <Button
                size="sm"
                variant="outline"
                class="gap-2 text-xs h-8 text-destructive border-destructive/30 hover:bg-destructive/5"
                :disabled="actioning === req.id"
                @click="openReject(req)"
              >
                <XCircle class="h-3.5 w-3.5" />Reject
              </Button>
            </div>

            <!-- Outcome for processed requests -->
            <div v-else class="shrink-0 text-right">
              <div v-if="req.status === 'approved'" class="flex items-center gap-1 text-xs text-emerald-600">
                <CheckCircle2 class="h-4 w-4" />Enrolled
              </div>
              <div v-else class="flex items-center gap-1 text-xs text-destructive">
                <XCircle class="h-4 w-4" />
                {{ req.payment_status === 'paid' ? 'Refunded' : 'Rejected' }}
              </div>
            </div>

          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Reject dialog -->
    <Dialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <XCircle class="h-5 w-5" />Reject Enrollment
          </DialogTitle>
          <DialogDescription class="text-xs">
            Rejecting <strong class="text-foreground">{{ rejectTarget?.student_name }}</strong>'s enrollment
            for {{ rejectTarget?.batch_name }}.
            <span v-if="rejectTarget?.payment_status === 'paid'" class="block mt-1 text-amber-600 font-medium">
              ⚠️ This student has already paid. Rejection will trigger a Paystack refund of {{ formatNaira(rejectTarget?.amount) }}.
            </span>
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-3 py-2">
          <div class="space-y-1.5">
            <Label>Reason for rejection <span class="text-destructive">*</span></Label>
            <Textarea
              v-model="rejectReason"
              placeholder="e.g., Batch is now full. Please consider the April 2026 batch instead."
              :rows="3"
              :class="rejectError && 'border-destructive'"
            />
            <p v-if="rejectError" class="text-xs text-destructive">{{ rejectError }}</p>
            <p class="text-xs text-muted-foreground">This reason will be included in the rejection email sent to the student.</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" :disabled="actioning === rejectTarget?.id" @click="showRejectDialog = false">Cancel</Button>
          <Button
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="actioning === rejectTarget?.id"
            @click="handleReject"
          >
            <RefreshCw v-if="actioning === rejectTarget?.id" class="mr-2 h-4 w-4 animate-spin" />
            {{ actioning === rejectTarget?.id ? 'Rejecting...' : rejectTarget?.payment_status === 'paid' ? 'Reject & Refund' : 'Reject' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
  </DashboardLayout>
</template>