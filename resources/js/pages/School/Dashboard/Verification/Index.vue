<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CheckCircle2, Clock, AlertCircle, ArrowLeft } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  verificationStatus: Object,
  user: Object,
  isRequired: Boolean,
})

const form = useForm({
  id_type: 'nin',
  id_number: '',
  first_name: '',
  last_name: '',
})

const isSubmitting = computed(() => form.processing)

const showForm = computed(() => {
  return !verificationStatus || verificationStatus.status !== 'verified'
})

const statusBgColor = computed(() => {
  if (verificationStatus?.status === 'verified') return 'bg-emerald-100 dark:bg-emerald-950'
  if (verificationStatus?.status === 'pending') return 'bg-amber-100 dark:bg-amber-950'
  if (verificationStatus?.status === 'rejected') return 'bg-red-100 dark:bg-red-950'
  return 'bg-gray-100 dark:bg-gray-950'
})

const statusTextColor = computed(() => {
  if (verificationStatus?.status === 'verified') return 'text-emerald-600'
  if (verificationStatus?.status === 'pending') return 'text-amber-600'
  if (verificationStatus?.status === 'rejected') return 'text-red-600'
  return 'text-gray-600'
})

const statusIcon = computed(() => {
  if (verificationStatus?.status === 'verified') return CheckCircle2
  if (verificationStatus?.status === 'pending') return Clock
  if (verificationStatus?.status === 'rejected') return AlertCircle
  return AlertCircle
})

async function handleSubmit() {
  await form.post(route('verification.submit'), {
    onSuccess: () => {
      toast.success('Verification submitted successfully')
      form.reset()
    },
  })
}
</script>

<template>
  <DashboardLayout>
    <div class="p-6 max-w-2xl mx-auto space-y-6">

      <div class="flex items-center gap-3">
        <Link :href="route('profile.show')">
          <Button variant="ghost" size="sm" class="gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Profile
          </Button>
        </Link>
      </div>

      <div>
        <h1 class="text-2xl font-bold text-foreground">ID Verification</h1>
        <p class="text-sm text-muted-foreground mt-1">
          School owners and instructors must be verified to process payments
        </p>
      </div>

      <Card v-if="verificationStatus">
        <CardContent class="pt-6">
          <div :class="['flex items-center gap-4 p-4 rounded-lg', statusBgColor]">
            <component :is="statusIcon" :class="['h-8 w-8 shrink-0', statusTextColor]" />
            <div>
              <p :class="['font-bold text-lg capitalize', statusTextColor]">
                {{ verificationStatus.status }}
              </p>
              <p v-if="verificationStatus.status === 'pending'" class="text-sm opacity-90 mt-1">
                Your verification is under review. We'll notify you within 24 hours.
              </p>
              <p v-else-if="verificationStatus.status === 'rejected'" class="text-sm opacity-90 mt-1">
                {{ verificationStatus.rejection_reason || 'Please try again with correct information.' }}
              </p>
              <p v-else-if="verificationStatus.status === 'verified'" class="text-sm opacity-90 mt-1">
                You are verified and can process payments
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="showForm">
        <CardHeader>
          <CardTitle>Submit for Verification</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-4">

            <div class="space-y-2">
              <Label for="id_type">ID Type</Label>
              <Select v-model="form.id_type">
                <SelectTrigger id="id_type">
                  <SelectValue placeholder="Select ID type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="nin">National ID (NIN)</SelectItem>
                  <SelectItem value="license">Driver's License</SelectItem>
                  <SelectItem value="passport">International Passport</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.id_type" class="text-xs text-red-500">{{ form.errors.id_type }}</p>
            </div>

            <div class="space-y-2">
              <Label for="id_number">ID Number</Label>
              <Input
                id="id_number"
                v-model="form.id_number"
                type="text"
                placeholder="e.g., 12345678901"
                :disabled="isSubmitting"
              />
              <p v-if="form.errors.id_number" class="text-xs text-red-500">{{ form.errors.id_number }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="first_name">First Name</Label>
                <Input
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  placeholder="First name"
                  :disabled="isSubmitting"
                />
                <p v-if="form.errors.first_name" class="text-xs text-red-500">{{ form.errors.first_name }}</p>
              </div>

              <div class="space-y-2">
                <Label for="last_name">Last Name</Label>
                <Input
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  placeholder="Last name"
                  :disabled="isSubmitting"
                />
                <p v-if="form.errors.last_name" class="text-xs text-red-500">{{ form.errors.last_name }}</p>
              </div>
            </div>

            <Button type="submit" :disabled="isSubmitting" class="w-full">
              {{ isSubmitting ? 'Submitting...' : 'Submit for Verification' }}
            </Button>

          </form>
        </CardContent>
      </Card>

    </div>
  </DashboardLayout>
</template>



// ── Props ─────────────────────────────────────────────────────────────────────
// TODO: replace mock with defineProps({ userType, userName, verification, documents })

const userType = ref('school')   // 'instructor' | 'school'
const userName = ref('Adebayo Johnson')

const verification = ref({
  status:       'unverified',   // change to 'pending' | 'verified' | 'rejected' to preview states
  submitted_at: null,
  reviewed_at:  null,
  feedback:     null,
})

const documents = ref([
  {
    key:              'government_id',
    label:            'Government-Issued ID',
    description:      'NIN slip, National Passport, or Driver\'s Licence',
    required:         true,
    status:           'idle',
    rejection_reason: null,
  },
  {
    key:              'qualification',
    label:            'Highest Qualification',
    description:      'WAEC, B.Sc, M.Sc, PhD or equivalent certificate',
    required:         true,
    status:           'idle',
    rejection_reason: null,
  },
  {
    key:              'experience_letter',
    label:            'Experience Letter',
    description:      'Reference from a school, employer, or professional body',
    required:         false,
    status:           'idle',
    rejection_reason: null,
  },
])

// School-specific document (only shown when userType === 'school')
const schoolDocuments = ref([
  {
    key:              'business_registration',
    label:            'Business Registration',
    description:      'CAC certificate or business name registration document',
    required:         true,
    status:           'idle',
    rejection_reason: null,
  },
  {
    key:              'owner_id',
    label:            'Owner\'s ID',
    description:      'Government-issued ID of the school owner',
    required:         true,
    status:           'idle',
    rejection_reason: null,
  },
  {
    key:              'school_logo',
    label:            'School Logo',
    description:      'Clear logo image (PNG or JPG, min 200×200px)',
    required:         false,
    status:           'idle',
    rejection_reason: null,
  },
])

// ── Active documents (switches based on userType) ─────────────────────────────
const activeDocuments = computed(() =>
  userType.value === 'school' ? schoolDocuments.value : documents.value
)

// ── Local file tracking ───────────────────────────────────────────────────────
const uploadedFiles = ref({})   // key → File

function onFileChange(docKey, file) {
  uploadedFiles.value = { ...uploadedFiles.value, [docKey]: file }
}

function onFileRemove(docKey) {
  const updated = { ...uploadedFiles.value }
  delete updated[docKey]
  uploadedFiles.value = updated
}

// ── Validation ────────────────────────────────────────────────────────────────
const requiredDocKeys = computed(() =>
  activeDocuments.value.filter(d => d.required).map(d => d.key)
)

const allRequiredUploaded = computed(() => {
  // Required docs are satisfied if already approved OR a new file was uploaded
  return requiredDocKeys.value.every(key => {
    const doc = activeDocuments.value.find(d => d.key === key)
    return doc?.status === 'approved' || uploadedFiles.value[key]
  })
})

const hasAnyNewFile = computed(() =>
  Object.keys(uploadedFiles.value).length > 0
)

const canSubmit = computed(() =>
  allRequiredUploaded.value &&
  hasAnyNewFile.value &&
  ['unverified', 'rejected'].includes(verification.value.status)
)

// ── Submit ────────────────────────────────────────────────────────────────────
const isSubmitting = ref(false)

async function handleSubmit() {
  if (!canSubmit.value) return
  isSubmitting.value = true

  try {
    await new Promise(r => setTimeout(r, 900))
    /**
     * TODO (Laravel 12):
     * const formData = new FormData()
     * Object.entries(uploadedFiles.value).forEach(([key, file]) => {
     *   formData.append(key, file)
     * })
     * router.post(
     *   route(userType.value === 'school'
     *     ? 'dashboard.verification.submit'
     *     : 'instructor.verification.submit'
     *   ),
     *   formData,
     *   {
     *     preserveScroll: true,
     *     onSuccess: () => {
     *       verification.value.status = 'pending'
     *       verification.value.submitted_at = new Date().toISOString()
     *       uploadedFiles.value = {}
     *     },
     *     onError: (errors) => { toast({ title: 'Upload failed', variant: 'destructive' }) },
     *   }
     * )
     */
    verification.value.status       = 'pending'
    verification.value.submitted_at = new Date().toISOString()
    uploadedFiles.value              = {}
    toast.success('Document submitted',{ title: 'Documents submitted', description: 'We\'ll review them within 1–2 business days.' })
  } finally {
    isSubmitting.value = false
  }
}

const isSchool = computed(() => userType.value === 'school')
</script>

<template>
  <DashboardLayout>
  <div class="p-6 max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight flex items-center gap-2">
          <Shield class="h-6 w-6 text-primary" />
          Verification
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          {{ isSchool
            ? 'Verify your school to appear in the marketplace and attract students.'
            : 'Get verified to appear in the tutor marketplace and apply to schools.'
          }}
        </p>
      </div>
      <VerificationBadge :status="verification.status" size="md" />
    </div>

    <!-- Overall status card -->
    <Verificationstatus
      :status="verification.status"
      :user-type="userType"
      :submitted-at="verification.submitted_at"
      :reviewed-at="verification.reviewed_at"
      :documents="activeDocuments.map(d => ({ label: d.label, status: d.status }))"
    />

    <!-- Admin feedback on rejection -->
    <div
      v-if="verification.status === 'rejected' && verification.feedback"
      class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4"
    >
      <AlertCircle class="h-5 w-5 text-destructive shrink-0 mt-0.5" />
      <div>
        <p class="text-sm font-semibold text-destructive">Reviewer feedback</p>
        <p class="text-sm text-muted-foreground mt-0.5">{{ verification.feedback }}</p>
      </div>
    </div>

    <!-- Document upload section -->
    <div
      v-if="['unverified', 'rejected'].includes(verification.status)"
      class="space-y-4"
    >
      <div class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-foreground">
          {{ verification.status === 'rejected' ? 'Re-upload Documents' : 'Upload Documents' }}
        </h2>
        <Badge variant="secondary" class="text-xs">
          {{ requiredDocKeys.length }} required
        </Badge>
      </div>

      <div class="space-y-3">
        <DocumentUploadCard
          v-for="doc in activeDocuments"
          :key="doc.key"
          :label="doc.label"
          :description="doc.description"
          :required="doc.required"
          :status="doc.status"
          :rejection-reason="doc.rejection_reason"
          @change="file => onFileChange(doc.key, file)"
          @remove="onFileRemove(doc.key)"
        />
      </div>

      <!-- Submit button -->
      <div class="flex items-center justify-between pt-2 flex-wrap gap-3">
        <p class="text-xs text-muted-foreground max-w-xs">
          All documents are reviewed manually by the Teach team. Approval typically takes 1–2 business days.
        </p>
        <Button
          :disabled="!canSubmit || isSubmitting"
          class="gap-2 min-w-40"
          @click="handleSubmit"
        >
          <RefreshCw v-if="isSubmitting" class="h-4 w-4 animate-spin" />
          <Send v-else class="h-4 w-4" />
          {{ isSubmitting ? 'Submitting...' : 'Submit for Review' }}
        </Button>
      </div>
    </div>

    <!-- Read-only document list (pending / verified states) -->
    <div v-else class="space-y-3">
      <h2 class="text-base font-semibold text-foreground">Your Documents</h2>
      <DocumentUploadCard
        v-for="doc in activeDocuments"
        :key="doc.key"
        :label="doc.label"
        :description="doc.description"
        :required="doc.required"
        :status="doc.status"
        :rejection-reason="doc.rejection_reason"
        @change="file => onFileChange(doc.key, file)"
        @remove="onFileRemove(doc.key)"
      />
    </div>

    <!-- Help section -->
    <div class="rounded-xl border border-border bg-muted/30 p-4 space-y-2">
      <p class="text-sm font-semibold text-foreground flex items-center gap-2">
        <HelpCircle class="h-4 w-4 text-muted-foreground" />Frequently Asked Questions
      </p>
      <div class="space-y-2 text-xs text-muted-foreground">
        <div v-for="faq in [
          {
            q: 'How long does verification take?',
            a: 'Typically 1–2 business days. You\'ll receive an email notification when done.',
          },
          {
            q: 'Why was my document rejected?',
            a: 'Common reasons: blurry image, expired document, wrong document type, or name mismatch. Check the reviewer\'s feedback above.',
          },
          {
            q: 'Can I use the platform before being verified?',
            a: isSchool
              ? 'Yes. You can set up your school, create courses, and enroll students. Your school won\'t appear in the marketplace until verified.'
              : 'Yes. You can access your dashboard, but won\'t appear in the tutor marketplace or apply for jobs until verified.',
          },
        ]" :key="faq.q" class="space-y-0.5">
          <p class="font-semibold text-foreground">{{ faq.q }}</p>
          <p>{{ faq.a }}</p>
        </div>
      </div>
      <a
        href="mailto:support@teach.com"
        class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline mt-1"
      >
        <ExternalLink class="h-3 w-3" />Contact support
      </a>
    </div>

  </div>
  </DashboardLayout>
</template>