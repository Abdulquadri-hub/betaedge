<script setup>

import { ref, computed } from 'vue'
import {
  Upload, FileText, CheckCircle2, X, AlertCircle,
  ArrowRight, ArrowLeft, Shield,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Label }  from '@/components/ui/label'
import { Badge }  from '@/components/ui/badge'

const emit = defineEmits(['next', 'back'])

// ── Document slot definition ──────────────────────────────────────────────────
const documentSlots = ref([
  {
    key:         'government_id',
    label:       'Government-Issued ID',
    description: 'NIN slip, National Passport, or Driver\'s Licence',
    required:    true,
    accept:      'image/*,.pdf',
    file:        null,
    preview:     null,
    error:       null,
  },
  {
    key:         'qualification',
    label:       'Highest Qualification Certificate',
    description: 'WAEC, B.Sc, M.Sc, PhD, or equivalent',
    required:    true,
    accept:      'image/*,.pdf',
    file:        null,
    preview:     null,
    error:       null,
  },
  {
    key:         'experience_letter',
    label:       'Experience Letter / Reference',
    description: 'Letter from a school, employer, or professional reference',
    required:    false,
    accept:      'image/*,.pdf',
    file:        null,
    preview:     null,
    error:       null,
  },
])

const isSubmitting = ref(false)

// ── File handling ─────────────────────────────────────────────────────────────
const MAX_SIZE_MB = 5

function onFileChange(slot, event) {
  const file = event.target.files?.[0]
  if (!file) return

  slot.error = null

  if (file.size > MAX_SIZE_MB * 1024 * 1024) {
    slot.error = `File too large. Maximum size is ${MAX_SIZE_MB}MB.`
    return
  }

  slot.file = file

  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = e => { slot.preview = e.target.result }
    reader.readAsDataURL(file)
  } else {
    slot.preview = 'pdf'
  }
}

function removeFile(slot) {
  slot.file    = null
  slot.preview = null
  slot.error   = null
}

function formatSize(bytes) {
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// ── Validation ────────────────────────────────────────────────────────────────
const requiredUploaded = computed(() =>
  documentSlots.value
    .filter(s => s.required)
    .every(s => s.file !== null)
)

async function handleNext() {
  isSubmitting.value = true
  await new Promise(r => setTimeout(r, 600))
  // TODO (Laravel 12):
  // const formData = new FormData()
  // documentSlots.value.forEach(slot => {
  //   if (slot.file) formData.append(slot.key, slot.file)
  // })
  // router.post(route('instructor.onboarding.documents'), formData, {
  //   onSuccess: () => emit('next', { documents_uploaded: true }),
  // })
  isSubmitting.value = false
  emit('next', {
    documents: documentSlots.value
      .filter(s => s.file)
      .map(s => ({ key: s.key, name: s.file.name })),
  })
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-bold text-foreground">Upload Your Documents</h2>
      <p class="text-sm text-muted-foreground mt-1">
        Required for verification. Your profile will be reviewed by the Teach team before appearing publicly.
      </p>
    </div>

    <!-- Security notice -->
    <div class="flex items-start gap-3 rounded-xl border border-secondary/20 bg-secondary/5 p-4">
      <Shield class="h-5 w-5 text-secondary shrink-0 mt-0.5" />
      <div class="text-xs">
        <p class="font-semibold text-foreground">Your documents are secure</p>
        <p class="text-muted-foreground mt-0.5">
          Documents are encrypted and only reviewed by the Teach verification team.
          They are never shared with schools or third parties.
        </p>
      </div>
    </div>

    <!-- Document upload slots -->
    <div class="space-y-4">
      <div
        v-for="slot in documentSlots"
        :key="slot.key"
        class="rounded-xl border border-border overflow-hidden"
      >
        <!-- Slot header -->
        <div class="flex items-start justify-between gap-3 p-4 bg-muted/30">
          <div>
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-foreground">{{ slot.label }}</p>
              <Badge v-if="slot.required" variant="destructive" class="text-[10px] px-1.5 py-0">Required</Badge>
              <Badge v-else variant="secondary" class="text-[10px] px-1.5 py-0">Optional</Badge>
            </div>
            <p class="text-xs text-muted-foreground mt-0.5">{{ slot.description }}</p>
          </div>
          <CheckCircle2 v-if="slot.file" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
        </div>

        <!-- Upload area / preview -->
        <div class="p-4">

          <!-- File already uploaded -->
          <div v-if="slot.file" class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 p-3">
            <!-- Image preview -->
            <img
              v-if="slot.preview && slot.preview !== 'pdf'"
              :src="slot.preview"
              class="h-12 w-12 rounded-lg object-cover shrink-0 border border-border"
              alt="Preview"
            />
            <!-- PDF icon -->
            <div
              v-else
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/30"
            >
              <FileText class="h-6 w-6 text-red-500" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-foreground truncate">{{ slot.file.name }}</p>
              <p class="text-xs text-muted-foreground">{{ formatSize(slot.file.size) }}</p>
            </div>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-destructive shrink-0"
              @click="removeFile(slot)"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>

          <!-- Upload dropzone -->
          <Label v-else :for="`upload-${slot.key}`" class="cursor-pointer block">
            <div
              class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border p-6 text-center transition-colors hover:border-primary/40 hover:bg-primary/5"
            >
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                <Upload class="h-5 w-5 text-muted-foreground" />
              </div>
              <div>
                <p class="text-sm font-medium text-foreground">Click to upload</p>
                <p class="text-xs text-muted-foreground">JPG, PNG, or PDF · max {{ 5 }}MB</p>
              </div>
            </div>
            <input
              :id="`upload-${slot.key}`"
              type="file"
              :accept="slot.accept"
              class="sr-only"
              @change="onFileChange(slot, $event)"
            />
          </Label>

          <!-- Error -->
          <p v-if="slot.error" class="mt-2 flex items-center gap-1.5 text-xs text-destructive">
            <AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ slot.error }}
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-2">
      <Button variant="outline" class="gap-2" @click="$emit('back')">
        <ArrowLeft class="h-4 w-4" />Back
      </Button>
      <Button
        :disabled="!requiredUploaded || isSubmitting"
        class="gap-2 min-w-36"
        @click="handleNext"
      >
        <span v-if="isSubmitting" class="h-4 w-4 rounded-full border-2 border-primary-foreground border-t-transparent animate-spin" />
        <ArrowRight v-else class="h-4 w-4" />
        {{ isSubmitting ? 'Uploading...' : 'Submit Documents' }}
      </Button>
    </div>
  </div>
</template>