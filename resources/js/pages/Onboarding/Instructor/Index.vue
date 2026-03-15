<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { GraduationCap, User, BookOpen, FileText, CheckCircle2 } from 'lucide-vue-next'
import { Input }  from '@/components/ui/input'
import { Label }  from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import Onboardingstepindicator from './Steps/Onboardingstepindicator.vue'
import Steppersonalinfo from './Steps/Steppersonalinfo.vue'
import Stepsubjectsavailability from './Steps/Stepsubjectsavailability.vue'
import Stepdocumentupload from './Steps/Stepdocumentupload.vue'
import Stepcomplete from './Steps/Stepcomplete .vue'

// ── Step config ───────────────────────────────────────────────────────────────
const STEPS = [
  { label: 'Your Info',    icon: User        },
  { label: 'Subjects',     icon: BookOpen    },
  { label: 'Documents',    icon: FileText    },
  { label: 'Done',         icon: CheckCircle2 },
]

const currentStep = ref(1)

// ── Collected data across steps ───────────────────────────────────────────────
const formData = ref({
  // Step 0 — credentials (collected before steps)
  email:    '',
  password: '',
  // Step 1
  personal:      null,
  // Step 2
  subjects:      null,
  // Step 3
  documents:     null,
})

// ── Credential form (pre-step) ────────────────────────────────────────────────
const credentialsDone  = ref(false)
const credErrors       = ref({})
const isCreatingAccount = ref(false)

function validateCredentials() {
  credErrors.value = {}
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!formData.value.email.trim() || !emailRe.test(formData.value.email)) {
    credErrors.value.email = 'Enter a valid email address'
  }
  if (!formData.value.password || formData.value.password.length < 8) {
    credErrors.value.password = 'Password must be at least 8 characters'
  }
  return Object.keys(credErrors.value).length === 0
}

async function handleCredentials() {
  if (!validateCredentials()) return
  isCreatingAccount.value = true
  await new Promise(r => setTimeout(r, 700))
  // TODO (Laravel 12):
  // router.post(route('onboarding.instructor.start'), {
  //   email:    formData.value.email,
  //   password: formData.value.password,
  // }, {
  //   onSuccess: () => { credentialsDone.value = true },
  //   onError:   (e) => { credErrors.value = e },
  // })
  isCreatingAccount.value = false
  credentialsDone.value   = true
}

// ── Step handlers ─────────────────────────────────────────────────────────────
function handlePersonalNext(data) {
  formData.value.personal = data
  currentStep.value = 2
}

function handleSubjectsNext(data) {
  formData.value.subjects = data
  currentStep.value = 3
}

async function handleDocumentsNext(data) {
  formData.value.documents = data
  // TODO (Laravel 12): final submit
  // router.post(route('onboarding.instructor.submit'), buildFormData(), {
  //   onSuccess: () => { currentStep.value = 4 },
  // })
  currentStep.value = 4
}

const instructorFirstName = computed(() =>
  formData.value.personal?.first_name ?? 'Instructor'
)
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col">

    <!-- Top nav -->
    <header class="sticky top-0 z-10 border-b border-border bg-background/95 backdrop-blur">
      <div class="container mx-auto px-4 h-16 flex items-center justify-between">
        <Link href="/" class="flex items-center gap-2.5">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary shadow-sm">
            <GraduationCap class="h-5 w-5 text-primary-foreground" />
          </div>
          <span class="text-lg font-bold text-foreground">BetaEdge</span>
        </Link>
        <p class="text-sm text-muted-foreground">
          Already have an account?
          <Link href="/auth/login" class="text-primary font-medium hover:underline ml-1">Sign in</Link>
        </p>
      </div>
    </header>

    <main class="flex-1 flex items-start justify-center py-10 px-4">
      <div class="w-full max-w-xl space-y-8">

        <!-- Page title -->
        <div class="text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-secondary/30 bg-secondary/10 px-4 py-1.5 text-xs font-semibold text-secondary mb-4">
            <GraduationCap class="h-3.5 w-3.5" />Join as a Tutor
          </div>
          <h1 class="text-2xl font-black text-foreground">Create your tutor profile</h1>
          <p class="text-sm text-muted-foreground mt-1.5">
            Get verified, connect with schools, and grow your teaching career.
          </p>
        </div>

        <!-- Credential step (before numbered steps) -->
        <div v-if="!credentialsDone" class="rounded-2xl border border-border bg-card p-6 sm:p-8 space-y-6 shadow-sm">
          <div>
            <h2 class="text-lg font-bold text-foreground">Create your account</h2>
            <p class="text-sm text-muted-foreground mt-1">You'll use these to log into your tutor dashboard.</p>
          </div>

          <div class="space-y-4">
            <div class="space-y-1.5">
              <Label>Email Address <span class="text-destructive">*</span></Label>
              <Input
                v-model="formData.email"
                type="email"
                placeholder="you@example.com"
                :class="credErrors.email ? 'border-destructive' : ''"
                @keydown.enter="handleCredentials"
              />
              <p v-if="credErrors.email" class="text-xs text-destructive">{{ credErrors.email }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>Password <span class="text-destructive">*</span></Label>
              <Input
                v-model="formData.password"
                type="password"
                placeholder="Minimum 8 characters"
                :class="credErrors.password ? 'border-destructive' : ''"
                @keydown.enter="handleCredentials"
              />
              <p v-if="credErrors.password" class="text-xs text-destructive">{{ credErrors.password }}</p>
            </div>
          </div>

          <Button
            class="w-full gap-2"
            :disabled="isCreatingAccount"
            @click="handleCredentials"
          >
            <span v-if="isCreatingAccount" class="h-4 w-4 rounded-full border-2 border-primary-foreground border-t-transparent animate-spin" />
            {{ isCreatingAccount ? 'Creating account...' : 'Continue' }}
          </Button>

          <p class="text-center text-xs text-muted-foreground">
            By continuing, you agree to our
            <a href="/terms" class="text-primary underline">Terms of Service</a>
            and
            <a href="/privacy" class="text-primary underline">Privacy Policy</a>.
          </p>
        </div>

        <!-- Multi-step form -->
        <template v-else>

          <!-- Step indicator -->
          <Onboardingstepindicator :steps="STEPS" :current="currentStep" />

          <!-- Step card -->
          <div class="rounded-2xl border border-border bg-card p-6 sm:p-8 shadow-sm">
            <Steppersonalinfo
              v-if="currentStep === 1"
              @next="handlePersonalNext"
            />
            <Stepsubjectsavailability
              v-else-if="currentStep === 2"
              @next="handleSubjectsNext"
              @back="currentStep = 1"
            />
            <Stepdocumentupload
              v-else-if="currentStep === 3"
              @next="handleDocumentsNext"
              @back="currentStep = 2"
            />
            <Stepcomplete
              v-else-if="currentStep === 4"
              :name="instructorFirstName"
            />
          </div>

        </template>

        <!-- Bottom note -->
        <p class="text-center text-xs text-muted-foreground pb-6">
          Are you a school owner?
          <Link href="/onboarding" class="text-primary font-medium hover:underline ml-1">
            Create a school instead
          </Link>
        </p>

      </div>
    </main>
  </div>
</template>