<script setup>

import { ref, computed } from 'vue'
import {
  CheckCircle2, Clock, Building2, MessageCircle, AlertCircle,
} from 'lucide-vue-next'
import { Card, CardContent} from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Badge }    from '@/components/ui/badge'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Label }    from '@/components/ui/label'
import { toast } from 'vue-sonner'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { earnings, formatNaira, fmtDate } = useInstructorDashboard()


const totalPaid    = computed(() => earnings.value.reduce((s, e) => s + e.payment_history.filter(p => p.status === 'paid').reduce((a, p) => a + p.amount, 0), 0))
const totalPending = computed(() => earnings.value.reduce((s, e) => {
  if (e.payment_structure === 'per_batch') return s + (e.batches_active * e.amount)
  if (e.payment_structure === 'per_student') return s + (e.total_earned - e.payment_history.reduce((a, p) => a + (p.status === 'paid' ? p.amount : 0), 0))
  return s
}, 0))

// ── Message school owner dialog ───────────────────────────────────────────────
const showMessageDialog = ref(false)
const messageTarget     = ref(null)
const messageText       = ref('')
const isSending         = ref(false)

function openMessage(schoolName) {
  messageTarget.value = schoolName
  messageText.value   = ''
  showMessageDialog.value = true
}

async function sendMessage() {
  if (!messageText.value.trim()) return
  isSending.value = true
  await new Promise(r => setTimeout(r, 700))
  // TODO: router.post(route('instructor.messages.store'), { school_name: messageTarget.value, message: messageText.value })
  isSending.value = false
  showMessageDialog.value = false
  toast({ title: 'Message sent', description: `Your message has been sent to the owner of ${messageTarget.value}.` })
}

const paymentStructureLabel = {
  per_batch:   'Per Batch',
  per_student: 'Per Student (enrolled)',
  monthly:     'Monthly Retainer',
  custom:      'Custom Agreement',
}
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">My Earnings</h1>
      <p class="text-sm text-muted-foreground mt-1">Payment agreements and history across all schools you teach at.</p>
    </div>

    <!-- Total stats -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />Total Received
          </p>
          <p class="text-2xl font-black text-emerald-600 mt-1">{{ formatNaira(totalPaid) }}</p>
          <p class="text-xs text-muted-foreground">all schools combined</p>
        </CardContent>
      </Card>
      <Card class="border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Clock class="h-3.5 w-3.5 text-amber-600" />Expected (Active)
          </p>
          <p class="text-2xl font-black text-amber-600 mt-1">{{ formatNaira(totalPending) }}</p>
          <p class="text-xs text-muted-foreground">upon batch completion</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Building2 class="h-3.5 w-3.5" />Schools
          </p>
          <p class="text-2xl font-black text-foreground mt-1">{{ earnings.length }}</p>
          <p class="text-xs text-muted-foreground">active teaching positions</p>
        </CardContent>
      </Card>
    </div>

    <!-- Per-school earnings cards -->
    <div class="space-y-4">
      <Card v-for="school in earnings" :key="school.tenant_id" class="overflow-hidden">
        <!-- School header -->
        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-border">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
              <Building2 class="h-5 w-5" />
            </div>
            <div>
              <p class="text-sm font-bold text-foreground">{{ school.school }}</p>
              <p class="text-xs text-muted-foreground">{{ paymentStructureLabel[school.payment_structure] }}</p>
            </div>
          </div>
          <Button variant="outline" size="sm" class="gap-2 text-xs h-8" @click="openMessage(school.school)">
            <MessageCircle class="h-3.5 w-3.5" />Message Owner
          </Button>
        </div>

        <CardContent class="p-5 space-y-4">

          <!-- Payment agreement summary -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="rounded-lg bg-muted/50 p-3">
              <p class="text-lg font-black text-primary">{{ formatNaira(school.amount) }}</p>
              <p class="text-[10px] text-muted-foreground">
                {{ school.payment_structure === 'per_batch' ? 'per batch' : 'per student' }}
              </p>
            </div>
            <div class="rounded-lg bg-muted/50 p-3">
              <p class="text-lg font-black text-foreground">{{ school.batches_active }}</p>
              <p class="text-[10px] text-muted-foreground">active batches</p>
            </div>
            <div class="rounded-lg bg-muted/50 p-3">
              <p class="text-lg font-black text-emerald-600">{{ formatNaira(school.total_earned) }}</p>
              <p class="text-[10px] text-muted-foreground">total earned</p>
            </div>
            <div class="rounded-lg bg-muted/50 p-3">
              <p class="text-lg font-black text-amber-600">
                {{ formatNaira(school.payment_structure === 'per_batch' ? school.batches_active * school.amount : 0) }}
              </p>
              <p class="text-[10px] text-muted-foreground">expected</p>
            </div>
          </div>

          <!-- Additional terms -->
          <div v-if="school.additional_terms" class="rounded-lg border border-border bg-muted/20 px-3 py-2">
            <p class="text-xs text-muted-foreground leading-relaxed">📋 {{ school.additional_terms }}</p>
          </div>

          <!-- Payment history -->
          <div v-if="school.payment_history.length" class="space-y-2">
            <p class="text-xs font-semibold text-foreground uppercase tracking-wide">Payment History</p>
            <div class="divide-y divide-border rounded-lg border border-border overflow-hidden">
              <div
                v-for="p in school.payment_history" :key="p.batch"
                class="flex items-center justify-between px-4 py-3"
              >
                <div>
                  <p class="text-sm font-medium text-foreground">{{ p.batch }}</p>
                  <p class="text-xs text-muted-foreground">{{ p.status === 'paid' ? `Paid on ${fmtDate(p.paid_at)}` : 'Awaiting payment' }}</p>
                </div>
                <div class="flex items-center gap-3">
                  <p class="text-sm font-bold text-foreground">{{ formatNaira(p.amount) }}</p>
                  <Badge
                    :variant="p.status === 'paid' ? 'outline' : 'secondary'"
                    :class="p.status === 'paid' ? 'text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20' : 'text-amber-600'"
                    class="text-xs gap-1"
                  >
                    <CheckCircle2 v-if="p.status === 'paid'" class="h-3 w-3" />
                    <Clock v-else class="h-3 w-3" />
                    {{ p.status === 'paid' ? 'Paid' : 'Pending' }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-xs text-muted-foreground text-center py-2">
            No payment history yet. Complete a batch to record earnings.
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Note about payment handling -->
    <div class="rounded-xl border border-border bg-muted/30 p-4 text-xs text-muted-foreground flex items-start gap-3">
      <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-muted-foreground" />
      <p>Instructor payments are handled directly between you and the school owner outside the Teach platform. The platform only tracks payment records for your reference. If there's a discrepancy, contact the school owner directly.</p>
    </div>

    <!-- Message dialog -->
    <Dialog :open="showMessageDialog" @update:open="showMessageDialog = $event">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle>Message {{ messageTarget }}</DialogTitle>
          <DialogDescription class="text-xs">Send a message to the school owner. They will respond via email.</DialogDescription>
        </DialogHeader>
        <div class="space-y-3 py-2">
          <Label>Your Message</Label>
          <Textarea
            v-model="messageText"
            placeholder="e.g., Hi, I wanted to follow up on the payment for the January 2026 batch..."
            :rows="5"
          />
        </div>
        <DialogFooter>
          <Button variant="outline" :disabled="isSending" @click="showMessageDialog = false">Cancel</Button>
          <Button :disabled="!messageText.trim() || isSending" @click="sendMessage">
            <span v-if="isSending" class="flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />Sending...</span>
            <span v-else>Send Message</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
  </InstructorLayout>
</template>