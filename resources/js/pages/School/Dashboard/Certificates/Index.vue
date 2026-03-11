<script setup>

import { ref, computed } from 'vue'
// import { router } from '@inertiajs/vue3'
import {
    Award, Download, Search, RefreshCw, CheckCircle2,
    ExternalLink,
    //Plus, Filter, 
    Users,
    //BookOpen,
    Eye,
    //AlertCircle, ChevronRight,
} from 'lucide-vue-next'
import {
    Card, CardContent,
    //CardHeader, CardTitle 
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
// import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const search = ref('')
const activeTab = ref('issued')

// ── Mock data ─────────────────────────────────────────────────────────────────
const completedBatches = ref([
    {
        id: 'batch-004', name: 'Excel Mastery Batch 1', course: 'Microsoft Excel Mastery',
        completed_at: '2025-11-30', total_enrolled: 33, eligible: 29, generated: 29,
        certificates_generated: true,
    },
    {
        id: 'batch-005', name: 'Web Dev Batch 2', course: 'Full Stack Web Development',
        completed_at: '2025-10-15', total_enrolled: 22, eligible: 18, generated: 0,
        certificates_generated: false,
    },
])

const issuedCerts = ref([
    {
        id: 'cert-001', certificate_id: 'BSA-EX-EB1-001',
        student_name: 'Ada Okonkwo', student_email: 'ada@gmail.com',
        course: 'Microsoft Excel Mastery', batch: 'Excel Mastery Batch 1',
        final_grade: 88, batch_rank: 2, total_students: 29,
        issue_date: '2025-12-01',
        verification_url: 'https://brightstars.teach.com/verify/BSA-EX-EB1-001',
    },
    {
        id: 'cert-002', certificate_id: 'BSA-EX-EB1-002',
        student_name: 'Emeka Nwosu', student_email: 'emeka@yahoo.com',
        course: 'Microsoft Excel Mastery', batch: 'Excel Mastery Batch 1',
        final_grade: 91, batch_rank: 1, total_students: 29,
        issue_date: '2025-12-01',
        verification_url: 'https://brightstars.teach.com/verify/BSA-EX-EB1-002',
    },
    {
        id: 'cert-003', certificate_id: 'BSA-EX-EB1-003',
        student_name: 'Chiamaka Eze', student_email: 'chiamaka@gmail.com',
        course: 'Microsoft Excel Mastery', batch: 'Excel Mastery Batch 1',
        final_grade: 76, batch_rank: 7, total_students: 29,
        issue_date: '2025-12-01',
        verification_url: 'https://brightstars.teach.com/verify/BSA-EX-EB1-003',
    },
])

// ── Filters ───────────────────────────────────────────────────────────────────
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return issuedCerts.value
    return issuedCerts.value.filter(c =>
        c.student_name.toLowerCase().includes(q) ||
        c.certificate_id.toLowerCase().includes(q) ||
        c.batch.toLowerCase().includes(q)
    )
})

// ── Generate dialog ───────────────────────────────────────────────────────────
const showGenerateDialog = ref(false)
const generatingBatch = ref(null)
const isGenerating = ref(false)

function openGenerate(batch) {
    generatingBatch.value = batch
    showGenerateDialog.value = true
}

async function handleGenerate() {
    if (!generatingBatch.value) return
    isGenerating.value = true
    try {
        await new Promise(r => setTimeout(r, 1200))
        /**
         * TODO (Laravel 12):
         * router.post(route('dashboard.certificates.generate'), {
         *   batch_id: generatingBatch.value.id
         * }, {
         *   onSuccess: () => { toast({ title: '🎉 Certificates generated!' }) },
         *   preserveScroll: true,
         * })
         */
        const b = completedBatches.value.find(b => b.id === generatingBatch.value.id)
        if (b) { b.certificates_generated = true; b.generated = b.eligible }
        toast({
            title: '🎉 Certificates generated!',
            description: `${generatingBatch.value.eligible} certificates issued. Students have been notified by email.`,
        })
        showGenerateDialog.value = false
    } finally {
        isGenerating.value = false
    }
}

// ── Preview dialog ────────────────────────────────────────────────────────────
const showPreview = ref(false)
const previewCert = ref(null)

function openPreview(cert) {
    previewCert.value = cert
    showPreview.value = true
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function gradeLabel(g) {
    if (g >= 90) return 'A+'
    if (g >= 80) return 'A'
    if (g >= 70) return 'B+'
    if (g >= 60) return 'B'
    return 'C'
}
function gradeColor(g) {
    return g >= 80 ? 'text-emerald-600' : g >= 60 ? 'text-amber-600' : 'text-destructive'
}
function fmtDate(iso) {
    return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'long', year: 'numeric' })
}
function initials(name) {
    return (name ?? '').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function downloadAll() {
    // TODO: window.location.href = route('dashboard.certificates.download-all')
    toast({ title: 'Download started', description: 'All certificates zipped and downloading...' })
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold text-foreground tracking-tight">Certificates</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Generate and manage completion certificates. Issued to students with grade ≥ 60%.
                    </p>
                </div>
                <Button variant="outline" class="gap-2" @click="downloadAll">
                    <Download class="h-4 w-4" />Download All
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="tile in [
                    { label: 'Total Issued', value: issuedCerts.length, icon: Award, color: 'text-primary' },
                    { label: 'Batches Processed', value: completedBatches.filter(b => b.certificates_generated).length, icon: CheckCircle2, color: 'text-emerald-600' },
                    { label: 'Pending Generation', value: completedBatches.filter(b => !b.certificates_generated).length, icon: RefreshCw, color: 'text-amber-600' },
                    { label: 'Eligible Students', value: completedBatches.reduce((s, b) => s + b.eligible, 0), icon: Users, color: 'text-foreground' },
                ]" :key="tile.label">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2">
                            <component :is="tile.icon" class="h-3.5 w-3.5" :class="tile.color" />
                            {{ tile.label }}
                        </div>
                        <p :class="['text-2xl font-bold', tile.color]">{{ tile.value }}</p>
                    </CardContent>
                </Card>
            </div>

            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="issued">
                        Issued
                        <Badge variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ issuedCerts.length }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="generate">
                        Generate
                        <Badge v-if="completedBatches.filter(b => !b.certificates_generated).length" variant="destructive"
                            class="ml-1.5 h-4 px-1 text-[10px]">
                            {{completedBatches.filter(b => !b.certificates_generated).length}}
                        </Badge>
                    </TabsTrigger>
                </TabsList>

                <!-- Issued tab -->
                <TabsContent value="issued" class="mt-4 space-y-4">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search by student name, certificate ID, or batch..."
                            class="pl-9" />
                    </div>

                    <div v-if="!filtered.length"
                        class="py-16 text-center rounded-xl border border-dashed border-border">
                        <Award class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                        <p class="text-sm font-medium text-foreground">No certificates found</p>
                    </div>

                    <div v-else class="space-y-3">
                        <Card v-for="cert in filtered" :key="cert.id"
                            class="group hover:border-primary/20 hover:shadow-sm transition-all cursor-pointer"
                            @click="openPreview(cert)">
                            <CardContent class="p-4">
                                <div class="flex items-center gap-4">
                                    <Avatar class="h-10 w-10 shrink-0">
                                        <AvatarFallback class="bg-primary/10 text-primary font-bold text-sm">
                                            {{ initials(cert.student_name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-foreground">{{ cert.student_name }}</p>
                                            <Badge variant="outline" class="text-[10px] font-mono">{{
                                                cert.certificate_id }}</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-0.5">
                                            {{ cert.course }} · {{ cert.batch }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">Issued {{ fmtDate(cert.issue_date) }}
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0 space-y-1">
                                        <p :class="['text-lg font-black', gradeColor(cert.final_grade)]">
                                            {{ cert.final_grade }}% <span class="text-sm">{{
                                                gradeLabel(cert.final_grade) }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">Rank #{{ cert.batch_rank }}/{{
                                            cert.total_students }}</p>
                                    </div>
                                    <div class="flex flex-col gap-1.5 shrink-0">
                                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7"
                                            @click.stop="openPreview(cert)">
                                            <Eye class="h-3.5 w-3.5" />Preview
                                        </Button>
                                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7" as-child
                                            @click.stop>
                                            <a :href="`/dashboard/certificates/${cert.id}/download`">
                                                <Download class="h-3.5 w-3.5" />PDF
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Generate tab -->
                <TabsContent value="generate" class="mt-4 space-y-4">
                    <div
                        class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 p-4 text-sm">
                        <p class="font-semibold text-amber-800 dark:text-amber-300">Certificates are issued
                            automatically to eligible students</p>
                        <p class="text-amber-700 dark:text-amber-400 mt-0.5 text-xs">
                            Any student who completed a batch with a final grade of 60% or higher qualifies. Click
                            Generate below for each batch.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <Card v-for="batch in completedBatches" :key="batch.id">
                            <CardContent class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <p class="text-sm font-semibold text-foreground">{{ batch.name }}</p>
                                            <Badge :variant="batch.certificates_generated ? 'default' : 'secondary'"
                                                class="text-xs gap-1">
                                                <CheckCircle2 v-if="batch.certificates_generated" class="h-3 w-3" />
                                                {{ batch.certificates_generated ? 'Generated' : 'Pending' }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ batch.course }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Completed {{ fmtDate(batch.completed_at) }}
                                        </p>
                                        <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
                                            <span><strong class="text-foreground">{{ batch.total_enrolled }}</strong>
                                                enrolled</span>
                                            <span><strong class="text-emerald-600">{{ batch.eligible }}</strong>
                                                eligible (≥60%)</span>
                                            <span v-if="batch.certificates_generated"><strong class="text-primary">{{
                                                    batch.generated }}</strong> issued</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <Button v-if="!batch.certificates_generated" class="gap-2"
                                            @click="openGenerate(batch)">
                                            <Award class="h-4 w-4" />Generate Certificates
                                        </Button>
                                        <Button v-else variant="outline" class="gap-2" @click="downloadAll">
                                            <Download class="h-4 w-4" />Download All
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>

            <!-- Generate confirm dialog -->
            <AlertDialog :open="showGenerateDialog" @update:open="showGenerateDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Generate Certificates?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will generate <strong>{{ generatingBatch?.eligible }}</strong> certificates for
                            eligible students
                            in <strong>{{ generatingBatch?.name }}</strong>. Each student will receive an email with
                            their
                            certificate download link. This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isGenerating">Cancel</AlertDialogCancel>
                        <AlertDialogAction :disabled="isGenerating" @click="handleGenerate">
                            <RefreshCw v-if="isGenerating" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isGenerating ? 'Generating...' : '🎉 Generate Now' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <!-- Certificate preview dialog -->
            <Dialog :open="showPreview" @update:open="showPreview = $event">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Certificate Preview</DialogTitle>
                        <DialogDescription class="text-xs">How this certificate appears to the student.
                        </DialogDescription>
                    </DialogHeader>
                    <div v-if="previewCert"
                        class="rounded-xl border-2 border-primary/30 bg-gradient-to-br from-primary/5 to-secondary/5 p-6 text-center space-y-3 font-serif">
                        <p class="text-xs font-sans font-bold text-muted-foreground uppercase tracking-widest">Bright
                            Stars Academy</p>
                        <p class="text-lg font-bold text-foreground">Certificate of Completion</p>
                        <p class="text-xs text-muted-foreground">This is to certify that</p>
                        <p class="text-xl font-black text-primary uppercase">{{ previewCert.student_name }}</p>
                        <p class="text-xs text-muted-foreground">has successfully completed</p>
                        <p class="text-base font-bold text-foreground">{{ previewCert.course }}</p>
                        <p class="text-sm text-muted-foreground">{{ previewCert.batch }}</p>
                        <div class="border-t border-b border-border/50 py-3 grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="text-muted-foreground">Final Grade</p>
                                <p :class="['font-bold text-lg', gradeColor(previewCert.final_grade)]">{{
                                    previewCert.final_grade }}%</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Batch Rank</p>
                                <p class="font-bold text-lg text-foreground">#{{ previewCert.batch_rank }}/{{
                                    previewCert.total_students }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Issue Date</p>
                                <p class="font-bold text-sm text-foreground">{{ fmtDate(previewCert.issue_date) }}</p>
                            </div>
                        </div>
                        <p class="text-[10px] font-sans text-muted-foreground font-sans">ID: {{
                            previewCert.certificate_id }}</p>
                        <a :href="previewCert.verification_url" target="_blank"
                            class="text-[10px] font-sans text-secondary hover:underline flex items-center justify-center gap-1">
                            <ExternalLink class="h-3 w-3" />Verify certificate
                        </a>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button class="flex-1 gap-2" as-child>
                            <a :href="`/dashboard/certificates/${previewCert?.id}/download`">
                                <Download class="h-4 w-4" />Download PDF
                            </a>
                        </Button>
                        <Button variant="outline" class="gap-2" @click="showPreview = false">Close</Button>
                    </div>
                </DialogContent>
            </Dialog>

        </div>
    </DashboardLayout>
</template>