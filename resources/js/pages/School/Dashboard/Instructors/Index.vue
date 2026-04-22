<script setup>
import { ref, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, Search, Star, Users, CheckCircle2, Clock,
    MoreVertical, Eye, Trash2,
    AlertCircle, DollarSign, Building2,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    instructors: { type: Array,  default: () => [] },
    batches:     { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
    stats:       { type: Object, default: () => ({}) },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const search = ref(props.filters.search ?? '')

function applySearch() {
    router.get(window.location.pathname, { search: search.value || undefined }, { preserveState: true })
}

// ── Invite dialog ──────────────────────────────────────────────────────────────
const showInvite   = ref(false)
const isInviting   = ref(false)
const inviteErrors = ref({})

const inviteForm = reactive({
    email:          '',
    name:           '',
    phone:          '',
    batch_ids:      [],
    payment_type:   'per_batch',
    payment_amount: '',
    payment_terms:  '',
})

const PAYMENT_TYPES = [
    { value: 'per_batch',   label: 'Fixed per Batch',    desc: 'Paid when a batch completes'   },
    { value: 'per_student', label: 'Fixed per Student',  desc: 'Based on enrollment count'     },
    { value: 'monthly',     label: 'Monthly Salary',     desc: 'Fixed monthly payment'         },
    { value: 'custom',      label: 'Custom Arrangement', desc: 'Define your own terms'         },
]

function paymentLabel(type) {
    const found = PAYMENT_TYPES.find(p => p.value === type)
    return found?.label ?? type
}

function paymentSuffix(type) {
    return { per_batch: '/ batch', per_student: '/ student', monthly: '/ month', custom: '' }[type] ?? ''
}

function toggleBatch(id) {
    const idx = inviteForm.batch_ids.indexOf(id)
    if (idx === -1) inviteForm.batch_ids.push(id)
    else inviteForm.batch_ids.splice(idx, 1)
}

function openInvite() {
    Object.assign(inviteForm, { email: '', name: '', phone: '', batch_ids: [], payment_type: 'per_batch', payment_amount: '', payment_terms: '' })
    inviteErrors.value = {}
    showInvite.value   = true
}

function handleInvite() {
    inviteErrors.value = {}
    const e = {}
    if (!inviteForm.email?.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) e.email = 'Valid email required'
    if (!inviteForm.payment_type) e.payment_type = 'Select a payment type'
    if (!inviteForm.payment_amount || Number(inviteForm.payment_amount) < 0) e.payment_amount = 'Enter payment amount'
    if (Object.keys(e).length) { inviteErrors.value = e; return }

    isInviting.value = true
    router.post('/dashboard/instructors/invite', { ...inviteForm }, {
        onError: (errs) => { inviteErrors.value = errs },
        onSuccess: () => { showInvite.value = false },
        onFinish: () => { isInviting.value = false },
    })
}

// ── Remove dialog ──────────────────────────────────────────────────────────────
const showRemove   = ref(false)
const removingInst = ref(null)
const isRemoving   = ref(false)

function confirmRemove(inst) {
    removingInst.value = inst
    showRemove.value   = true
}

function handleRemove() {
    if (!removingInst.value) return
    isRemoving.value = true
    router.delete(`/dashboard/instructors/${removingInst.value.id}`, {}, {
        onSuccess: () => { showRemove.value = false; removingInst.value = null },
        onFinish:  () => { isRemoving.value = false },
    })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtNaira(n) {
    if (!n) return '₦0'
    return '₦' + Number(n).toLocaleString('en-NG')
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Instructors</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage your teaching team, payment agreements, and permissions.
                    </p>
                </div>
                <Button class="gap-2 shrink-0" @click="openInvite">
                    <Plus class="h-4 w-4" />+ Invite Instructor
                </Button>
            </div>

    
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Total Instructors</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Active</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ stats?.active ?? 0 }}</p>
                </div>
            </div>

            <!-- Search -->
            <div class="flex gap-3 max-w-sm">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search instructors or specialties…"
                        class="pl-9" @keydown.enter="applySearch" />
                </div>
            </div>

            <!-- Empty -->
            <div v-if="instructors.length === 0"
                class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                <Users class="h-10 w-10 text-muted-foreground/30 mb-3" />
                <p class="text-sm font-medium">No instructors yet</p>
                <p class="text-xs text-muted-foreground mt-1">Invite your first instructor to get started.</p>
                <Button class="mt-4 gap-2" size="sm" @click="openInvite">
                    <Plus class="h-4 w-4" />Invite Instructor
                </Button>
            </div>

            <!-- Cards grid — matches screenshot design -->
            <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="inst in instructors" :key="inst.id" class="overflow-hidden">
                    <CardContent class="p-5 space-y-4">

                        <!-- Top: avatar + name + status + menu -->
                        <div class="flex items-start gap-3">
                            <Avatar class="h-12 w-12 shrink-0">
                                <img v-if="inst.avatar" :src="inst.avatar" :alt="inst.name"
                                    class="h-full w-full object-cover rounded-full" />
                                <AvatarFallback class="text-sm bg-muted font-bold text-foreground">
                                    {{ initials(inst.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-foreground text-sm truncate">{{ inst.name }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ inst.email }}</p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <Badge :variant="inst.status === 'active' ? 'default' : 'secondary'"
                                        class="text-xs capitalize">{{ inst.status }}</Badge>
                                    <span v-if="inst.rating" class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                        {{ inst.rating }}
                                    </span>
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="router.visit(`/dashboard/instructors/${inst.id}`)">
                                        <Eye class="mr-2 h-4 w-4" />View Profile & Batches
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-destructive focus:text-destructive"
                                        @click="confirmRemove(inst)">
                                        <Trash2 class="mr-2 h-4 w-4" />Remove from School
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Specialties -->
                        <div v-if="inst.specialties?.length" class="flex flex-wrap gap-1.5">
                            <span v-for="s in inst.specialties.slice(0, 3)" :key="s"
                                class="px-2 py-0.5 rounded-full bg-muted text-xs font-medium text-foreground">
                                {{ s }}
                            </span>
                            <span v-if="inst.specialties.length > 3"
                                class="px-2 py-0.5 rounded-full bg-muted text-xs text-muted-foreground">
                                +{{ inst.specialties.length - 3 }}
                            </span>
                        </div>

                        <!-- Also teaches elsewhere -->
                        <p v-if="inst.other_schools > 0"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Building2 class="h-3.5 w-3.5 shrink-0" />
                            Also teaches at {{ inst.other_schools }} other school{{ inst.other_schools !== 1 ? 's' : '' }}
                        </p>

                        <!-- Stats row: Active / Done / Completion -->
                        <div class="grid grid-cols-3 gap-2 text-center border border-border rounded-xl p-3">
                            <div>
                                <p class="text-lg font-bold text-foreground">{{ inst.active_batches }}</p>
                                <p class="text-[10px] text-muted-foreground">Active</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-foreground">{{ inst.done_batches }}</p>
                                <p class="text-[10px] text-muted-foreground">Done</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-foreground">
                                    {{ inst.completion_rate !== null ? inst.completion_rate + '%' : '—' }}
                                </p>
                                <p class="text-[10px] text-muted-foreground">Completion</p>
                            </div>
                        </div>

                        <!-- Payment agreement -->
                        <div v-if="inst.payment_agreement"
                            class="rounded-xl border border-border p-3 space-y-1.5">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                <DollarSign class="h-3.5 w-3.5" />Payment Agreement
                            </div>
                            <p class="text-sm font-bold text-foreground">
                                {{ fmtNaira(inst.payment_agreement.amount) }}
                                <span class="text-xs font-normal text-muted-foreground">
                                    {{ paymentSuffix(inst.payment_agreement.type) }}
                                </span>
                            </p>
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ paymentLabel(inst.payment_agreement.type) }}
                            </p>
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span class="text-muted-foreground">
                                    Earned: <strong :class="inst.earnings.total_paid > 0 ? 'text-emerald-600' : ''">
                                        {{ fmtNaira(inst.earnings.total_paid) }}
                                    </strong>
                                </span>
                                <span v-if="inst.earnings.has_pending"
                                    class="flex items-center gap-1 text-amber-600 font-medium">
                                    <Clock class="h-3 w-3" />
                                    {{ inst.earnings.balance_due > 0 ? fmtNaira(inst.earnings.balance_due) + ' pending' : '1 batch pending' }}
                                </span>
                                <span v-else-if="inst.earnings.total_paid > 0"
                                    class="flex items-center gap-1 text-emerald-600">
                                    <CheckCircle2 class="h-3 w-3" />All paid
                                </span>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-border p-3 text-center">
                            <p class="text-xs text-muted-foreground">No payment agreement set</p>
                        </div>

                        <!-- View button -->
                        <Button variant="outline" size="sm" class="w-full gap-2"
                            @click="router.visit(`/dashboard/instructors/${inst.id}`)">
                            <Eye class="h-4 w-4" />View Profile & Batches
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ── Invite dialog ──────────────────────────────────────────────────── -->
        <Dialog :open="showInvite" @update:open="showInvite = $event">
            <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Plus class="h-5 w-5 text-primary" />Invite Instructor
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        An invitation email will be sent. If they don't have a BetaEdge account, they'll be prompted to create one.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5 py-2">
                    <!-- Contact details -->
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Contact Details</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Email Address <span class="text-destructive">*</span></Label>
                        <Input v-model="inviteForm.email" type="email"
                            placeholder="instructor@email.com"
                            :class="inviteErrors.email && 'border-destructive'" />
                        <p v-if="inviteErrors.email" class="text-xs text-destructive">{{ inviteErrors.email }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Full Name <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <Input v-model="inviteForm.name" placeholder="e.g., Adebayo Johnson" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Phone <span class="text-muted-foreground text-xs">(optional)</span></Label>
                            <Input v-model="inviteForm.phone" placeholder="+234 8XX XXX XXXX" />
                        </div>
                    </div>

                    <!-- Assign to batches -->
                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Assign to Batches</p>
                        <div v-if="!batches.length" class="text-xs text-muted-foreground">
                            No active batches available
                        </div>
                        <div v-else class="space-y-2 max-h-44 overflow-y-auto pr-1">
                            <button v-for="b in batches" :key="b.id" type="button"
                                class="w-full text-left rounded-xl border px-4 py-3 transition-all text-sm"
                                :class="inviteForm.batch_ids.includes(b.id)
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                    : 'border-border hover:border-primary/40'"
                                @click="toggleBatch(b.id)">
                                <p class="font-medium text-foreground">{{ b.name }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    {{ b.subjects }} · {{ b.count }}/{{ b.max }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <!-- Payment agreement -->
                    <div class="space-y-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Payment Agreement</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button v-for="pt in PAYMENT_TYPES" :key="pt.value" type="button"
                                class="rounded-xl border px-3 py-2.5 text-left transition-all"
                                :class="inviteForm.payment_type === pt.value
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                    : 'border-border hover:border-primary/40'"
                                @click="inviteForm.payment_type = pt.value">
                                <p class="text-sm font-semibold text-foreground">{{ pt.label }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ pt.desc }}</p>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label>
                                    Amount (₦)
                                    <span class="text-muted-foreground text-xs">
                                        {{ paymentSuffix(inviteForm.payment_type) }}
                                    </span>
                                </Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">₦</span>
                                    <Input v-model="inviteForm.payment_amount" type="number" min="0" class="pl-7"
                                        :class="inviteErrors.payment_amount && 'border-destructive'" />
                                </div>
                                <p v-if="inviteErrors.payment_amount" class="text-xs text-destructive">{{ inviteErrors.payment_amount }}</p>
                            </div>
                            <div v-if="inviteForm.payment_type === 'custom'" class="space-y-1.5">
                                <Label>Terms <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                <Input v-model="inviteForm.payment_terms" placeholder="Describe the arrangement" />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" :disabled="isInviting" @click="showInvite = false">Cancel</Button>
                    <Button :disabled="isInviting" class="gap-2" @click="handleInvite">
                        <RefreshCw v-if="isInviting" class="h-4 w-4 animate-spin" />
                        {{ isInviting ? 'Sending…' : 'Send Invitation' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Remove confirmation -->
        <AlertDialog :open="showRemove" @update:open="showRemove = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove this instructor?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong>{{ removingInst?.name }}</strong> will be removed from your school.
                        Their BetaEdge account remains active — they won't lose access to other schools they teach at.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isRemoving">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isRemoving" @click="handleRemove">
                        <RefreshCw v-if="isRemoving" class="mr-2 h-4 w-4 animate-spin" />
                        Remove
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </DashboardLayout>
</template>