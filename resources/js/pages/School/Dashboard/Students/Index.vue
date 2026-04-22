<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Search, Users, MoreVertical, Eye,
    UserX, UserCheck, 
    // Download, RefreshCw, GraduationCap,
    // Calendar, Mail, Phone, Plus,,
} from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    students:   { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
    batches:    { type: Object, default: () => ({}) },
    stats:      { type: Object, default: () => ({}) },
    pagination: { type: Object, default: null },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const search  = ref(props.filters.search ?? '')
const batchId = ref(props.filters.batchId ?? '')
const status  = ref(props.filters.status ?? '')

function applyFilters() {
    router.get(window.location.pathname, {
        search:   search.value || undefined,
        batch_id: batchId.value || undefined,
        status:   status.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}

function handleSuspend(s) {
    router.post(`/dashboard/students/${s.id}/suspend`, {}, { preserveScroll: true })
}

function handleActivate(s) {
    router.post(`/dashboard/students/${s.id}/activate`, {}, { preserveScroll: true })
}

function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Students</h1>
                    <p class="text-sm text-muted-foreground mt-1">All students enrolled in your batches</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Total Students</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Active</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ stats?.active ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Enrolled this month</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ stats?.enrolled_this_month ?? 0 }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 sm:max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search by name or email…" class="pl-9"
                        @keydown.enter="applyFilters" />
                </div>
                <Select :model-value="batchId" @update:model-value="v => { batchId = v; applyFilters() }">
                    <SelectTrigger class="w-full sm:w-48">
                        <SelectValue placeholder="All batches" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All batches</SelectItem>
                        <SelectItem v-for="(name, id) in batches" :key="id" :value="String(id)">{{ name }}</SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" class="gap-2" @click="applyFilters">
                    <Search class="h-4 w-4" />Filter
                </Button>
            </div>

            <!-- Empty -->
            <div v-if="students.length === 0"
                class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                <Users class="h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="text-sm font-medium">No students found</p>
                <p class="text-xs text-muted-foreground mt-1">Students appear here once they complete enrollment and payment.</p>
            </div>

            <!-- Table -->
            <Card v-else>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Student</TableHead>
                            <TableHead>Level</TableHead>
                            <TableHead class="text-center">Enrollments</TableHead>
                            <TableHead>Enrolled</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="student in students" :key="student.id"
                            class="cursor-pointer hover:bg-muted/50"
                            @click="router.visit(`/dashboard/students/${student.id}`)">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-9 w-9 shrink-0">
                                        <AvatarFallback class="text-xs bg-primary/10 text-primary font-bold">
                                            {{ initials(student.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ student.name }}</p>
                                        <p class="text-xs text-muted-foreground truncate">{{ student.email }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-xs text-muted-foreground">{{ student.academic_level ?? '—' }}</span>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary" class="text-xs">{{ student.active_enrollments }}</Badge>
                            </TableCell>
                            <TableCell>
                                <span class="text-xs text-muted-foreground">{{ student.enrolled_at ?? '—' }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="student.enrollment_status === 'active' ? 'default' : 'secondary'"
                                    class="text-xs capitalize">
                                    {{ student.enrollment_status }}
                                </Badge>
                            </TableCell>
                            <TableCell @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-7 w-7">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="router.visit(`/dashboard/students/${student.id}`)">
                                            <Eye class="mr-2 h-4 w-4" />View Profile
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem v-if="student.enrollment_status === 'active'"
                                            class="text-destructive focus:text-destructive"
                                            @click="handleSuspend(student)">
                                            <UserX class="mr-2 h-4 w-4" />Suspend
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-else @click="handleActivate(student)">
                                            <UserCheck class="mr-2 h-4 w-4" />Activate
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <!-- Pagination -->
            <div v-if="pagination && pagination.last_page > 1"
                class="flex items-center justify-between text-sm text-muted-foreground">
                <span>Page {{ pagination.current_page }} of {{ pagination.last_page }} · {{ pagination.total }} students</span>
                <div class="flex gap-2">
                    <Button v-if="pagination.current_page > 1" variant="outline" size="sm"
                        @click="router.visit(window.location.pathname + '?page=' + (pagination.current_page - 1))">
                        Previous
                    </Button>
                    <Button v-if="pagination.current_page < pagination.last_page" variant="outline" size="sm"
                        @click="router.visit(window.location.pathname + '?page=' + (pagination.current_page + 1))">
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>