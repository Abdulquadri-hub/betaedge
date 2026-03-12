<script setup>

import { ref, computed } from 'vue'
import {
  Briefcase, Search, Clock,
  CheckCircle2, Send, RefreshCw,  Users,
  Building2,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Badge }    from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { jobListings, applyToJob } = useInstructorDashboard()

const search      = ref('')
const filterTab   = ref('open')

const filtered = computed(() => {
  let list = filterTab.value === 'applied'
    ? jobListings.value.filter(j => j.applied)
    : jobListings.value.filter(j => j.status === 'open' && !j.applied)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(j =>
    j.course.toLowerCase().includes(q) ||
    j.school_name.toLowerCase().includes(q)
  )
  return list.sort((a, b) => new Date(b.posted_at) - new Date(a.posted_at))
})

const pendingCount = computed(() => jobListings.value.filter(j => j.status === 'open' && !j.applied).length)
const appliedCount = computed(() => jobListings.value.filter(j => j.applied).length)

const showApplyDialog = ref(false)
const applyingJob     = ref(null)
const coverMessage    = ref('')
const isApplying      = ref(false)
const applyError      = ref('')

function openApply(job) {
  applyingJob.value   = job
  coverMessage.value  = ''
  applyError.value    = ''
  showApplyDialog.value = true
}

async function submitApplication() {
  if (!coverMessage.value.trim()) { applyError.value = 'Please write a brief message.'; return }
  isApplying.value = true
  try {
    const result = await applyToJob(applyingJob.value.id, coverMessage.value)
    if (result.success) {
      toast({
        title: 'Application sent! 🎉',
        description: `${applyingJob.value.school_name} will review your application and contact you via email.`,
      })
      showApplyDialog.value = false
    }
  } finally {
    isApplying.value = false
  }
}

function timeAgo(iso) {
  const d = Math.floor((Date.now() - new Date(iso)) / 86400000)
  return d === 0 ? 'Today' : d === 1 ? 'Yesterday' : `${d} days ago`
}

const paymentLabel = {
  per_batch:   'Per Batch',
  per_student: 'Per Student',
  monthly:     'Monthly',
  custom:      'Negotiable',
}
</script>

<template>
  <InstructorLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">Job Board</h1>
      <p class="text-sm text-muted-foreground mt-1">Browse teaching opportunities posted by schools on the Teach platform.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Briefcase class="h-3.5 w-3.5" />Open Positions</p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ pendingCount }}</p>
        </CardContent>
      </Card>
      <Card class="border-primary/20 bg-primary/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Send class="h-3.5 w-3.5 text-primary" />Applied</p>
          <p class="text-2xl font-bold text-primary mt-1">{{ appliedCount }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5"><Building2 class="h-3.5 w-3.5" />Total Listings</p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ jobListings.length }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <Tabs :model-value="filterTab" @update:model-value="filterTab = $event">
        <TabsList>
          <TabsTrigger value="open">
            Available
            <Badge v-if="pendingCount" variant="default" class="ml-1.5 h-4 px-1 text-[10px]">{{ pendingCount }}</Badge>
          </TabsTrigger>
          <TabsTrigger value="applied">
            Applied
            <Badge v-if="appliedCount" variant="secondary" class="ml-1.5 h-4 px-1 text-[10px]">{{ appliedCount }}</Badge>
          </TabsTrigger>
        </TabsList>
      </Tabs>
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search by course or school..." class="pl-9" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!filtered.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <Briefcase class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">
        {{ filterTab === 'applied' ? 'No applications yet' : 'No open positions match your search' }}
      </p>
      <p class="text-xs text-muted-foreground mt-1">
        {{ filterTab === 'applied' ? 'Browse available positions and apply below.' : 'Check back later for new opportunities.' }}
      </p>
    </div>

    <!-- Job cards -->
    <div v-else class="space-y-4">
      <Card
        v-for="job in filtered" :key="job.id"
        class="group hover:border-primary/20 hover:shadow-sm transition-all"
      >
        <CardContent class="p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <!-- School + course -->
              <div class="flex items-start gap-3 mb-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-secondary/10 text-secondary shrink-0">
                  <Building2 class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-sm font-bold text-foreground">{{ job.school_name }}</p>
                  <p class="text-xs text-muted-foreground">{{ job.location }}</p>
                </div>
              </div>

              <!-- Course + details -->
              <div class="ml-13 pl-0 space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-semibold text-foreground">{{ job.course }}</p>
                  <Badge v-if="job.applied" variant="outline" class="text-xs gap-1 text-primary border-primary/30 bg-primary/5">
                    <CheckCircle2 class="h-3 w-3" />Applied
                  </Badge>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                  <span class="flex items-center gap-1"><Clock class="h-3 w-3" />{{ job.schedule }}</span>
                  <span class="flex items-center gap-1">{{ paymentLabel[job.payment_model] }}: ₦{{ job.payment_amount.toLocaleString('en-NG') }}</span>
                  <span class="flex items-center gap-1"><Users class="h-3 w-3" />Up to {{ job.students_per_batch }} students/batch</span>
                </div>

                <p class="text-xs text-muted-foreground leading-relaxed line-clamp-3">
                  {{ job.requirements }}
                </p>

                <p class="text-[10px] text-muted-foreground">Posted {{ timeAgo(job.posted_at) }}</p>
              </div>
            </div>

            <div class="shrink-0">
              <Button
                v-if="!job.applied"
                class="gap-2"
                @click="openApply(job)"
              >
                <Send class="h-4 w-4" />Apply
              </Button>
              <div v-else class="flex items-center gap-1.5 text-xs text-primary font-medium">
                <CheckCircle2 class="h-4 w-4" />Applied
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Apply dialog -->
    <Dialog :open="showApplyDialog" @update:open="showApplyDialog = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Send class="h-5 w-5 text-primary" />Apply to {{ applyingJob?.school_name }}
          </DialogTitle>
          <DialogDescription class="text-xs">
            Applying for: <strong>{{ applyingJob?.course }}</strong> · {{ applyingJob?.schedule }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">
          <!-- Position summary -->
          <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-1.5 text-xs">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Schedule</span>
              <span class="font-medium text-foreground">{{ applyingJob?.schedule }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Payment</span>
              <span class="font-medium text-foreground">{{ paymentLabel[applyingJob?.payment_model] }}: ₦{{ (applyingJob?.payment_amount ?? 0).toLocaleString('en-NG') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Students / batch</span>
              <span class="font-medium text-foreground">Up to {{ applyingJob?.students_per_batch }}</span>
            </div>
          </div>

          <!-- Cover message -->
          <div class="space-y-1.5">
            <Label class="font-semibold">
              Cover Message <span class="text-destructive">*</span>
            </Label>
            <Textarea
              v-model="coverMessage"
              placeholder="Introduce yourself and explain why you're a great fit for this position. Mention your relevant experience and teaching style..."
              :rows="6"
              :class="applyError && 'border-destructive'"
              @input="applyError = ''"
            />
            <p v-if="applyError" class="text-xs text-destructive">{{ applyError }}</p>
            <p class="text-xs text-muted-foreground">The school owner will see this message along with your profile.</p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" :disabled="isApplying" @click="showApplyDialog = false">Cancel</Button>
          <Button :disabled="isApplying || !coverMessage.trim()" @click="submitApplication">
            <RefreshCw v-if="isApplying" class="mr-2 h-4 w-4 animate-spin" />
            <Send v-else class="mr-2 h-4 w-4" />
            {{ isApplying ? 'Sending...' : 'Submit Application' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
  </InstructorLayout>
</template>