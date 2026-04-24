<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, Search, Star, Users, CheckCircle2, Clock,
    MoreVertical, Eye, Trash2,
    //AlertCircle, 
    DollarSign, Building2,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
//import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
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
    instructors:    { type: Array,  default: () => [] },
    pendingInvites: { type: Array,  default: () => [] },
    batches:        { type: Array,  default: () => [] },
    filters:        { type: Object, default: () => ({}) },
    stats:          { type: Object, default: () => ({}) },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const search = ref(props.filters.search ?? '')
const pendingInvites = props.pendingInvites ?? []

function applySearch() {
    router.get(window.location.pathname, { search: search.value || undefined }, { preserveState: true })
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
    const path = removingInst.value.type === 'invite'
        ? `/dashboard/instructors/invite/${removingInst.value.id}`
        : `/dashboard/instructors/${removingInst.value.id}`

    router.delete(path, {}, {
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

function paymentLabel(type) {
    return {
        per_batch:   'Fixed per Batch',
        per_student: 'Fixed per Student',
        monthly:     'Monthly Salary',
        custom:      'Custom Arrangement',
    }[type] ?? type ?? '—'
}

function paymentSuffix(type) {
    return { per_batch: '/ batch', per_student: '/ student', monthly: '/ month' }[type] ?? ''
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
                <Button class="gap-2 shrink-0" @click="router.visit('/dashboard/instructors/create')">
                    <Plus class="h-4 w-4" />+ Add Instructor
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
            <div v-if="instructors.length === 0 && pendingInvites.length === 0"
                class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                <Users class="h-10 w-10 text-muted-foreground/30 mb-3" />
                <p class="text-sm font-medium">No instructors yet</p>
                <p class="text-xs text-muted-foreground mt-1">Invite your first instructor to get started.</p>
                <Button class="mt-4 gap-2" size="sm" @click="router.visit('/dashboard/instructors/create')">
                    <Plus class="h-4 w-4" />Add Instructor
                </Button>
            </div>

            <!-- Pending invites -->
            <div v-if="pendingInvites.length" class="space-y-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Pending invitations</h2>
                        <p class="text-sm text-muted-foreground">Invited instructors who have not yet accepted.</p>
                    </div>
                    <Badge variant="outline" class="text-xs capitalize">
                        {{ pendingInvites.length }} pending
                    </Badge>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="invite in pendingInvites" :key="`invite-${invite.id}`" class="overflow-hidden">
                        <CardContent class="p-5 space-y-4">
                            <div class="flex items-start gap-3">
                                <Avatar class="h-12 w-12 shrink-0">
                                    <AvatarFallback class="text-sm bg-muted font-bold text-foreground">
                                        {{ initials(invite.name || invite.email) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-foreground text-sm truncate">{{ invite.name || invite.email }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ invite.email }}</p>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <Badge variant="outline" class="text-xs capitalize">Pending invite</Badge>
                                    </div>
                                </div>
                                <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0"
                                    @click="confirmRemove(invite)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="text-xs text-muted-foreground space-y-1">
                                <p>Invited by: {{ invite.invited_by ?? 'Admin' }}</p>
                                <p v-if="invite.expires_at">Expires: {{ invite.expires_at }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Cards grid — matches screenshot design -->
            <div v-if="instructors.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
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