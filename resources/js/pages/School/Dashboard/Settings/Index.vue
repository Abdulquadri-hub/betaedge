<script setup>

import { ref } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
  School, BookOpen, CreditCard, Zap, Bell,
  Receipt, Save, RefreshCw, Plus, Trash2,
  Eye, EyeOff,CheckCircle2,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Badge }    from '@/components/ui/badge'
// import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
// import {
//   AlertDialog, AlertDialogAction, AlertDialogCancel,
//   AlertDialogContent, AlertDialogDescription,
//   AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
// } from '@/components/ui/alert-dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const activeTab  = ref('profile')
const isSaving   = ref(false)

const profile = ref({
  name:           'Bright Stars Academy',
  tagline:        'Excellence in online education',
  email:          'admin@brightstars.teach.com',
  phone:          '+234 803 456 7890',
  whatsapp:       '+234 803 456 7890',
  website:        '',
  address:        'Lagos, Nigeria',
  timezone:       'Africa/Lagos',
  primary_color:  '#f97316',
  secondary_color:'#0d9488',
  enrollment_mode:'manual',
})

async function saveProfile() {
  isSaving.value = true
  await new Promise(r => setTimeout(r, 800))
  // TODO: router.put(route('dashboard.settings.profile'), profile.value, { preserveScroll: true })
  isSaving.value = false
  toast({ title: 'School profile saved' })
}

// ── 2. Academic Levels ────────────────────────────────────────────────────────
const academicLevels = ref([
  { id: 'lvl-001', name: 'Primary 1–3',   description: 'Ages 6–9',   active: true  },
  { id: 'lvl-002', name: 'Primary 4–6',   description: 'Ages 9–12',  active: true  },
  { id: 'lvl-003', name: 'JSS 1–3',       description: 'Ages 12–15', active: true  },
  { id: 'lvl-004', name: 'SS 1–3',        description: 'Ages 15–18', active: false },
  { id: 'lvl-005', name: 'University',    description: '18+',        active: false },
])
const newLevel = ref({ name: '', description: '' })
const isAddingLevel = ref(false)

async function addLevel() {
  if (!newLevel.value.name.trim()) return
  isAddingLevel.value = true
  await new Promise(r => setTimeout(r, 400))
  // TODO: router.post(route('dashboard.settings.levels.store'), newLevel.value)
  academicLevels.value.push({ id: 'lvl-' + Date.now(), active: true, ...newLevel.value })
  newLevel.value = { name: '', description: '' }
  isAddingLevel.value = false
  toast({ title: 'Academic level added' })
}

async function toggleLevel(level) {
  level.active = !level.active
  // TODO: router.patch(route('dashboard.settings.levels.toggle', level.id))
}

async function deleteLevel(id) {
  academicLevels.value = academicLevels.value.filter(l => l.id !== id)
  // TODO: router.delete(route('dashboard.settings.levels.destroy', id))
  toast({ title: 'Level removed' })
}

// ── 3. Paystack ───────────────────────────────────────────────────────────────
const paystack = ref({
  public_key:  'pk_live_****************************',
  secret_key:  '',
  bank_name:   'Guaranty Trust Bank (GTBank)',
  account_number: '****5678',
  account_name: 'Bright Stars Academy Ltd',
})
const showSecret    = ref(false)
// const showDeletePay = ref(false)

async function savePaystack() {
  isSaving.value = true
  await new Promise(r => setTimeout(r, 800))
  // TODO: router.put(route('dashboard.settings.paystack'), paystack.value)
  isSaving.value = false
  toast({ title: 'Paystack settings saved', description: 'Keys updated securely.' })
}

// ── 4. Subscription ───────────────────────────────────────────────────────────
// Billing is duration-based (not monthly). School pays once per period.
// TODO: replace with Inertia prop — subscription: { plan, expires_at, status }
const currentPlan = ref({
  name: '6 Months', price: 25000, duration: '6months',
  expires_at: '2026-09-10', status: 'active',
})

const plans = ref([
  {
    key: '3months', name: '3 Months', price: 15000, duration_label: '3 months',
    saving: null, per_month: 5000, current: false,
    features: ['Unlimited students', 'Unlimited batches', 'All dashboard features', 'Email support'],
  },
  {
    key: '6months', name: '6 Months', price: 25000, duration_label: '6 months',
    saving: '5,000', per_month: 4167, current: true, popular: true,
    features: ['Unlimited students', 'Unlimited batches', 'All dashboard features', 'Priority support'],
  },
  {
    key: '1year', name: '1 Year', price: 40000, duration_label: '12 months',
    saving: '20,000', per_month: 3333, current: false,
    features: ['Unlimited students', 'Unlimited batches', 'All dashboard features', 'Priority support', 'Dedicated onboarding'],
  },
])

// ── 5. Notifications ──────────────────────────────────────────────────────────
const notifPrefs = ref({
  email_new_enrollment:  true,
  email_payment_received:true,
  email_batch_complete:  true,
  email_complaint:       true,
  email_weekly_summary:  true,
  sms_new_enrollment:    false,
  sms_payment_received:  true,
  sms_complaint:         true,
})

async function saveNotifications() {
  isSaving.value = true
  await new Promise(r => setTimeout(r, 600))
  // TODO: router.put(route('dashboard.settings.notifications'), notifPrefs.value)
  isSaving.value = false
  toast({ title: 'Notification preferences saved' })
}

// ── 6. Billing History ────────────────────────────────────────────────────────
const billingHistory = ref([
  { id: 'inv-001', date: '2026-03-10', amount: 25000, plan: '6 Months', expires: '2026-09-10', status: 'paid', invoice_url: '#' },
  { id: 'inv-002', date: '2025-09-10', amount: 15000, plan: '3 Months', expires: '2025-12-10', status: 'paid', invoice_url: '#' },
  { id: 'inv-003', date: '2025-06-10', amount: 15000, plan: '3 Months', expires: '2025-09-10', status: 'paid', invoice_url: '#' },
])

function fmt(v) { return '₦' + (v ?? 0).toLocaleString('en-NG') }
function fmtDate(iso) {
  return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const tabs = [
  { value: 'profile',       label: 'School Profile',   icon: School      },
  { value: 'levels',        label: 'Academic Levels',  icon: BookOpen    },
  { value: 'paystack',      label: 'Paystack',         icon: CreditCard  },
  { value: 'subscription',  label: 'Subscription',     icon: Zap         },
  { value: 'notifications', label: 'Notifications',    icon: Bell        },
  { value: 'billing',       label: 'Billing History',  icon: Receipt     },
]
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-5xl mx-auto space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">Settings</h1>
      <p class="text-sm text-muted-foreground mt-1">Manage your school profile, integrations, and preferences.</p>
    </div>

    <Tabs v-model="activeTab" class="space-y-4">
      <!-- Tab list — scrollable on mobile -->
      <div class="overflow-x-auto">
        <TabsList class="inline-flex w-max min-w-full sm:w-full">
          <TabsTrigger v-for="t in tabs" :key="t.value" :value="t.value" class="gap-1.5 text-xs sm:text-sm">
            <component :is="t.icon" class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">{{ t.label }}</span>
            <span class="sm:hidden">{{ t.label.split(' ')[0] }}</span>
          </TabsTrigger>
        </TabsList>
      </div>

      <!-- 1. School Profile -->
      <TabsContent value="profile" class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">School Information</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div class="space-y-1.5"><Label>School Name</Label><Input v-model="profile.name" /></div>
              <div class="space-y-1.5"><Label>Tagline</Label><Input v-model="profile.tagline" placeholder="e.g., Excellence in online education" /></div>
              <div class="space-y-1.5"><Label>Contact Email</Label><Input v-model="profile.email" type="email" /></div>
              <div class="space-y-1.5"><Label>Phone</Label><Input v-model="profile.phone" /></div>
              <div class="space-y-1.5"><Label>WhatsApp Number</Label><Input v-model="profile.whatsapp" placeholder="+234 8XX XXX XXXX" /></div>
              <div class="space-y-1.5"><Label>Website (Optional)</Label><Input v-model="profile.website" placeholder="https://yourschool.com" /></div>
              <div class="space-y-1.5"><Label>Address / Location</Label><Input v-model="profile.address" /></div>
              <div class="space-y-1.5">
                <Label>Timezone</Label>
                <Select v-model="profile.timezone">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Africa/Lagos">Africa/Lagos (WAT, UTC+1)</SelectItem>
                    <SelectItem value="Africa/Accra">Africa/Accra (GMT, UTC+0)</SelectItem>
                    <SelectItem value="Africa/Nairobi">Africa/Nairobi (EAT, UTC+3)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label>Primary Color</Label>
                <div class="flex items-center gap-2">
                  <input v-model="profile.primary_color" type="color" class="h-9 w-16 rounded border border-border cursor-pointer" />
                  <Input v-model="profile.primary_color" class="font-mono text-sm" />
                </div>
              </div>
              <div class="space-y-1.5">
                <Label>Secondary Color</Label>
                <div class="flex items-center gap-2">
                  <input v-model="profile.secondary_color" type="color" class="h-9 w-16 rounded border border-border cursor-pointer" />
                  <Input v-model="profile.secondary_color" class="font-mono text-sm" />
                </div>
              </div>
            </div>
            <div class="space-y-1.5">
              <Label>Enrollment Mode</Label>
              <Select v-model="profile.enrollment_mode">
                <SelectTrigger class="max-w-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="automatic">Automatic (approve on payment)</SelectItem>
                  <SelectItem value="manual">Manual (review each request)</SelectItem>
                </SelectContent>
              </Select>
              <p class="text-xs text-muted-foreground">Manual mode gives you a chance to review each student before approving.</p>
            </div>
            <Button :disabled="isSaving" @click="saveProfile">
              <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
              <Save v-else class="mr-2 h-4 w-4" />
              {{ isSaving ? 'Saving...' : 'Save Profile' }}
            </Button>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- 2. Academic Levels -->
      <TabsContent value="levels" class="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm">Academic Levels Offered</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div v-for="level in academicLevels" :key="level.id"
              class="flex items-center justify-between gap-3 rounded-lg border border-border p-3"
            >
              <div class="flex items-center gap-3">
                <Checkbox :checked="level.active" @update:checked="toggleLevel(level)" />
                <div>
                  <p class="text-sm font-medium text-foreground">{{ level.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ level.description }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge :variant="level.active ? 'default' : 'secondary'" class="text-xs">{{ level.active ? 'Active' : 'Hidden' }}</Badge>
                <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:bg-destructive/10" @click="deleteLevel(level.id)">
                  <Trash2 class="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>

            <div class="rounded-lg border border-dashed border-border p-3 space-y-3">
              <p class="text-xs font-semibold text-foreground">Add New Level</p>
              <div class="grid sm:grid-cols-2 gap-3">
                <Input v-model="newLevel.name" placeholder="e.g., Adult Learning" />
                <Input v-model="newLevel.description" placeholder="e.g., Ages 18+" />
              </div>
              <Button size="sm" class="gap-2" :disabled="!newLevel.name.trim() || isAddingLevel" @click="addLevel">
                <Plus class="h-4 w-4" />Add Level
              </Button>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- 3. Paystack -->
      <TabsContent value="paystack" class="space-y-4">
        <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4 text-xs">
          <p class="font-semibold text-amber-800 dark:text-amber-300">⚠️ Keep your secret key private</p>
          <p class="text-amber-700 dark:text-amber-400 mt-0.5">Never share your Paystack secret key. We store it encrypted and only use it to verify webhooks.</p>
        </div>
        <Card>
          <CardHeader><CardTitle class="text-sm">Paystack Integration</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Public Key</Label>
              <Input v-model="paystack.public_key" placeholder="pk_live_..." class="font-mono text-sm" />
              <p class="text-xs text-muted-foreground">Used in the payment form on your school's enrollment page.</p>
            </div>
            <div class="space-y-1.5">
              <Label>Secret Key</Label>
              <div class="relative">
                <Input v-model="paystack.secret_key" :type="showSecret ? 'text' : 'password'" placeholder="sk_live_..." class="font-mono text-sm pr-10" />
                <Button variant="ghost" size="icon" class="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7" @click="showSecret = !showSecret">
                  <component :is="showSecret ? EyeOff : Eye" class="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
            <div class="pt-2 border-t border-border space-y-3">
              <p class="text-sm font-semibold text-foreground">Payout Bank Account</p>
              <div class="grid sm:grid-cols-2 gap-3">
                <div class="space-y-1.5"><Label>Bank Name</Label><Input v-model="paystack.bank_name" /></div>
                <div class="space-y-1.5"><Label>Account Number</Label><Input v-model="paystack.account_number" class="font-mono" /></div>
              </div>
              <div class="space-y-1.5"><Label>Account Name</Label><Input v-model="paystack.account_name" /></div>
            </div>
            <Button :disabled="isSaving" @click="savePaystack">
              <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
              <Save v-else class="mr-2 h-4 w-4" />
              {{ isSaving ? 'Saving...' : 'Save Paystack Settings' }}
            </Button>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- 4. Subscription -->
      <TabsContent value="subscription" class="space-y-4">

        <!-- Current plan status -->
        <Card class="border-primary/30 bg-primary/5">
          <CardContent class="p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
              <div>
                <p class="text-xs text-muted-foreground font-medium">Current Plan</p>
                <p class="text-xl font-black text-primary">{{ currentPlan.name }}</p>
                <p class="text-sm text-muted-foreground mt-0.5">
                  Expires {{ fmtDate(currentPlan.expires_at) }} · {{ fmt(currentPlan.price) }} paid
                </p>
              </div>
              <Badge variant="default" class="shrink-0 gap-1.5">
                <CheckCircle2 class="h-3.5 w-3.5" />Active
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Plan cards -->
        <div class="grid sm:grid-cols-3 gap-4">
          <Card
            v-for="plan in plans" :key="plan.key"
            class="relative transition-all"
            :class="plan.current
              ? 'border-primary shadow-md'
              : plan.popular
                ? 'border-secondary/50 hover:border-secondary cursor-pointer'
                : 'hover:border-primary/40 cursor-pointer'"
          >
            <!-- Popular badge -->
            <div
              v-if="plan.popular"
              class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-secondary text-secondary-foreground text-xs font-bold shadow"
            >
              Most Popular
            </div>

            <CardContent class="p-5 space-y-4 pt-6">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-base font-bold text-foreground">{{ plan.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ plan.duration_label }} access</p>
                </div>
                <Badge v-if="plan.current" variant="default" class="text-xs shrink-0">Current</Badge>
              </div>

              <div>
                <p class="text-3xl font-black text-primary">{{ fmt(plan.price) }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">
                  ≈ {{ fmt(plan.per_month) }}/month
                  <span v-if="plan.saving" class="text-emerald-600 font-semibold ml-1">· save ₦{{ plan.saving }}</span>
                </p>
              </div>

              <ul class="space-y-1.5">
                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-xs text-muted-foreground">
                  <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" />
                  {{ feature }}
                </li>
              </ul>

              <Button
                v-if="!plan.current"
                class="w-full text-sm"
                :variant="plan.popular ? 'default' : 'outline'"
                @click="toast({ title: 'Redirecting to payment', description: `Processing ${plan.name} plan via Paystack...` })"
              >
                Get {{ plan.name }}
              </Button>
              <Button v-else variant="ghost" class="w-full text-sm" disabled>
                Current Plan
              </Button>
            </CardContent>
          </Card>
        </div>

        <p class="text-xs text-muted-foreground text-center">
          All plans include the full platform. Payments are processed securely via Paystack.
          Your school data is never deleted — renew anytime to regain access.
        </p>
      </TabsContent>

      <!-- 5. Notifications -->
      <TabsContent value="notifications" class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Email Notifications</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <label v-for="(val, key) in {
              email_new_enrollment:   'New student enrollment',
              email_payment_received: 'Payment received from student',
              email_batch_complete:   'Batch completed',
              email_complaint:        'New complaint submitted',
              email_weekly_summary:   'Weekly summary report',
            }" :key="key"
              class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40"
            >
              <span class="text-sm text-foreground">{{ val }}</span>
              <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
            </label>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-sm">SMS Notifications</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <label v-for="(val, key) in {
              sms_new_enrollment:    'New student enrollment',
              sms_payment_received:  'Payment received',
              sms_complaint:         'New complaint submitted',
            }" :key="key"
              class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40"
            >
              <span class="text-sm text-foreground">{{ val }}</span>
              <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
            </label>
          </CardContent>
        </Card>
        <Button :disabled="isSaving" @click="saveNotifications">
          <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
          <Save v-else class="mr-2 h-4 w-4" />
          {{ isSaving ? 'Saving...' : 'Save Preferences' }}
        </Button>
      </TabsContent>

      <!-- 6. Billing History -->
      <TabsContent value="billing" class="space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Subscription Invoices</CardTitle></CardHeader>
          <CardContent class="p-0">
            <div class="divide-y divide-border">
              <div v-for="inv in billingHistory" :key="inv.id"
                class="flex items-center justify-between gap-4 px-5 py-4"
              >
                <div>
                  <p class="text-sm font-medium text-foreground">{{ inv.plan }} Plan · {{ fmtDate(inv.date) }}</p>
                  <p class="text-xs text-muted-foreground">Invoice #{{ inv.id }} · Valid until {{ fmtDate(inv.expires) }}</p>
                </div>
                <div class="flex items-center gap-3">
                  <p class="text-sm font-bold text-foreground">{{ fmt(inv.amount) }}</p>
                  <Badge variant="outline" class="text-xs gap-1 text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20">
                    <CheckCircle2 class="h-3 w-3" />Paid
                  </Badge>
                  <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7" as-child>
                    <a :href="inv.invoice_url" target="_blank"><Receipt class="h-3.5 w-3.5" />PDF</a>
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

    </Tabs>
  </div>
  </DashboardLayout>
</template>