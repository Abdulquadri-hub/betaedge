<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    ArrowLeft, Trash2, Star, CheckCircle2, Clock,
    DollarSign, Mail, Phone, BookOpen, Users,
    TrendingUp, AlertCircle, Building2,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    instructor: { type: Object, required: true },
    batches:    { type: Array,  default: () => [] },
})

// ── Remove ─────────────────────────────────────────────────────────────────────
const showRemove  = ref(false)
const isRemoving  = ref(false)

function handleRemove() {
    isRemoving.value = true
    router.delete(`/dashboard/instructors/${props.instructor.id}`, {}, {
        onSuccess: () => {
            toast.success('Instructor removed from school')
            router.visit('/dashboard/instructors')
        },
        onError: () => toast.error('Failed to remove instructor'),
        onFinish: () => { isRemoving.value = false; showRemove.value = false },
    })
}

// ── Mark paid ──────────────────────────────────────────────────────────────────
const markingPaidId = ref(null)

function handleMarkPaid(batch) {
    markingPaidId.value = batch.batch_id
    router.post(`/dashboard/instructors/${props.instructor.id}/mark-paid`, {
        batch_id: batch.batch_id,
        amount:   props.instructor.payment_agreement?.amount ?? 0,
    }, {
        onSuccess: () => toast.success(`Payment for ${batch.batch_name} marked as paid`),
        onError:   () => toast.error('Failed to record payment'),
        onFinish:  () => { markingPaidId.value = null },
    })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtNaira(n) {
    if (!n && n !== 0) return '₦0'
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

const batchStatusCfg = {
    planning:  { label: 'Planning',     variant: 'outline'   },
    open:      { label: 'Enrolling',    variant: 'default'   },
    active:    { label: 'In Progress',  variant: 'secondary' },
    completed: { label: 'Completed',    variant: 'outline'   },
    closed:    { label: 'Closed',       variant: 'outline'   },
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/instructors')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>

                <div class="flex-1 min-w-0 flex items-center gap-4">
                    <Avatar class="h-14 w-14 shrink-0">
                        <img v-if="instructor.avatar" :src="instructor.avatar"
                            class="h-full w-full object-cover rounded-full" />
                        <AvatarFallback class="text-lg font-bold bg-primary/10 text-primary">
                            {{ initials(instructor.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-bold text-foreground">{{ instructor.name }}</h1>
                            <div v-if="instructor.rating"
                                class="flex items-center gap-1 text-sm text-amber-600">
                                <Star class="h-4 w-4 fill-amber-500" />{{ instructor.rating }}
                            </div>
                            <Badge :variant="instructor.status === 'active' ? 'default' : 'secondary'"
                                class="text-xs capitalize">
                                {{ instructor.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center gap-4 mt-1 text-xs text-muted-foreground flex-wrap">
                            <span class="flex items-center gap-1">
                                <Mail class="h-3 w-3" />{{ instructor.email }}
                            </span>
                            <span v-if="instructor.phone" class="flex items-center gap-1">
                                <Phone class="h-3 w-3" />{{ instructor.phone }}
                            </span>
                            <span v-if="instructor.qualification">{{ instructor.qualification }}</span>
                        </div>
                    </div>
                </div>

                <Button variant="outline" size="sm"
                    class="gap-2 text-destructive border-destructive/30 hover:bg-destructive/5 shrink-0"
                    @click="showRemove = true">
                    <Trash2 class="h-4 w-4" />Remove
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Active Batches</p>
                        <p class="text-2xl font-bold text-foreground mt-1">{{ instructor.active_batches }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Completed</p>
                        <p class="text-2xl font-bold text-foreground mt-1">{{ instructor.done_batches }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Completion Rate</p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            {{ instructor.completion_rate !== null ? instructor.completion_rate + '%' : '—' }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground font-medium">Total Paid Out</p>
                        <p class="text-2xl font-bold text-secondary mt-1">
                            {{ fmtNaira(instructor.earnings?.total_paid ?? 0) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">

                <!-- Left: bio + batch history -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Bio -->
                    <Card v-if="instructor.bio || instructor.specialties?.length">
                        <CardHeader>
                            <CardTitle class="text-sm">About</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0 space-y-3">
                            <p v-if="instructor.bio"
                                class="text-sm text-muted-foreground leading-relaxed">
                                {{ instructor.bio }}
                            </p>
                            <div v-if="instructor.specialties?.length" class="flex flex-wrap gap-1.5">
                                <Badge v-for="s in instructor.specialties" :key="s"
                                    variant="outline" class="text-xs">{{ s }}</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Batch history -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <BookOpen class="h-4 w-4 text-primary" />Batch History & Payments
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0 space-y-3">
                            <div v-if="batches.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground">
                                No batches assigned yet.
                            </div>
                            <div v-for="b in batches" :key="b.batch_id"
                                class="rounded-lg border border-border p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-foreground">
                                                {{ b.batch_name }}
                                            </p>
                                            <Badge
                                                :variant="batchStatusCfg[b.batch_status]?.variant ?? 'outline'"
                                                class="text-xs">
                                                {{ batchStatusCfg[b.batch_status]?.label ?? b.batch_status }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-0.5">
                                            {{ b.course_title }}
                                        </p>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground mt-1 flex-wrap">
                                            <span v-if="b.student_count" class="flex items-center gap-1">
                                                <Users class="h-3 w-3" />{{ b.student_count }} students
                                            </span>
                                            <span v-if="b.schedule" class="flex items-center gap-1">
                                                <TrendingUp class="h-3 w-3" />{{ b.schedule }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Payment status -->
                                    <div class="text-right shrink-0">
                                        <div v-if="b.payment_status === 'paid'"
                                            class="flex items-center gap-1 text-xs text-secondary border-1 px-2 py-1 rounded-2xl">
                                            <CheckCircle2 class="h-3.5 w-3.5" />Paid
                                        </div>
                                        <div v-else class="space-y-1.5">
                                            <div class="flex items-center gap-1 text-xs text-secondary border-1 px-2 py-1 rounded-2xl">
                                                <Clock class="h-3.5 w-3.5" />Pending
                                            </div>
                                            <Button v-if="b.batch_status === 'completed'"
                                                size="sm" variant="outline"
                                                class="text-xs h-7 gap-1"
                                                :disabled="markingPaidId === b.batch_id"
                                                @click="handleMarkPaid(b)">
                                                <CheckCircle2 class="h-3 w-3" />
                                                {{ markingPaidId === b.batch_id ? 'Saving…' : 'Mark Paid' }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right sidebar -->
                <div class="space-y-4">

                    <!-- Payment agreement -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <DollarSign class="h-4 w-4 text-primary" />Payment Agreement
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 pt-0">
                            <div v-if="instructor.payment_agreement" class="space-y-2">
                                <div class="rounded-lg bg-muted/50 p-3">
                                    <p class="text-xs text-muted-foreground">Structure</p>
                                    <p class="text-sm font-semibold text-foreground mt-0.5">
                                        {{ paymentLabel(instructor.payment_agreement.type) }}
                                    </p>
                                    <p class="text-lg font-bold text-primary mt-1">
                                        {{ fmtNaira(instructor.payment_agreement.amount) }}
                                        <span class="text-xs font-normal text-muted-foreground">
                                            {{ paymentSuffix(instructor.payment_agreement.type) }}
                                        </span>
                                    </p>
                                </div>
                                <p v-if="instructor.payment_agreement.terms"
                                    class="text-xs text-muted-foreground leading-relaxed">
                                    {{ instructor.payment_agreement.terms }}
                                </p>
                                <div class="space-y-1.5 pt-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-muted-foreground">Expected</span>
                                        <span class="font-medium">
                                            {{ fmtNaira(instructor.earnings.total_expected) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-muted-foreground">Paid out</span>
                                        <span class="font-medium text-secondary">
                                            {{ fmtNaira(instructor.earnings.total_paid) }}
                                        </span>
                                    </div>
                                    <div v-if="instructor.earnings.has_pending"
                                        class="flex justify-between text-xs pt-1 border-t border-border">
                                        <span class="font-semibold text-muted-foreground">Balance due</span>
                                        <span class="font-bold text-secondary">
                                            {{ fmtNaira(instructor.earnings.balance_due) }}
                                        </span>
                                    </div>
                                    <div v-else class="flex justify-between text-xs pt-1 border-t border-border">
                                        <span class="font-semibold text-muted-foreground">Balance due</span>
                                        <span class="flex items-center gap-1 text-secondary-600 font-bold">
                                            <CheckCircle2 class="h-3 w-3" />All paid
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else
                                class="rounded-xl border border-dashed border-border p-4 text-center">
                                <p class="text-xs text-muted-foreground">No payment agreement set</p>
                            </div>
                            <p class="text-[10px] text-muted-foreground">
                                Payments are handled directly by you outside the platform.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Also teaches elsewhere -->
                    <Card v-if="instructor.other_schools > 0">
                        <CardHeader>
                            <CardTitle class="text-sm flex items-center gap-2">
                                <Building2 class="h-4 w-4 text-muted-foreground" />Also Teaching At
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <p class="text-sm text-muted-foreground">
                                This instructor teaches at
                                <strong class="text-foreground">{{ instructor.other_schools }}</strong>
                                other school{{ instructor.other_schools !== 1 ? 's' : '' }}.
                            </p>
                            <p class="text-[10px] text-muted-foreground mt-2">
                                Data from other schools is not visible to you.
                            </p>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>

        <!-- Remove confirmation -->
        <AlertDialog :open="showRemove" @update:open="showRemove = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove this instructor?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong>{{ instructor.name }}</strong> will be removed from your school.
                        Their account remains active — they won't lose access to other schools.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isRemoving">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isRemoving"
                        @click="handleRemove">
                        {{ isRemoving ? 'Removing…' : 'Remove' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </DashboardLayout>
</template>