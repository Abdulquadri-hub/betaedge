<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    ArrowLeft, Trash2, Star, CheckCircle2,
    Clock, DollarSign, School, Shield,
    Mail, Phone, BookOpen, Users, TrendingUp,
    AlertCircle,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
// import { Progress } from '@/components/ui/progress'
import {
    useInstructorsManager,
    INSTRUCTOR_PERMISSIONS,
    // PAYMENT_STRUCTURES,
} from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const { getInstructorById, formatNaira, formatPaymentLine, paymentLabel, initials, markInstructorPaid, removeInstructor } = useInstructorsManager()


// TODO (Laravel 12): const props = defineProps({ instructor: Object })
const instructorId = ref('inst-001')
const instructor = computed(() => getInstructorById(instructorId.value))

// Mock batch history for this instructor
const batchHistory = ref([
    { id: 'batch-001', name: 'Web Dev Batch 3', course: 'Full Stack Web Development', status: 'active', students: 24, completion_rate: null, payment_status: 'pending' },
    { id: 'batch-004', name: 'Excel Mastery Batch 1', course: 'Microsoft Excel Mastery', status: 'completed', students: 33, completion_rate: 87, payment_status: 'paid' },
])

// function permissionLabel(val) {
//     return INSTRUCTOR_PERMISSIONS.find(p => p.value === val)?.label ?? val
// }

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function handleMarkPaid(batch) {
    if (!instructor.value) return
    await markInstructorPaid(instructor.value.id, batch.id)
    batch.payment_status = 'paid'
    toast({ title: 'Payment recorded', description: `Payment for ${batch.name} marked as complete.` })
}

async function handleRemove() {
    if (!instructor.value) return
    await removeInstructor(instructor.value.id)
    toast({ title: 'Instructor removed' })
    router.visit('/dashboard/instructors')
}

const batchStatusCfg = {
    open: { label: 'Enrolling', variant: 'default' },
    active: { label: 'In Progress', variant: 'secondary' },
    completed: { label: 'Completed', variant: 'outline' },
    closed: { label: 'Closed', variant: 'outline' },
}
</script>

<template>
    <DashboardLayout>
    <div v-if="instructor" class="p-6 max-w-5xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-start gap-4">
            <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                @click="router.visit('/dashboard/instructors')">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div class="flex-1 min-w-0 flex items-center gap-4">
                <Avatar class="h-14 w-14">
                    <AvatarFallback class="text-lg font-bold bg-primary/10 text-primary">{{ initials(instructor.name) }}
                    </AvatarFallback>
                </Avatar>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground">{{ instructor.name }}</h1>
                        <div v-if="instructor.avg_rating" class="flex items-center gap-1 text-sm text-amber-600">
                            <Star class="h-4 w-4 fill-amber-500" />{{ instructor.avg_rating }}
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
                        <span class="flex items-center gap-1">
                            <Phone class="h-3 w-3" />{{ instructor.phone }}
                        </span>
                        <span>Joined {{ fmtDate(instructor.joined_at) }}</span>
                    </div>
                </div>
            </div>
            <Button variant="outline" size="sm"
                class="gap-2 text-destructive border-destructive/30 hover:bg-destructive/5 shrink-0"
                @click="handleRemove">
                <Trash2 class="h-4 w-4" />Remove
            </Button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Active Batches</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ instructor.active_batches_count }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Completed</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ instructor.completed_batches_count }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Completion Rate</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ instructor.avg_completion_rate != null ?
                        instructor.avg_completion_rate + '%' : '—' }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Total Earned</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatNaira(instructor.payment?.total_earned
                        ?? 0) }}</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: batches + bio -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Bio -->
                <Card v-if="instructor.bio">
                    <CardHeader>
                        <CardTitle class="text-sm">About</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <p class="text-sm text-muted-foreground leading-relaxed">{{ instructor.bio }}</p>
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            <Badge v-for="s in instructor.specialties" :key="s" variant="outline" class="text-xs">{{ s
                                }}</Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Batch history + payment tracking -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <BookOpen class="h-4 w-4 text-primary" />Batch History & Payments
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-3">
                        <div v-if="batchHistory.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No batches assigned yet.
                        </div>
                        <div v-for="b in batchHistory" :key="b.id" class="rounded-lg border border-border p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-semibold text-foreground">{{ b.name }}</p>
                                        <Badge :variant="batchStatusCfg[b.status]?.variant ?? 'outline'"
                                            class="text-xs">
                                            {{ batchStatusCfg[b.status]?.label }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ b.course }}</p>
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground mt-1">
                                        <span class="flex items-center gap-1">
                                            <Users class="h-3 w-3" />{{ b.students }} students
                                        </span>
                                        <span v-if="b.completion_rate" class="flex items-center gap-1">
                                            <TrendingUp class="h-3 w-3" />{{ b.completion_rate }}% completion
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div v-if="b.payment_status === 'paid'"
                                        class="flex items-center gap-1 text-xs text-emerald-600">
                                        <CheckCircle2 class="h-3.5 w-3.5" />Paid
                                    </div>
                                    <div v-else class="space-y-1">
                                        <div class="flex items-center gap-1 text-xs text-amber-600">
                                            <Clock class="h-3.5 w-3.5" />Pending
                                        </div>
                                        <Button v-if="b.status === 'completed'" size="sm" variant="outline"
                                            class="text-xs h-7 gap-1" @click="handleMarkPaid(b)">
                                            <CheckCircle2 class="h-3 w-3" />Mark Paid
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
                        <div class="rounded-lg bg-muted/50 p-3">
                            <p class="text-xs text-muted-foreground">Structure</p>
                            <p class="text-sm font-semibold text-foreground mt-0.5">{{
                                paymentLabel(instructor.payment?.structure) }}</p>
                            <p class="text-xs font-bold text-primary mt-1">{{ formatPaymentLine(instructor.payment) }}
                            </p>
                        </div>
                        <p v-if="instructor.payment?.additional_terms"
                            class="text-xs text-muted-foreground leading-relaxed">
                            {{ instructor.payment.additional_terms }}
                        </p>
                        <div class="flex justify-between text-xs pt-1">
                            <span class="text-muted-foreground">Paid batches</span>
                            <span class="font-medium text-foreground">{{ instructor.payment?.paid_batches ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Pending batches</span>
                            <span
                                :class="['font-medium', (instructor.payment?.pending_batches ?? 0) > 0 ? 'text-amber-600' : 'text-foreground']">
                                {{ instructor.payment?.pending_batches ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs pt-1 border-t border-border">
                            <span class="text-muted-foreground font-semibold">Total earned</span>
                            <span class="font-bold text-emerald-600">{{ formatNaira(instructor.payment?.total_earned ??
                                0) }}</span>
                        </div>
                        <p class="text-[10px] text-muted-foreground mt-1">Payments handled directly by you outside the
                            platform.</p>
                    </CardContent>
                </Card>

                <!-- Permissions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <Shield class="h-4 w-4 text-primary" />Permissions
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-1.5">
                        <div v-for="perm in INSTRUCTOR_PERMISSIONS" :key="perm.value"
                            class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">{{ perm.label }}</span>
                            <CheckCircle2 v-if="instructor.permissions.includes(perm.value)"
                                class="h-3.5 w-3.5 text-emerald-500" />
                            <span v-else class="text-[10px] text-muted-foreground/50">—</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Multi-school context -->
                <Card v-if="instructor.other_schools.length > 0">
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <School class="h-4 w-4 text-secondary" />Also Teaching At
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-1.5">
                        <div v-for="s in instructor.other_schools" :key="s"
                            class="flex items-center gap-2 text-xs text-muted-foreground">
                            <School class="h-3.5 w-3.5" />{{ s }}
                        </div>
                        <p class="text-[10px] text-muted-foreground pt-1">Data from other schools is not visible to you.
                        </p>
                    </CardContent>
                </Card>

            </div>
        </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex flex-col items-center justify-center min-h-[60vh] p-6 text-center">
        <AlertCircle class="h-10 w-10 text-muted-foreground/40 mb-3" />
        <p class="text-sm font-medium">Instructor not found</p>
        <Button class="mt-4 gap-2" variant="outline" @click="router.visit('/dashboard/instructors')">
            <ArrowLeft class="h-4 w-4" />Back
        </Button>
    </div>
    </DashboardLayout>
</template>