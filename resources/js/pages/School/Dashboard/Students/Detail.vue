<script setup>
import { watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ArrowLeft, 
    GraduationCap,UserX, UserCheck,
    // Users, CreditCard, CheckCircle2, AlertCircle, User, Mail, Phone, Calendar, 
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    student:     { type: Object, required: true },
    enrollments: { type: Array,  default: () => [] },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtNaira(n) {
    if (!n) return '₦0'
    return '₦' + Number(n).toLocaleString('en-NG')
}

function paymentBadge(status) {
    return { completed: 'default', pending: 'secondary', failed: 'destructive' }[status] ?? 'outline'
}

function handleSuspend() {
    router.post(`/dashboard/students/${props.student.id}/suspend`, {}, { preserveScroll: true })
}

function handleActivate() {
    router.post(`/dashboard/students/${props.student.id}/activate`, {}, { preserveScroll: true })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-start gap-4">
                <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0"
                    @click="router.visit('/dashboard/students')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex items-start gap-4 flex-1">
                    <Avatar class="h-16 w-16 shrink-0">
                        <AvatarFallback class="text-lg bg-primary/10 text-primary font-bold">
                            {{ initials(student.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-bold text-foreground">{{ student.name }}</h1>
                            <Badge :variant="student.enrollment_status === 'active' ? 'default' : 'secondary'"
                                class="text-xs capitalize">
                                {{ student.enrollment_status }}
                            </Badge>
                            <Badge v-if="student.has_parent" variant="outline" class="text-xs">Parent linked</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground mt-0.5">{{ student.email }}</p>
                        <p class="text-xs text-muted-foreground font-mono">ID: {{ student.student_id }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button v-if="student.enrollment_status === 'active'"
                        variant="outline" size="sm" class="gap-2 text-destructive border-destructive/30"
                        @click="handleSuspend">
                        <UserX class="h-4 w-4" />Suspend
                    </Button>
                    <Button v-else size="sm" class="gap-2" @click="handleActivate">
                        <UserCheck class="h-4 w-4" />Activate
                    </Button>
                </div>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Academic Level</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ student.academic_level ?? '—' }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Age</p>
                        <p class="text-lg font-bold text-foreground mt-1">
                            {{ student.age ?? '—' }}
                            <span v-if="student.age" class="text-xs font-normal text-muted-foreground">yrs</span>
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Active Enrollments</p>
                        <p class="text-2xl font-bold text-primary mt-1">{{ student.active_enrollments }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Joined</p>
                        <p class="text-sm font-semibold text-foreground mt-1">{{ student.enrolled_at ?? '—' }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs -->
            <Tabs default-value="enrollments">
                <TabsList>
                    <TabsTrigger value="enrollments">
                        Enrollments
                        <Badge variant="secondary" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ enrollments.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="info">Personal Info</TabsTrigger>
                </TabsList>

                <!-- Enrollments -->
                <TabsContent value="enrollments" class="mt-4">
                    <div v-if="enrollments.length === 0"
                        class="flex flex-col items-center py-12 text-center rounded-xl border border-dashed border-border">
                        <GraduationCap class="h-8 w-8 text-muted-foreground/40 mb-2" />
                        <p class="text-sm text-muted-foreground">No enrollments yet</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="e in enrollments" :key="e.id"
                            class="flex items-center gap-4 rounded-xl border border-border bg-card p-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <p class="text-sm font-semibold text-foreground truncate">{{ e.batch_name }}</p>
                                    <Badge :variant="e.status === 'active' ? 'default' : 'secondary'"
                                        class="text-xs capitalize">{{ e.status }}</Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">{{ e.courses }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">Enrolled {{ e.enrolled_at }}</p>
                            </div>
                            <div class="shrink-0 text-right space-y-1">
                                <Badge :variant="paymentBadge(e.payment_status)" class="text-xs capitalize">
                                    {{ e.payment_status }}
                                </Badge>
                                <p class="text-sm font-semibold text-foreground">{{ fmtNaira(e.amount_paid) }}</p>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <!-- Personal info -->
                <TabsContent value="info" class="mt-4">
                    <Card>
                        <CardContent class="p-5 space-y-4">
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Full Name</p>
                                    <p class="font-semibold text-foreground">{{ student.name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Email</p>
                                    <p class="text-foreground">{{ student.email }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Phone</p>
                                    <p class="text-foreground">{{ student.phone ?? '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Date of Birth</p>
                                    <p class="text-foreground">{{ student.date_of_birth ?? '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Student ID</p>
                                    <p class="font-mono text-foreground">{{ student.student_id }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-muted-foreground">Gender</p>
                                    <p class="text-foreground capitalize">{{ student.gender ?? '—' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </DashboardLayout>
</template>