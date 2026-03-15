<script setup>
import { ref, computed } from 'vue'
import {
  Lock, Unlock, Building2, CheckCircle2,
  Shield, Zap, Users, CreditCard, X,
} from 'lucide-vue-next'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge  } from '@/components/ui/badge'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  currentSchool: {
    type: String,
    default: '',
  },
  targetAction: {
    type: String,
    default: 'apply',
    validator: v => ['apply', 'accept_invite'].includes(v),
  },
  targetLabel: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'paymentSuccess'])

const UNLOCK_FEE    = 10000
const isProcessing  = ref(false)
const paymentStage  = ref('idle') // 'idle' | 'initiating' | 'redirecting' | 'verifying' | 'done'

const actionLabel = computed(() =>
  props.targetAction === 'apply' ? 'apply to this job' : 'accept this invite'
)

const benefits = [
  { icon: Building2, text: 'Teach at unlimited schools simultaneously'      },
  { icon: Users,     text: 'Manage all batches from one dashboard'           },
  { icon: Zap,       text: 'Switch between schools instantly'                },
  { icon: Shield,    text: 'One-time payment — never pay again'              },
]

async function handlePayment() {
  isProcessing.value = true
  paymentStage.value = 'initiating'

  try {
    await new Promise(r => setTimeout(r, 800))

    /**
     * TODO (Laravel 12 + Paystack):
     *
     * Step 1 — Initiate payment server-side
     * const res = await fetch('/instructor/multi-school/initiate', {
     *   method: 'POST',
     *   headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': ... },
     * })
     * const { payment_url, reference } = await res.json()
     *
     * Step 2 — Open Paystack popup (or redirect)
     * Option A (popup — recommended):
     * const handler = PaystackPop.setup({
     *   key:       'pk_live_...',       // school's own Paystack key
     *   email:     user.email,
     *   amount:    10000 * 100,         // kobo
     *   ref:       reference,
     *   currency:  'NGN',
     *   metadata: { type: 'multi_school_unlock', instructor_id: user.id },
     *   onSuccess: async (transaction) => {
     *     paymentStage.value = 'verifying'
     *     await fetch('/instructor/multi-school/verify', {
     *       method: 'POST',
     *       body: JSON.stringify({ reference: transaction.reference }),
     *       headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': ... },
     *     })
     *     paymentStage.value = 'done'
     *     emit('paymentSuccess')
     *   },
     *   onClose: () => {
     *     isProcessing.value = false
     *     paymentStage.value = 'idle'
     *   },
     * })
     * handler.openIframe()
     */

    // Mock success for UI demo
    paymentStage.value = 'done'
    await new Promise(r => setTimeout(r, 600))
    emit('paymentSuccess')

  } catch {
    paymentStage.value = 'idle'
    isProcessing.value = false
  }
}

const buttonLabel = computed(() => ({
  idle:       `Unlock for ₦${UNLOCK_FEE.toLocaleString('en-NG')}`,
  initiating: 'Preparing payment...',
  redirecting:'Opening Paystack...',
  verifying:  'Verifying payment...',
  done:       'Payment confirmed!',
}[paymentStage.value]))
</script>

<template>
  <Dialog :open="open" @update:open="val => !val && $emit('close')">
    <DialogContent class="max-w-md p-0 overflow-hidden">

      <!-- Header gradient -->
      <div class="bg-gradient-to-br from-primary/10 via-secondary/10 to-background p-6 pb-4 border-b border-border">
        <div class="flex items-start justify-between gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/15 shrink-0">
            <Lock class="h-6 w-6 text-primary" />
          </div>
          <button
            class="text-muted-foreground hover:text-foreground transition-colors -mr-1 -mt-1"
            @click="$emit('close')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-3">
          <DialogHeader>
            <DialogTitle class="text-lg font-black text-foreground text-left">
              Unlock Multi-School Access
            </DialogTitle>
            <DialogDescription class="text-sm text-muted-foreground text-left mt-1">
              You're currently teaching at
              <strong class="text-foreground">{{ currentSchool }}</strong>.
              To {{ actionLabel }}
              <strong v-if="targetLabel" class="text-foreground"> ({{ targetLabel }})</strong>,
              you need multi-school access.
            </DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-5">

        <!-- Price highlight -->
        <div class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 px-4 py-3">
          <div>
            <p class="text-xs text-muted-foreground font-medium">One-time unlock fee</p>
            <p class="text-2xl font-black text-primary mt-0.5">₦10,000</p>
            <p class="text-xs text-muted-foreground">Pay once. Teach at unlimited schools forever.</p>
          </div>
          <Badge class="bg-emerald-500 text-white border-0 text-xs shrink-0">
            One-time only
          </Badge>
        </div>

        <!-- Benefits list -->
        <div class="space-y-2.5">
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">What you unlock</p>
          <div
            v-for="benefit in benefits"
            :key="benefit.text"
            class="flex items-center gap-3 text-sm text-foreground"
          >
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/30">
              <component :is="benefit.icon" class="h-3.5 w-3.5 text-emerald-600" />
            </div>
            {{ benefit.text }}
          </div>
        </div>

        <!-- Paystack trust note -->
        <div class="flex items-center gap-2 text-xs text-muted-foreground rounded-lg bg-muted/50 px-3 py-2.5">
          <CreditCard class="h-3.5 w-3.5 shrink-0" />
          Secured by Paystack. Card, bank transfer, or USSD accepted.
        </div>

        <!-- Done state -->
        <div
          v-if="paymentStage === 'done'"
          class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 p-4"
        >
          <CheckCircle2 class="h-6 w-6 text-emerald-500 shrink-0" />
          <div>
            <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">Payment confirmed!</p>
            <p class="text-xs text-muted-foreground mt-0.5">Multi-school access unlocked. Continuing...</p>
          </div>
        </div>

        <!-- CTA buttons -->
        <div v-if="paymentStage !== 'done'" class="flex gap-3 pt-1">
          <Button
            variant="outline"
            class="flex-1"
            :disabled="isProcessing"
            @click="$emit('close')"
          >
            Not now
          </Button>
          <Button
            class="flex-1 gap-2"
            :disabled="isProcessing"
            @click="handlePayment"
          >
            <span
              v-if="isProcessing"
              class="h-4 w-4 rounded-full border-2 border-primary-foreground border-t-transparent animate-spin shrink-0"
            />
            <Unlock v-else class="h-4 w-4 shrink-0" />
            {{ buttonLabel }}
          </Button>
        </div>

        <p class="text-center text-xs text-muted-foreground">
          Your first school is always free.
          This is a one-time payment — no recurring charges.
        </p>
      </div>

    </DialogContent>
  </Dialog>
</template>