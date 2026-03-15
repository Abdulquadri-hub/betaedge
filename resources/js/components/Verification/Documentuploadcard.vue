<script setup>

import { ref, computed } from 'vue'
import {
  Upload, FileText, CheckCircle2, X, AlertCircle,
  ShieldCheck, RefreshCw,
} from 'lucide-vue-next'
import { Badge }  from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps({
  label:           { type: String,  required: true         },
  description:     { type: String,  default: ''            },
  required:        { type: Boolean, default: false         },
  accept:          { type: String,  default: 'image/*,.pdf' },
  status:          { type: String,  default: 'idle'        },
  rejectionReason: { type: String,  default: ''            },
})

const emit = defineEmits(['change', 'remove'])

const localFile    = ref(null)
const localPreview = ref(null)
const localError   = ref(null)
const MAX_SIZE_MB  = 5

const displayFile = computed(() => localFile.value)

const statusConfig = computed(() => ({
  idle:     { badge: null,                     border: 'border-border'         },
  uploaded: { badge: null,                     border: 'border-border'         },
  approved: { badge: 'approved',               border: 'border-emerald-200'    },
  rejected: { badge: 'rejected',               border: 'border-destructive/40' },
}[props.status]))

function onFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  localError.value = null

  if (file.size > MAX_SIZE_MB * 1024 * 1024) {
    localError.value = `File too large — max ${MAX_SIZE_MB}MB`
    return
  }

  localFile.value = file
  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = e => { localPreview.value = e.target.result }
    reader.readAsDataURL(file)
  } else {
    localPreview.value = 'pdf'
  }

  emit('change', file)
}

function handleRemove() {
  localFile.value    = null
  localPreview.value = null
  localError.value   = null
  emit('remove')
}

function formatSize(bytes) {
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>

<template>
  <div
    class="rounded-xl border overflow-hidden transition-all"
    :class="statusConfig.border"
  >
    <!-- Header -->
    <div class="flex items-start justify-between gap-3 p-4 bg-muted/30">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <p class="text-sm font-semibold text-foreground">{{ label }}</p>
          <Badge v-if="required" variant="destructive" class="text-[10px] px-1.5 py-0">Required</Badge>
          <Badge v-else variant="secondary" class="text-[10px] px-1.5 py-0">Optional</Badge>

          <!-- Status badges -->
          <Badge
            v-if="status === 'approved'"
            class="text-[10px] px-1.5 py-0 gap-1 text-emerald-700 bg-emerald-100 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/30"
            variant="outline"
          >
            <ShieldCheck class="h-2.5 w-2.5" />Approved
          </Badge>
          <Badge
            v-else-if="status === 'rejected'"
            class="text-[10px] px-1.5 py-0 gap-1"
            variant="destructive"
          >
            <AlertCircle class="h-2.5 w-2.5" />Rejected
          </Badge>
        </div>
        <p v-if="description" class="text-xs text-muted-foreground mt-0.5">{{ description }}</p>
      </div>
      <CheckCircle2
        v-if="displayFile || status === 'approved'"
        class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5"
      />
    </div>

    <!-- Body -->
    <div class="p-4">

      <!-- Rejection reason -->
      <div
        v-if="status === 'rejected' && rejectionReason"
        class="mb-3 flex items-start gap-2 rounded-lg bg-destructive/10 border border-destructive/20 p-3 text-xs text-destructive"
      >
        <AlertCircle class="h-3.5 w-3.5 shrink-0 mt-0.5" />
        <div>
          <p class="font-semibold">Reason for rejection:</p>
          <p class="mt-0.5">{{ rejectionReason }}</p>
        </div>
      </div>

      <!-- File uploaded state -->
      <div
        v-if="displayFile"
        class="flex items-center gap-3 rounded-lg border bg-emerald-50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800 p-3"
      >
        <img
          v-if="localPreview && localPreview !== 'pdf'"
          :src="localPreview"
          class="h-12 w-12 rounded-lg object-cover shrink-0 border border-border"
          alt="Preview"
        />
        <div
          v-else
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/30"
        >
          <FileText class="h-6 w-6 text-red-500" />
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-foreground truncate">{{ localFile.name }}</p>
          <p class="text-xs text-muted-foreground">{{ formatSize(localFile.size) }}</p>
        </div>

        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8 text-muted-foreground hover:text-destructive shrink-0"
          @click="handleRemove"
        >
          <X class="h-4 w-4" />
        </Button>
      </div>

      <!-- Approved with no local file (already approved from server) -->
      <div
        v-else-if="status === 'approved'"
        class="flex items-center gap-3 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800 p-3"
      >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
          <ShieldCheck class="h-5 w-5 text-emerald-600" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Document approved</p>
          <p class="text-xs text-muted-foreground">Verified by Teach team</p>
        </div>
      </div>

      <!-- Upload zone (idle or rejected — allow re-upload) -->
      <label
        v-else
        :for="`doc-upload-${label.replace(/\s+/g,'-').toLowerCase()}`"
        class="cursor-pointer block"
      >
        <div
          class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 text-center transition-colors hover:bg-primary/5"
          :class="status === 'rejected' ? 'border-destructive/40 hover:border-destructive/60' : 'border-border hover:border-primary/40'"
        >
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
            <component :is="status === 'rejected' ? RefreshCw : Upload" class="h-5 w-5 text-muted-foreground" />
          </div>
          <div>
            <p class="text-sm font-medium text-foreground">
              {{ status === 'rejected' ? 'Upload a new document' : 'Click to upload' }}
            </p>
            <p class="text-xs text-muted-foreground">JPG, PNG or PDF · max 5MB</p>
          </div>
        </div>
        <input
          :id="`doc-upload-${label.replace(/\s+/g,'-').toLowerCase()}`"
          type="file"
          :accept="accept"
          class="sr-only"
          @change="onFileChange"
        />
      </label>

      <!-- Validation error -->
      <p v-if="localError" class="mt-2 flex items-center gap-1.5 text-xs text-destructive">
        <AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ localError }}
      </p>
    </div>
  </div>
</template>