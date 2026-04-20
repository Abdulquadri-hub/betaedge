<script setup>
import { ref, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    School, BookOpen, CreditCard, Zap, Bell,
    Receipt, Save, RefreshCw, Plus, Trash2,
    Eye, EyeOff, CheckCircle2, Pencil,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription,
} from '@/components/ui/dialog'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    tenant:              { type: Object, required: true },
    academicLevels:      { type: Array,  default: () => [] },
    paystackConfig:      { type: Object, default: null },
    currentSubscription: { type: Object, default: null },
    plans:               { type: Array,  default: () => [] },
    notifPrefs:          { type: Object, default: null },
    billingHistory:      { type: Array,  default: () => [] },
})

const page = usePage()

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
}, { deep: true })

const activeTab = ref('profile')
const isSaving  = ref(false)

const profile = reactive({
    name:            props.tenant.name            ?? '',
    tagline:         props.tenant.tagline         ?? '',
    email:           props.tenant.owner_email     ?? '',
    phone:           props.tenant.phone           ?? '',
    whatsapp:        props.tenant.whatsapp        ?? '',
    website:         props.tenant.website         ?? '',
    address:         props.tenant.address         ?? '',
    timezone:        props.tenant.timezone        ?? 'Africa/Lagos',
    primary_color:   props.tenant.primary_color   ?? '#f97316',
    secondary_color: props.tenant.secondary_color ?? '#0d9488',
    enrollment_mode: props.tenant.enrollment_mode ?? 'manual',
})

watch(() => props.tenant, (t) => {
    profile.name            = t.name            ?? ''
    profile.tagline         = t.tagline         ?? ''
    profile.email           = t.owner_email     ?? ''
    profile.phone           = t.phone           ?? ''
    profile.whatsapp        = t.whatsapp        ?? ''
    profile.website         = t.website         ?? ''
    profile.address         = t.address         ?? ''
    profile.timezone        = t.timezone        ?? 'Africa/Lagos'
    profile.primary_color   = t.primary_color   ?? '#f97316'
    profile.secondary_color = t.secondary_color ?? '#0d9488'
    profile.enrollment_mode = t.enrollment_mode ?? 'manual'
}, { deep: true })

function saveProfile() {
    isSaving.value = true
    router.post('/dashboard/settings/profile', { ...profile }, {
        preserveScroll: true,
        onFinish: () => { isSaving.value = false },
    })
}

const showLevelDialog   = ref(false)
const isEditingLevel    = ref(false)
const editingLevelId    = ref(null)
const isSubmittingLevel = ref(false)
const showDeleteDialog  = ref(false)
const deletingLevel     = ref(null)

const levelForm = reactive({
    name:         '',
    code:         '',
    level_number: '',
    description:  '',
})
const levelErrors = reactive({ name: '', code: '' })

function openAddLevel() {
    isEditingLevel.value  = false
    editingLevelId.value  = null
    levelForm.name         = ''
    levelForm.code         = ''
    levelForm.level_number = ''
    levelForm.description  = ''
    levelErrors.name       = ''
    levelErrors.code       = ''
    showLevelDialog.value  = true
}

function openEditLevel(level) {
    isEditingLevel.value   = true
    editingLevelId.value   = level.id
    levelForm.name         = level.name
    levelForm.code         = level.code
    levelForm.level_number = level.level_number ?? ''
    levelForm.description  = level.description  ?? ''
    levelErrors.name       = ''
    levelErrors.code       = ''
    showLevelDialog.value  = true
}

function submitLevel() {
    levelErrors.name = ''
    levelErrors.code = ''
    if (!levelForm.name.trim()) { levelErrors.name = 'Name is required'; return }
    if (!levelForm.code.trim()) { levelErrors.code = 'Code is required'; return }

    isSubmittingLevel.value = true
     

    const url = isEditingLevel.value
        ? `/dashboard/settings/academic-levels/${editingLevelId.value}`
        : '/dashboard/settings/academic-levels'

    const method = isEditingLevel.value ? 'put' : 'post'

    router[method](url, { ...levelForm }, {
        preserveScroll: true,
        onSuccess: () => { showLevelDialog.value = false },
        onError: (errors) => {
            if (errors.name) levelErrors.name = errors.name
            if (errors.code) levelErrors.code = errors.code
        },
        onFinish: () => { isSubmittingLevel.value = false },
    })
}

function toggleLevel(level) {
    router.patch(`/dashboard/settings/academic-levels/${level.id}/toggle`, {}, {
        preserveScroll: true,
    })
}

function confirmDeleteLevel(level) {
    deletingLevel.value   = level
    showDeleteDialog.value = true
}

function deleteLevel() {
    if (!deletingLevel.value) return
    router.delete(`/dashboard/settings/academic-levels/${deletingLevel.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false
            deletingLevel.value    = null
        },
    })
}

const paystack   = reactive({
    public_key:     props.paystackConfig?.public_key     ?? '',
    secret_key:     '',
    bank_name:      props.paystackConfig?.bank_name      ?? '',
    account_number: props.paystackConfig?.account_number ?? '',
    account_name:   props.paystackConfig?.account_name   ?? '',
})
const showSecret = ref(false)

watch(() => props.paystackConfig, (c) => {
    if (!c) return
    paystack.public_key     = c.public_key     ?? ''
    paystack.bank_name      = c.bank_name      ?? ''
    paystack.account_number = c.account_number ?? ''
    paystack.account_name   = c.account_name   ?? ''
}, { deep: true })

function savePaystack() {
    isSaving.value = true
    router.post('/dashboard/settings/paystack', { ...paystack }, {
        preserveScroll: true,
        onFinish: () => { isSaving.value = false },
    })
}

const notifPrefs = reactive({
    email_new_enrollment:   props.notifPrefs?.email_new_enrollment   ?? true,
    email_payment_received: props.notifPrefs?.email_payment_received ?? true,
    email_batch_complete:   props.notifPrefs?.email_batch_complete   ?? true,
    email_complaint:        props.notifPrefs?.email_complaint        ?? true,
    email_weekly_summary:   props.notifPrefs?.email_weekly_summary   ?? true,
    sms_new_enrollment:     props.notifPrefs?.sms_new_enrollment     ?? false,
    sms_payment_received:   props.notifPrefs?.sms_payment_received   ?? true,
    sms_complaint:          props.notifPrefs?.sms_complaint          ?? true,
})

watch(() => props.notifPrefs, (n) => {
    if (!n) return
    Object.keys(notifPrefs).forEach(k => { notifPrefs[k] = n[k] ?? notifPrefs[k] })
}, { deep: true })

function saveNotifications() {
    isSaving.value = true
    router.post('/dashboard/settings/notifications', { ...notifPrefs }, {
        preserveScroll: true,
        onFinish: () => { isSaving.value = false },
    })
}

function handleUpgrade(plan) {
    toast.info(`Redirecting to payment for ${plan.name} plan…`)
}

function fmt(v) {
    return '₦' + (v ?? 0).toLocaleString('en-NG')
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const tabs = [
    { value: 'profile',       label: 'School Profile',  icon: School     },
    { value: 'levels',        label: 'Academic Levels', icon: BookOpen   },
    { value: 'paystack',      label: 'Paystack',        icon: CreditCard },
    { value: 'subscription',  label: 'Subscription',    icon: Zap        },
    { value: 'notifications', label: 'Notifications',   icon: Bell       },
    { value: 'billing',       label: 'Billing History', icon: Receipt    },
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
                <div class="overflow-x-auto">
                    <TabsList class="inline-flex w-max min-w-full sm:w-full">
                        <TabsTrigger v-for="t in tabs" :key="t.value" :value="t.value" class="gap-1.5 text-xs sm:text-sm">
                            <component :is="t.icon" class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">{{ t.label }}</span>
                            <span class="sm:hidden">{{ t.label.split(' ')[0] }}</span>
                        </TabsTrigger>
                    </TabsList>
                </div>

                <TabsContent value="profile" class="space-y-4">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">School Information</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>School Name</Label>
                                    <Input v-model="profile.name" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Tagline</Label>
                                    <Input v-model="profile.tagline" placeholder="e.g., Excellence in online education" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Contact Email</Label>
                                    <Input v-model="profile.email" type="email" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Phone</Label>
                                    <Input v-model="profile.phone" placeholder="+234 8XX XXX XXXX" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>WhatsApp Number</Label>
                                    <Input v-model="profile.whatsapp" placeholder="+234 8XX XXX XXXX" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Website (Optional)</Label>
                                    <Input v-model="profile.website" placeholder="https://yourschool.com" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Address / Location</Label>
                                    <Input v-model="profile.address" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Timezone</Label>
                                    <Select v-model="profile.timezone">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Africa/Lagos">Africa/Lagos (WAT, UTC+1)</SelectItem>
                                            <SelectItem value="Africa/Accra">Africa/Accra (GMT, UTC+0)</SelectItem>
                                            <SelectItem value="Africa/Nairobi">Africa/Nairobi (EAT, UTC+3)</SelectItem>
                                            <SelectItem value="Africa/Johannesburg">Africa/Johannesburg (SAST, UTC+2)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Primary Color</Label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="profile.primary_color" type="color" class="h-9 w-16 rounded border border-border cursor-pointer p-0.5" />
                                        <Input v-model="profile.primary_color" class="font-mono text-sm" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Secondary Color</Label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="profile.secondary_color" type="color" class="h-9 w-16 rounded border border-border cursor-pointer p-0.5" />
                                        <Input v-model="profile.secondary_color" class="font-mono text-sm" />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Enrollment Mode</Label>
                                <Select v-model="profile.enrollment_mode">
                                    <SelectTrigger class="max-w-xs"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="automatic">Automatic — approve on payment</SelectItem>
                                        <SelectItem value="manual">Manual — review each request first</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">Manual mode lets you review each student before approving enrollment.</p>
                            </div>

                            <Button :disabled="isSaving" @click="saveProfile">
                                <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                {{ isSaving ? 'Saving…' : 'Save Profile' }}
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="levels" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-sm">Academic Levels</CardTitle>
                                    <p class="text-xs text-muted-foreground mt-0.5">Define the grade levels or classes your school offers.</p>
                                </div>
                                <Button size="sm" class="gap-2" @click="openAddLevel">
                                    <Plus class="h-4 w-4" />Add Level
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="props.academicLevels.length === 0" class="py-10 text-center rounded-lg border border-dashed border-border">
                                <BookOpen class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-sm text-muted-foreground">No academic levels yet.</p>
                                <p class="text-xs text-muted-foreground mt-0.5">Add levels like Primary 1, JSS 1, SS3, etc.</p>
                            </div>

                            <div v-for="level in props.academicLevels" :key="level.id"
                                class="flex items-center justify-between gap-3 rounded-lg border border-border p-3 hover:bg-muted/30 transition-colors">
                                <div class="flex items-center gap-3">
                                    <Checkbox :checked="level.is_active" @update:checked="toggleLevel(level)" />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-foreground">{{ level.name }}</p>
                                            <span class="text-[10px] font-mono bg-muted px-1.5 py-0.5 rounded text-muted-foreground">{{ level.code }}</span>
                                        </div>
                                        <p v-if="level.description" class="text-xs text-muted-foreground mt-0.5">{{ level.description }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <Badge :variant="level.is_active ? 'default' : 'secondary'" class="text-xs">
                                        {{ level.is_active ? 'Active' : 'Hidden' }}
                                    </Badge>
                                    <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEditLevel(level)">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive hover:bg-destructive/10" @click="confirmDeleteLevel(level)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="paystack" class="space-y-4">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4 text-xs">
                        <p class="font-semibold text-amber-800 dark:text-amber-300">Keep your secret key private</p>
                        <p class="text-amber-700 dark:text-amber-400 mt-0.5">Never share your Paystack secret key. We store it encrypted and only use it to verify webhooks.</p>
                    </div>
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Paystack Integration</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label>Public Key</Label>
                                <Input v-model="paystack.public_key" placeholder="pk_live_…" class="font-mono text-sm" />
                                <p class="text-xs text-muted-foreground">Used in the payment form on your enrollment page.</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Secret Key</Label>
                                <div class="relative">
                                    <Input v-model="paystack.secret_key" :type="showSecret ? 'text' : 'password'" placeholder="sk_live_…" class="font-mono text-sm pr-10" />
                                    <Button variant="ghost" size="icon" class="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7" @click="showSecret = !showSecret">
                                        <component :is="showSecret ? EyeOff : Eye" class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">Leave blank to keep your existing key.</p>
                            </div>
                            <div class="pt-2 border-t border-border space-y-3">
                                <p class="text-sm font-semibold text-foreground">Payout Bank Account</p>
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="space-y-1.5">
                                        <Label>Bank Name</Label>
                                        <Input v-model="paystack.bank_name" placeholder="e.g., GTBank" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label>Account Number</Label>
                                        <Input v-model="paystack.account_number" class="font-mono" placeholder="0123456789" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Account Name</Label>
                                    <Input v-model="paystack.account_name" placeholder="e.g., Bright Stars Academy Ltd" />
                                </div>
                            </div>
                            <Button :disabled="isSaving" @click="savePaystack">
                                <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                {{ isSaving ? 'Saving…' : 'Save Paystack Settings' }}
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="subscription" class="space-y-4">
                    <Card v-if="props.currentSubscription" class="border-primary/30 bg-primary/5">
                        <CardContent class="p-5">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <p class="text-xs text-muted-foreground font-medium">Current Plan</p>
                                    <p class="text-xl font-black text-primary">{{ props.currentSubscription.name }}</p>
                                    <p class="text-sm text-muted-foreground mt-0.5">
                                        Expires {{ fmtDate(props.currentSubscription.expires_at) }} · {{ fmt(props.currentSubscription.price) }} paid
                                    </p>
                                </div>
                                <Badge variant="default" class="shrink-0 gap-1.5">
                                    <CheckCircle2 class="h-3.5 w-3.5" />Active
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <div v-if="props.plans.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                        No subscription plans available.
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <Card v-for="plan in props.plans" :key="plan.key" class="relative transition-all"
                            :class="plan.current ? 'border-primary shadow-md' : 'hover:border-primary/40 cursor-pointer'">
                            <div v-if="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-secondary text-secondary-foreground text-xs font-bold shadow">
                                Most Popular
                            </div>
                            <CardContent class="p-5 space-y-4 pt-6">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-base font-bold text-foreground">{{ plan.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ plan.duration_label }}</p>
                                    </div>
                                    <Badge v-if="plan.current" variant="default" class="text-xs shrink-0">Current</Badge>
                                </div>
                                <div>
                                    <p class="text-3xl font-black text-primary">{{ fmt(plan.price) }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">≈ {{ fmt(plan.per_month) }}/month</p>
                                </div>
                                <ul class="space-y-1.5">
                                    <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-xs text-muted-foreground">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                        {{ feature }}
                                    </li>
                                </ul>
                                <Button v-if="!plan.current" class="w-full text-sm" :variant="plan.popular ? 'default' : 'outline'" @click="handleUpgrade(plan)">
                                    Get {{ plan.name }}
                                </Button>
                                <Button v-else variant="ghost" class="w-full text-sm" disabled>Current Plan</Button>
                            </CardContent>
                        </Card>
                    </div>

                    <p class="text-xs text-muted-foreground text-center">
                        All plans include the full platform. Payments processed securely via Paystack.
                        Your school data is never deleted — renew anytime to regain access.
                    </p>
                </TabsContent>

                <TabsContent value="notifications" class="space-y-4">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Email Notifications</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <label v-for="(label, key) in {
                                email_new_enrollment:   'New student enrollment',
                                email_payment_received: 'Payment received from student',
                                email_batch_complete:   'Batch completed',
                                email_complaint:        'New complaint submitted',
                                email_weekly_summary:   'Weekly summary report',
                            }" :key="key" class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40">
                                <span class="text-sm text-foreground">{{ label }}</span>
                                <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
                            </label>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle class="text-sm">SMS Notifications</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <label v-for="(label, key) in {
                                sms_new_enrollment:   'New student enrollment',
                                sms_payment_received: 'Payment received',
                                sms_complaint:        'New complaint submitted',
                            }" :key="key" class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40">
                                <span class="text-sm text-foreground">{{ label }}</span>
                                <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
                            </label>
                        </CardContent>
                    </Card>
                    <Button :disabled="isSaving" @click="saveNotifications">
                        <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        {{ isSaving ? 'Saving…' : 'Save Preferences' }}
                    </Button>
                </TabsContent>

                <TabsContent value="billing" class="space-y-4">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">Subscription Invoices</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <div v-if="props.billingHistory.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                                No billing history yet.
                            </div>
                            <div class="divide-y divide-border">
                                <div v-for="inv in props.billingHistory" :key="inv.id" class="flex items-center justify-between gap-4 px-5 py-4">
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
                                            <a :href="inv.invoice_url" target="_blank">
                                                <Receipt class="h-3.5 w-3.5" />PDF
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <Dialog :open="showLevelDialog" @update:open="showLevelDialog = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ isEditingLevel ? 'Edit Academic Level' : 'Add Academic Level' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditingLevel ? 'Update the details for this level.' : 'Add a new grade level or class type your school offers.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Level Name <span class="text-destructive">*</span></Label>
                        <Input v-model="levelForm.name" placeholder="e.g., Primary 4" :class="levelErrors.name && 'border-destructive'" />
                        <p v-if="levelErrors.name" class="text-xs text-destructive">{{ levelErrors.name }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Short Code <span class="text-destructive">*</span></Label>
                        <Input v-model="levelForm.code" placeholder="e.g., PRI4" class="font-mono uppercase" :class="levelErrors.code && 'border-destructive'" @input="levelForm.code = levelForm.code.toUpperCase()" />
                        <p v-if="levelErrors.code" class="text-xs text-destructive">{{ levelErrors.code }}</p>
                        <p class="text-xs text-muted-foreground">A short unique identifier. Max 20 characters.</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Level Number <span class="text-muted-foreground text-xs">(optional)</span></Label>
                        <Input v-model.number="levelForm.level_number" type="number" min="0" placeholder="e.g., 4" />
                        <p class="text-xs text-muted-foreground">Used to determine the order and next/previous level for student promotions.</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Description <span class="text-muted-foreground text-xs">(optional)</span></Label>
                        <Input v-model="levelForm.description" placeholder="e.g., Ages 9–10" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="isSubmittingLevel" @click="showLevelDialog = false">Cancel</Button>
                    <Button :disabled="isSubmittingLevel || !levelForm.name.trim() || !levelForm.code.trim()" @click="submitLevel">
                        <RefreshCw v-if="isSubmittingLevel" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmittingLevel ? 'Saving…' : isEditingLevel ? 'Update Level' : 'Add Level' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this level?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong class="text-foreground">{{ deletingLevel?.name }}</strong> will be permanently removed.
                        This cannot be undone. Courses assigned to this level will need to be reassigned.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="deleteLevel">
                        Delete Level
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </DashboardLayout>
</template>