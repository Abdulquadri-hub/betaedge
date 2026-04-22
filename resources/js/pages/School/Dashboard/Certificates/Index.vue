<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Award, Search,  Trash2, ExternalLink,
    RefreshCw,  MoreVertical,
} from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
// import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    certificates: { type: Array,  default: () => [] },
    filters:      { type: Object, default: () => ({}) },
    batches:      { type: Object, default: () => ({}) },
    stats:        { type: Object, default: () => ({}) },
    pagination:   { type: Object, default: null },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const search       = ref(props.filters.search ?? '')
const batchId      = ref(props.filters.batchId ?? '')
const generating   = ref(null)
const revokeTarget = ref(null)
const showRevoke   = ref(false)
const isRevoking   = ref(false)

function applyFilters() {
    router.get(window.location.pathname, {
        search:   search.value || undefined,
        batch_id: batchId.value || undefined,
    }, { preserveState: true })
}

function generateForBatch(id) {
    generating.value = id
    router.post(`/dashboard/certificates/batch/${id}/generate`, {}, {
        preserveScroll: true,
        onFinish: () => { generating.value = null },
    })
}

function confirmRevoke(cert) {
    revokeTarget.value = cert
    showRevoke.value   = true
}

function handleRevoke() {
    if (!revokeTarget.value) return
    isRevoking.value = true
    router.post(`/dashboard/certificates/${revokeTarget.value.id}/revoke`, {}, {
        preserveScroll: true,
        onSuccess: () => { showRevoke.value = false },
        onFinish: () => { isRevoking.value = false },
    })
}

function gradeColor(g) {
    if (!g) return 'text-muted-foreground'
    if (g >= 90) return 'text-emerald-600'
    if (g >= 70) return 'text-primary'
    if (g >= 60) return 'text-amber-600'
    return 'text-destructive'
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Certificates</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Issue and manage completion certificates for your students.
                    </p>
                </div>
                <!-- Generate for a batch via dropdown -->
                <div class="flex items-center gap-2">
                    <Select :model-value="batchId" @update:model-value="v => batchId = v">
                        <SelectTrigger class="w-52">
                            <SelectValue placeholder="Select batch to generate" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="(name, id) in batches" :key="id" :value="String(id)">{{ name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button :disabled="!batchId || generating === batchId" class="gap-2"
                        @click="generateForBatch(batchId)">
                        <RefreshCw v-if="generating === batchId" class="h-4 w-4 animate-spin" />
                        <Award v-else class="h-4 w-4" />
                        Generate
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Total Issued</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">This Month</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ stats?.this_month ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Batches Certified</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ stats?.batches_with_certs ?? 0 }}</p>
                </div>
            </div>

            <!-- Search -->
            <div class="flex gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search by name or code…" class="pl-9"
                        @keydown.enter="applyFilters" />
                </div>
                <Button variant="outline" @click="applyFilters">Search</Button>
            </div>

            <!-- Empty -->
            <div v-if="certificates.length === 0"
                class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                <Award class="h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="text-sm font-medium">No certificates issued yet</p>
                <p class="text-xs text-muted-foreground mt-1">
                    Select a completed batch and click "Generate" to issue certificates.
                </p>
            </div>

            <!-- Table -->
            <Card v-else>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Certificate Code</TableHead>
                            <TableHead>Student</TableHead>
                            <TableHead>Batch</TableHead>
                            <TableHead class="text-center">Grade</TableHead>
                            <TableHead class="text-center">Attendance</TableHead>
                            <TableHead>Issued</TableHead>
                            <TableHead class="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="cert in certificates" :key="cert.id" class="hover:bg-muted/50">
                            <TableCell>
                                <span class="font-mono text-xs font-semibold text-primary">{{ cert.certificate_code }}</span>
                            </TableCell>
                            <TableCell>
                                <p class="text-sm font-medium text-foreground">{{ cert.student_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ cert.student_email }}</p>
                            </TableCell>
                            <TableCell>
                                <p class="text-sm text-foreground truncate max-w-[160px]">{{ cert.batch_name }}</p>
                            </TableCell>
                            <TableCell class="text-center">
                                <span v-if="cert.final_grade" :class="['text-sm font-bold', gradeColor(cert.final_grade)]">
                                    {{ cert.grade_letter }} ({{ cert.final_grade }}%)
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-center">
                                <span v-if="cert.attendance_rate !== null" class="text-sm font-medium">
                                    {{ cert.attendance_rate }}%
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell>
                                <span class="text-xs text-muted-foreground">{{ cert.issued_at }}</span>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-7 w-7">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <a :href="cert.verification_url" target="_blank" rel="noopener noreferrer"
                                                class="flex items-center gap-2">
                                                <ExternalLink class="h-4 w-4" />View Public Page
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            @click="confirmRevoke(cert)">
                                            <Trash2 class="mr-2 h-4 w-4" />Revoke
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <!-- Revoke dialog -->
            <AlertDialog :open="showRevoke" @update:open="showRevoke = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Revoke this certificate?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Certificate <strong>{{ revokeTarget?.certificate_code }}</strong> issued to
                            <strong>{{ revokeTarget?.student_name }}</strong> will be permanently removed.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isRevoking">Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="isRevoking" @click="handleRevoke">
                            <RefreshCw v-if="isRevoking" class="mr-2 h-4 w-4 animate-spin" />
                            Revoke
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </DashboardLayout>
</template>