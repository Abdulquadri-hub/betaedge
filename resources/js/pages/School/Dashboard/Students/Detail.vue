<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    ArrowLeft, UserX, UserCheck, Mail, Phone,
    BookOpen, Calendar, Award, TrendingUp, Baby,
    Shield, AlertCircle, CheckCircle2, Clock,
    MessageCircle, ExternalLink, BarChart2, RefreshCw,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useStudentsManager } from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'

const { getStudentById, formatNaira, initials, suspendStudent, activateStudent } = useStudentsManager()


// TODO (Laravel 12): const props = defineProps({ student: Object })
const studentId = ref('stu-003')
const student = computed(() => getStudentById(studentId.value))

// Mock enrollment/progress data
const enrollments = ref([
    {
        course_name: 'Data Science & Analytics', batch_name: 'Data Science Batch 1',
        status: 'active', grade: 85, attendance_rate: 95,
        sessions_attended: 6, total_sessions: 8,
        assignments_submitted: 4, total_assignments: 5,
        paid: 75000, enrolled_at: '2026-01-20',
    },
])

// ─── Suspend / activate ───────────────────────────────────────────────────────
const showSuspendDialog = ref(false)
const isActioning = ref(false)

async function handleSuspend() {
    if (!student.value) return
    isActioning.value = true
    try {
        await suspendStudent(student.value.id)
        toast({ title: 'Student suspended' })
        showSuspendDialog.value = false
    } finally {
        isActioning.value = false
    }
}

async function handleActivate() {
    if (!student.value) return
    await activateStudent(student.value.id)
    toast({ title: 'Student reactivated' })
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function gradeColor(g) {
    if (g >= 80) return 'text-emerald-600'
    if (g >= 60) return 'text-amber-600'
    return 'text-destructive'
}
</script>

<template>
    <div v-if="student" class="p-6 max-w-5xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex items-start gap-4">
            <Button variant="ghost" size="icon" class="h-9 w-9 shrink-0" @click="router.visit('/dashboard/students')">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div class="flex-1 min-w-0 flex items-center gap-4">
                <Avatar class="h-14 w-14">
                    <AvatarFallback class="text-lg font-bold bg-primary/10 text-primary">{{ initials(student.name) }}
                    </AvatarFallback>
                </Avatar>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-bold text-foreground">{{ student.name }}</h1>
                        <Badge :variant="student.type === 'child' ? 'secondary' : 'outline'" class="text-xs gap-1">
                            <Baby v-if="student.type === 'child'" class="h-3 w-3" />
                            {{ student.type === 'child' ? 'Minor' : 'Adult' }}
                        </Badge>
                        <Badge :variant="student.status === 'active' ? 'default' : 'destructive'" class="text-xs">
                            {{ student.status }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-4 mt-1 text-xs text-muted-foreground flex-wrap">
                        <span class="flex items-center gap-1">
                            <Mail class="h-3 w-3" />{{ student.email }}
                        </span>
                        <span class="flex items-center gap-1">
                            <Phone class="h-3 w-3" />{{ student.phone }}
                        </span>
                        <span>Enrolled {{ fmtDate(student.enrolled_at) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <Button v-if="student.status === 'active'" variant="outline" size="sm"
                    class="gap-2 text-destructive border-destructive/30 hover:bg-destructive/5"
                    @click="showSuspendDialog = true">
                    <UserX class="h-4 w-4" />Suspend
                </Button>
                <Button v-else variant="outline" size="sm" class="gap-2" @click="handleActivate">
                    <UserCheck class="h-4 w-4" />Reactivate
                </Button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Total Paid</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ formatNaira(student.total_paid) }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Courses Enrolled</p>
                    <p class="text-xl font-bold text-foreground mt-1">{{ student.enrolled_courses.length }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Sessions Attended</p>
                    <p class="text-xl font-bold text-foreground mt-1">{{ student.total_sessions_attended }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground font-medium">Overall Grade</p>
                    <p :class="['text-xl font-bold mt-1', gradeColor(student.overall_grade)]">
                        {{ student.overall_grade != null ? student.overall_grade + '%' : '—' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Enrollments -->
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-sm font-semibold text-foreground">Course Enrollments</h2>
                <Card v-for="(enr, i) in enrollments" :key="i">
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div>
                                <p class="text-sm font-semibold text-foreground">{{ enr.course_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ enr.batch_name }}</p>
                            </div>
                            <Badge variant="default" class="text-xs shrink-0">{{ enr.status }}</Badge>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Grade -->
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Overall Grade</p>
                                <p :class="['text-lg font-bold', gradeColor(enr.grade)]">{{ enr.grade }}%</p>
                            </div>
                            <!-- Attendance -->
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-muted-foreground">Attendance</span>
                                    <span class="font-medium">{{ enr.sessions_attended }}/{{ enr.total_sessions
                                        }}</span>
                                </div>
                                <Progress :value="(enr.sessions_attended / enr.total_sessions) * 100" class="h-1.5" />
                                <p class="text-[10px] text-muted-foreground mt-0.5">{{ enr.attendance_rate }}%</p>
                            </div>
                            <!-- Assignments -->
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-muted-foreground">Assignments</span>
                                    <span class="font-medium">{{ enr.assignments_submitted }}/{{ enr.total_assignments
                                        }}</span>
                                </div>
                                <Progress :value="(enr.assignments_submitted / enr.total_assignments) * 100"
                                    class="h-1.5"  />
                            </div>
                            <!-- Paid -->
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Amount Paid</p>
                                <p class="text-sm font-bold text-emerald-600">{{ formatNaira(enr.paid) }}</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-border flex justify-end">
                            <Button variant="outline" size="sm" class="gap-2 text-xs h-8"
                                @click="router.visit(`/dashboard/batches/${student.current_batches[0]}`)">
                                <ExternalLink class="h-3.5 w-3.5" />View Batch
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar: parent + personal info -->
            <div class="space-y-4">
                <!-- Parent info (minors only) -->
                <Card v-if="student.parent">
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <Shield class="h-4 w-4 text-secondary" />Parent / Guardian
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 pt-0">
                        <div class="flex items-center gap-3">
                            <Avatar class="h-8 w-8">
                                <AvatarFallback class="text-xs font-bold bg-secondary/10 text-secondary">
                                    {{ initials(student.parent.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ student.parent.name }}</p>
                                <p class="text-xs text-muted-foreground capitalize">{{ student.parent.relationship }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <p class="flex items-center gap-2 text-xs text-muted-foreground">
                                <Mail class="h-3.5 w-3.5" />{{ student.parent.email }}
                            </p>
                            <p class="flex items-center gap-2 text-xs text-muted-foreground">
                                <Phone class="h-3.5 w-3.5" />{{ student.parent.phone }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- No parent for adult -->
                <Card v-else>
                    <CardHeader>
                        <CardTitle class="text-sm">Student Info</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 pt-0 text-xs text-muted-foreground">
                        <p class="flex items-center gap-2">
                            <Mail class="h-3.5 w-3.5" />{{ student.email }}
                        </p>
                        <p class="flex items-center gap-2">
                            <Phone class="h-3.5 w-3.5" />{{ student.phone }}
                        </p>
                        <p class="flex items-center gap-2">
                            <Calendar class="h-3.5 w-3.5" />Enrolled {{ fmtDate(student.enrolled_at) }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Performance summary -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm flex items-center gap-2">
                            <BarChart2 class="h-4 w-4 text-primary" />Performance
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 pt-0">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-muted-foreground">Overall Grade</span>
                                <span :class="['font-semibold', gradeColor(student.overall_grade)]">{{
                                    student.overall_grade ?? '—' }}%</span>
                            </div>
                            <Progress :value="student.overall_grade ?? 0" class="h-1.5" />
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Active Batches</span>
                            <span class="font-medium text-foreground">{{ student.current_batches.length }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Sessions Attended</span>
                            <span class="font-medium text-foreground">{{ student.total_sessions_attended }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Suspend dialog -->
        <AlertDialog :open="showSuspendDialog" @update:open="showSuspendDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Suspend {{ student.name }}?</AlertDialogTitle>
                    <AlertDialogDescription>
                        They will lose access to all active courses immediately. You can reactivate them at any time.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isActioning" @click="handleSuspend">
                        <RefreshCw v-if="isActioning" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isActioning ? 'Suspending...' : 'Suspend' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </div>
</template>