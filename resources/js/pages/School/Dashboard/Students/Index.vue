<script setup>

import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Users, Search, Download, Filter,
    CheckCircle2, AlertCircle, UserX, UserCheck,
    Eye, MessageCircle, MoreVertical, Baby,
    Wallet, BookOpen, Shield, RefreshCw,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useStudentsManager } from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const {
    filteredStudents, isLoading, counts,
    search, filterStatus, filterType,
    formatNaira, initials,
    suspendStudent, activateStudent,
} = useStudentsManager()



// ─── Status config ────────────────────────────────────────────────────────────
const statusCfg = {
    active: { label: 'Active', variant: 'default', icon: CheckCircle2 },
    inactive: { label: 'Inactive', variant: 'secondary', icon: AlertCircle },
    suspended: { label: 'Suspended', variant: 'destructive', icon: UserX },
}

function fmtDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ─── Suspend ──────────────────────────────────────────────────────────────────
const showSuspendDialog = ref(false)
const targetStudent = ref(null)
const isActioning = ref(false)

function confirmSuspend(student) {
    targetStudent.value = student
    showSuspendDialog.value = true
}

async function handleSuspend() {
    if (!targetStudent.value) return
    isActioning.value = true
    try {
        await suspendStudent(targetStudent.value.id)
        toast({ title: 'Student suspended', description: `${targetStudent.value.name} has been suspended.` })
        showSuspendDialog.value = false
    } finally {
        isActioning.value = false
    }
}

async function handleActivate(student) {
    await activateStudent(student.id)
    toast({ title: 'Student activated', description: `${student.name} is now active.` })
}

function exportStudents() {
    // TODO (Laravel 12): window.location.href = route('dashboard.students.export')
    toast({ title: 'Export started', description: 'Student list CSV will download shortly.' })
}
</script>

<template>
    <DashboardLayout>
    <div class="p-6 max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground tracking-tight">Students</h1>
                <p class="text-sm text-muted-foreground mt-1">Manage all enrolled students across your school.</p>
            </div>
            <Button variant="outline" class="gap-2 shrink-0" @click="exportStudents">
                <Download class="h-4 w-4" />Export CSV
            </Button>
        </div>

        <!-- Stat tiles -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="(tile, k) in [
                { label: 'Total Students', value: counts.all, color: 'text-foreground' },
                { label: 'Active', value: counts.active, color: 'text-primary' },
                { label: 'Suspended', value: counts.suspended, color: 'text-destructive' },
                { label: 'Minor Students', value: counts.children, color: 'text-secondary' },
            ]" :key="k" class="rounded-xl border border-border bg-card p-4">
                <p class="text-xs text-muted-foreground font-medium">{{ tile.label }}</p>
                <p :class="['text-2xl font-bold mt-1', tile.color]">{{ tile.value }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3">
            <Tabs :model-value="filterStatus" class="flex-1" @update:model-value="filterStatus = $event">
                <TabsList>
                    <TabsTrigger value="all">All <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{
                            counts.all }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="active">Active</TabsTrigger>
                    <TabsTrigger value="suspended">Suspended</TabsTrigger>
                </TabsList>
            </Tabs>
            <div class="relative w-full sm:w-64">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Search name or email..." class="pl-9" />
            </div>
        </div>

        <!-- Table -->
        <Card>
            <CardContent class="p-0">
                <!-- Loading -->
                <div v-if="isLoading" class="p-6 space-y-3">
                    <div v-for="i in 5" :key="i" class="h-12 bg-muted rounded animate-pulse" />
                </div>

                <!-- Empty -->
                <div v-else-if="filteredStudents.length === 0" class="py-16 text-center">
                    <Users class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p class="text-sm font-medium text-foreground">No students found</p>
                    <p class="text-xs text-muted-foreground mt-1">Try adjusting your search or filters.</p>
                </div>

                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Student</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="hidden md:table-cell">Courses</TableHead>
                            <TableHead class="hidden lg:table-cell">Total Paid</TableHead>
                            <TableHead class="hidden lg:table-cell">Grade</TableHead>
                            <TableHead class="hidden md:table-cell">Enrolled</TableHead>
                            <TableHead class="w-12" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="s in filteredStudents" :key="s.id" class="hover:bg-muted/50 cursor-pointer"
                            @click="router.visit(`/dashboard/students/${s.id}`)">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-8 w-8">
                                        <AvatarFallback class="text-xs font-bold bg-primary/10 text-primary">
                                            {{ initials(s.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ s.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ s.email }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="s.type === 'child' ? 'secondary' : 'outline'" class="text-xs gap-1">
                                    <Baby v-if="s.type === 'child'" class="h-3 w-3" />
                                    {{ s.type === 'child' ? 'Minor' : 'Adult' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="statusCfg[s.status]?.variant ?? 'outline'" class="text-xs gap-1">
                                    <component :is="statusCfg[s.status]?.icon" class="h-3 w-3" />
                                    {{ statusCfg[s.status]?.label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="hidden md:table-cell">
                                <span class="text-sm text-foreground">{{ s.enrolled_courses.length }}</span>
                                <span class="text-xs text-muted-foreground ml-1">course{{ s.enrolled_courses.length !==
                                    1 ? 's' : '' }}</span>
                            </TableCell>
                            <TableCell class="hidden lg:table-cell text-sm font-medium text-emerald-600">
                                {{ formatNaira(s.total_paid) }}
                            </TableCell>
                            <TableCell class="hidden lg:table-cell">
                                <span
                                    :class="['text-sm font-bold', s.overall_grade >= 70 ? 'text-primary' : s.overall_grade >= 50 ? 'text-amber-600' : 'text-destructive']">
                                    {{ s.overall_grade != null ? s.overall_grade + '%' : '—' }}
                                </span>
                            </TableCell>
                            <TableCell class="hidden md:table-cell text-xs text-muted-foreground">
                                {{ fmtDate(s.enrolled_at) }}
                            </TableCell>
                            <TableCell @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click.stop="router.visit(`/dashboard/students/${s.id}`)">
                                            <Eye class="mr-2 h-4 w-4" />View Profile
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem v-if="s.status === 'active'"
                                            class="text-destructive focus:text-destructive"
                                            @click.stop="confirmSuspend(s)">
                                            <UserX class="mr-2 h-4 w-4" />Suspend Student
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="s.status === 'suspended'"
                                            @click.stop="handleActivate(s)">
                                            <UserCheck class="mr-2 h-4 w-4" />Reactivate
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Suspend dialog -->
        <AlertDialog :open="showSuspendDialog" @update:open="showSuspendDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Suspend student?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong class="text-foreground">{{ targetStudent?.name }}</strong> will lose access to all
                        active courses and batches. You can reactivate them at any time.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isActioning">Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isActioning" @click="handleSuspend">
                        <RefreshCw v-if="isActioning" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isActioning ? 'Suspending...' : 'Suspend Student' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </div>
    </DashboardLayout>
</template>