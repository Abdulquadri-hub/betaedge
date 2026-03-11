<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Plus, Search, Star, MoreVertical,
    Eye, Trash2, CheckCircle2,
    RefreshCw, Send, DollarSign, Shield,
    School, AlertCircle,
    Clock
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Dialog, DialogContent, DialogDescription,
    DialogHeader, DialogTitle, DialogFooter,
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
// import {
//     Select, SelectContent, SelectItem,
//     SelectTrigger, SelectValue,
// } from '@/components/ui/select'
import {
    useInstructorsManager,
    PAYMENT_STRUCTURES,
    INSTRUCTOR_PERMISSIONS,
} from '@/composables/usePeopleManagement'
import { useDashboardCourses } from '@/composables/useDashboardCourses'
import { useDashboardBatches } from '@/composables/useDashboardBatches'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

// ─── Composables ──────────────────────────────────────────────────────────────
const {
    filteredInstructors, isLoading, error,
    search, initials, formatNaira, formatPaymentLine, paymentLabel,
    inviteInstructor, removeInstructor, markInstructorPaid,
} = useInstructorsManager()

const {
    //publishedCourses 
} = useDashboardCourses()
const {
    batches: allBatches,
    //formatNaira: fmtNaira 
} = useDashboardBatches()


// Open batches for assignment
const openBatches = computed(() =>
    allBatches.value.filter(b => b.status === 'open' || b.status === 'active')
)

// ─── Status config ────────────────────────────────────────────────────────────
const statusCfg = {
    active: { label: 'Active', variant: 'default' },
    pending: { label: 'Invited', variant: 'secondary' },
    inactive: { label: 'Inactive', variant: 'outline' },
}

// ─── INVITE DIALOG ─────────────────────────────────────────────────────────────
const showInviteDialog = ref(false)
const isInviting = ref(false)

const defaultInviteForm = () => ({
    email: '',
    name: '',
    phone: '',
    payment_structure: 'per_batch',
    payment_amount: 40000,
    payment_terms: '',
    batch_ids: [],
    permissions: ['upload_materials', 'grade_assignments', 'take_attendance', 'message_students'],
})

const inviteForm = ref(defaultInviteForm())
const inviteErrors = ref({})

function openInvite() {
    inviteForm.value = defaultInviteForm()
    inviteErrors.value = {}
    showInviteDialog.value = true
}

function validateInvite() {
    const e = {}
    if (!inviteForm.value.email.trim())
        e.email = 'Email is required'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inviteForm.value.email))
        e.email = 'Enter a valid email address'
    if (!inviteForm.value.payment_structure)
        e.payment_structure = 'Select a payment structure'
    if (inviteForm.value.payment_structure !== 'custom' && inviteForm.value.payment_amount <= 0)
        e.payment_amount = 'Amount must be greater than 0'
    inviteErrors.value = e
    return Object.keys(e).length === 0
}

function togglePermission(val) {
    const perms = inviteForm.value.permissions
    const idx = perms.indexOf(val)
    if (idx === -1) perms.push(val)
    else perms.splice(idx, 1)
}

function toggleBatch(id) {
    const ids = inviteForm.value.batch_ids
    const idx = ids.indexOf(id)
    if (idx === -1) ids.push(id)
    else ids.splice(idx, 1)
}

async function handleInvite() {
    if (!validateInvite()) return
    isInviting.value = true
    try {
        const result = await inviteInstructor(inviteForm.value)
        if (result?.success !== false) {
            toast({
                title: '📧 Invitation sent!',
                description: `An invitation email has been sent to ${inviteForm.value.email}.`,
            })
            showInviteDialog.value = false
        } else {
            toast({ title: 'Error', description: error.value ?? 'Failed to send invitation.', variant: 'destructive' })
        }
    } finally {
        isInviting.value = false
    }
}

// ─── REMOVE DIALOG ────────────────────────────────────────────────────────────
const showRemoveDialog = ref(false)
const removingInstructor = ref(null)
const isRemoving = ref(false)

function confirmRemove(inst) {
    removingInstructor.value = inst
    showRemoveDialog.value = true
}

async function handleRemove() {
    if (!removingInstructor.value) return
    isRemoving.value = true
    try {
        await removeInstructor(removingInstructor.value.id)
        toast({ title: 'Instructor removed', description: `${removingInstructor.value.name} has been removed from your school.` })
        showRemoveDialog.value = false
    } finally {
        isRemoving.value = false
    }
}

// ─── MARK PAID ────────────────────────────────────────────────────────────────
async function handleMarkPaid(inst) {
    await markInstructorPaid(inst.id, null)
    toast({ title: 'Payment recorded', description: `${inst.name} marked as paid for the latest completed batch.` })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground tracking-tight">Instructors</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage your teaching team, payment agreements, and
                        permissions.</p>
                </div>
                <Button class="gap-2 shrink-0" @click="openInvite">
                    <Plus class="h-4 w-4" />Invite Instructor
                </Button>
            </div>

            <!-- Payment notice (V3 spec) -->
            <div
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4">
                <AlertCircle class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                <p class="text-xs text-amber-800 dark:text-amber-300">
                    <strong>Instructor payments are handled directly by you, outside the platform.</strong>
                    The platform tracks your payment agreements for transparency but does not process instructor
                    payments.
                    All student payments are sent to your school account after the 10% platform fee.
                </p>
            </div>

            <!-- Search -->
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Search instructors or specialties..." class="pl-9" />
            </div>

            <!-- Cards grid -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <!-- Skeleton -->
                <template v-if="isLoading">
                    <div v-for="i in 3" :key="i"
                        class="rounded-xl border border-border bg-card p-5 animate-pulse space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-muted" />
                            <div class="space-y-2 flex-1">
                                <div class="h-4 bg-muted rounded w-3/4" />
                                <div class="h-3 bg-muted rounded w-1/2" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty -->
                <div v-else-if="filteredInstructors.length === 0"
                    class="col-span-full py-16 text-center rounded-xl border border-dashed border-border">
                    <School class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p class="text-sm font-medium text-foreground">No instructors yet</p>
                    <p class="text-xs text-muted-foreground mt-1">Invite your first instructor to get started.</p>
                    <Button class="mt-4 gap-2" size="sm" @click="openInvite">
                        <Plus class="h-4 w-4" />Invite Instructor
                    </Button>
                </div>

                <!-- Instructor card -->
                <Card v-for="inst in filteredInstructors" :key="inst.id"
                    class="group transition-all duration-200 hover:shadow-md hover:border-primary/30">
                    <CardContent class="p-5">

                        <!-- Top row -->
                        <div class="flex items-start justify-between gap-2 mb-4">
                            <div class="flex items-center gap-3">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="text-sm font-bold bg-primary/10 text-primary">{{
                                        initials(inst.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="text-sm font-semibold text-foreground">{{ inst.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ inst.email }}</p>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <Badge :variant="statusCfg[inst.status]?.variant ?? 'outline'"
                                            class="text-[10px] h-4 px-1.5">
                                            {{ statusCfg[inst.status]?.label }}
                                        </Badge>
                                        <div v-if="inst.avg_rating"
                                            class="flex items-center gap-0.5 text-[10px] text-amber-600">
                                            <Star class="h-3 w-3 fill-amber-500" />
                                            {{ inst.avg_rating }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon"
                                        class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="router.visit(`/dashboard/instructors/${inst.id}`)">
                                        <Eye class="mr-2 h-4 w-4" />View Profile
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="inst.payment?.pending_batches > 0"
                                        @click="handleMarkPaid(inst)">
                                        <CheckCircle2 class="mr-2 h-4 w-4 text-emerald-600" />Mark as Paid
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
                        <div class="flex flex-wrap gap-1 mb-3">
                            <Badge v-for="s in inst.specialties.slice(0, 3)" :key="s" variant="outline"
                                class="text-[10px] h-4 px-1.5">
                                {{
                                    s }}</Badge>
                            <span v-if="inst.specialties.length > 3" class="text-[10px] text-muted-foreground">+{{
                                inst.specialties.length - 3 }}</span>
                        </div>

                        <!-- Multi-school badge -->
                        <div v-if="inst.other_schools.length > 0"
                            class="flex items-center gap-1.5 text-xs text-secondary mb-3">
                            <School class="h-3.5 w-3.5" />
                            <span>Also teaches at {{ inst.other_schools.length }} other school{{
                                inst.other_schools.length >
                                    1 ? 's' :
                                '' }}</span>
                        </div>

                        <!-- Stats row -->
                        <div class="grid grid-cols-3 gap-2 py-3 border-y border-border text-center mb-3">
                            <div>
                                <p class="text-sm font-bold text-foreground">{{ inst.active_batches_count }}</p>
                                <p class="text-[10px] text-muted-foreground">Active</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-foreground">{{ inst.completed_batches_count }}</p>
                                <p class="text-[10px] text-muted-foreground">Done</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-foreground">{{ inst.avg_completion_rate != null ?
                                    inst.avg_completion_rate + '%' : '—' }}</p>
                                <p class="text-[10px] text-muted-foreground">Completion</p>
                            </div>
                        </div>

                        <!-- Payment agreement -->
                        <div class="rounded-lg bg-muted/50 border border-border p-3 space-y-1.5">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
                                <DollarSign class="h-3.5 w-3.5 text-primary" />Payment Agreement
                            </div>
                            <p class="text-xs font-medium text-foreground">{{ formatPaymentLine(inst.payment) }}</p>
                            <p class="text-[10px] text-muted-foreground">{{ paymentLabel(inst.payment?.structure) }}</p>

                            <!-- Payment status -->
                            <div class="flex items-center justify-between pt-1 mt-1 border-t border-border/50">
                                <div class="text-[10px] text-muted-foreground">
                                    Earned: <span class="font-semibold text-emerald-600">{{
                                        formatNaira(inst.payment?.total_earned ?? 0)
                                        }}</span>
                                </div>
                                <div v-if="inst.payment?.pending_batches > 0"
                                    class="flex items-center gap-1 text-[10px] text-amber-600">
                                    <Clock class="h-3 w-3" />
                                    {{ inst.payment.pending_batches }} batch{{ inst.payment.pending_batches > 1 ? 'es' :
                                        ''
                                    }} pending
                                </div>
                                <div v-else class="flex items-center gap-1 text-[10px] text-emerald-600">
                                    <CheckCircle2 class="h-3 w-3" />All paid
                                </div>
                            </div>
                        </div>

                        <!-- View profile -->
                        <Button variant="outline" size="sm" class="w-full mt-3 gap-2 text-xs h-8"
                            @click="router.visit(`/dashboard/instructors/${inst.id}`)">
                            <Eye class="h-3.5 w-3.5" />View Profile & Batches
                        </Button>

                    </CardContent>
                </Card>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
         INVITE DIALOG — 3 sections: Contact, Payment, Permissions
    ═══════════════════════════════════════════════════════════════════════ -->
            <Dialog :open="showInviteDialog" @update:open="showInviteDialog = $event">
                <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Send class="h-5 w-5 text-primary" />Invite Instructor
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            An invitation email will be sent. If they don't have a Teach account, they'll be prompted to
                            create one.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-5 py-2">

                        <!-- 1. Contact -->
                        <div class="space-y-3">
                            <p class="text-xs font-semibold text-foreground uppercase tracking-wide">Contact Details</p>
                            <div class="space-y-1.5">
                                <Label>Email Address <span class="text-destructive">*</span></Label>
                                <Input v-model="inviteForm.email" type="email" placeholder="instructor@email.com"
                                    :class="inviteErrors.email && 'border-destructive'" />
                                <p v-if="inviteErrors.email" class="text-xs text-destructive">{{ inviteErrors.email }}
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label>Full Name (optional)</Label>
                                    <Input v-model="inviteForm.name" placeholder="e.g., Adebayo Johnson" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Phone (optional)</Label>
                                    <Input v-model="inviteForm.phone" placeholder="+234 8XX XXX XXXX" />
                                </div>
                            </div>
                        </div>

                        <!-- 2. Batch assignment -->
                        <div class="space-y-3">
                            <p class="text-xs font-semibold text-foreground uppercase tracking-wide">Assign to Batches
                            </p>
                            <div v-if="openBatches.length === 0" class="text-xs text-muted-foreground">No open batches
                                available.
                            </div>
                            <div v-else class="space-y-2">
                                <label v-for="b in openBatches" :key="b.id"
                                    class="flex items-center gap-3 rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40 transition-colors">
                                    <Checkbox :checked="inviteForm.batch_ids.includes(b.id)"
                                        @update:checked="toggleBatch(b.id)" />
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ b.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ b.course_name }} · {{
                                            b.current_enrollment }}/{{
                                                b.max_students }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 3. Payment agreement (V3 spec) -->
                        <div class="space-y-3 rounded-lg bg-muted/30 border border-border p-4">
                            <p
                                class="text-xs font-semibold text-foreground uppercase tracking-wide flex items-center gap-2">
                                <DollarSign class="h-3.5 w-3.5 text-primary" />Payment Agreement
                            </p>

                            <!-- Payment structure cards -->
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="ps in PAYMENT_STRUCTURES" :key="ps.value" type="button"
                                    class="flex flex-col gap-0.5 rounded-lg border p-2.5 text-left text-xs transition-all"
                                    :class="inviteForm.payment_structure === ps.value
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/40'"
                                    @click="inviteForm.payment_structure = ps.value">
                                    <span class="font-semibold text-foreground">{{ ps.label }}</span>
                                    <span class="text-muted-foreground">{{ ps.desc }}</span>
                                </button>
                            </div>

                            <!-- Amount field (hidden for custom) -->
                            <div v-if="inviteForm.payment_structure !== 'custom'" class="space-y-1.5">
                                <Label>Amount (₦) <span class="text-destructive">*</span></Label>
                                <div class="relative">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">₦</span>
                                    <Input v-model.number="inviteForm.payment_amount" type="number" min="0" class="pl-7"
                                        :placeholder="inviteForm.payment_structure === 'per_batch' ? '40000'
                                            : inviteForm.payment_structure === 'per_student' ? '2500'
                                                : '80000'" :class="inviteErrors.payment_amount && 'border-destructive'" />
                                </div>
                                <p v-if="inviteErrors.payment_amount" class="text-xs text-destructive">{{
                                    inviteErrors.payment_amount }}</p>
                            </div>

                            <!-- Additional terms -->
                            <div class="space-y-1.5">
                                <Label>Additional Terms (Optional)</Label>
                                <Textarea v-model="inviteForm.payment_terms"
                                    placeholder="e.g., Payment within 7 days of batch completion. Bonus ₦10,000 if completion > 85%."
                                    :rows="3" />
                            </div>

                            <div
                                class="rounded-lg bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-300">
                                You will handle instructor payments directly outside this platform. The platform tracks
                                your
                                agreement for transparency only.
                            </div>
                        </div>

                        <!-- 4. Permissions -->
                        <div class="space-y-3">
                            <p
                                class="text-xs font-semibold text-foreground uppercase tracking-wide flex items-center gap-2">
                                <Shield class="h-3.5 w-3.5 text-primary" />Permissions
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="perm in INSTRUCTOR_PERMISSIONS" :key="perm.value"
                                    class="flex items-center gap-2.5 rounded-lg border border-border p-2.5 cursor-pointer hover:bg-muted/40 transition-colors">
                                    <Checkbox :checked="inviteForm.permissions.includes(perm.value)"
                                        @update:checked="togglePermission(perm.value)" />
                                    <span class="text-xs font-medium text-foreground">{{ perm.label }}</span>
                                </label>
                            </div>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button variant="outline" :disabled="isInviting"
                            @click="showInviteDialog = false">Cancel</Button>
                        <Button :disabled="isInviting" @click="handleInvite">
                            <RefreshCw v-if="isInviting" class="mr-2 h-4 w-4 animate-spin" />
                            <Send v-else class="mr-2 h-4 w-4" />
                            {{ isInviting ? 'Sending...' : 'Send Invitation' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Remove confirm -->
            <AlertDialog :open="showRemoveDialog" @update:open="showRemoveDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remove instructor?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong class="text-foreground">{{ removingInstructor?.name }}</strong> will be removed from
                            your
                            school.
                            Their completed batch records will be retained. Active batches will become unassigned.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isRemoving">Cancel</AlertDialogCancel>
                        <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="isRemoving" @click="handleRemove">
                            <RefreshCw v-if="isRemoving" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isRemoving ? 'Removing...' : 'Remove Instructor' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </DashboardLayout>
</template>