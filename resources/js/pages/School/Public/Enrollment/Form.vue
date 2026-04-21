<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle, GraduationCap, Users, RefreshCw } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { toast } from 'vue-sonner'

// ─── Real Inertia Props from EnrollmentController@showForm ───────────────────
const props = defineProps({
    tenant:              { type: Object,  required: true },
    batch:               { type: Object,  required: true },
    paystack_public_key: { type: String,  default: null  },
    meta:                { type: Object,  default: () => ({}) },
})

const page = usePage()
watch(() => page.props.errors, (errs) => {
    if (errs?.enrollment) toast.error(errs.enrollment)
}, { deep: true })

// ─── Step machine ─────────────────────────────────────────────────────────────
// Step 0 → Account type (adult / parent)
// Step 1 → Registration info
// Step 2 → Review & pay

const STEPS = [
    { id: 'type',     label: 'Who is enrolling' },
    { id: 'details',  label: 'Your information' },
    { id: 'review',   label: 'Review & pay'     },
]

const step         = ref(0)
const isSubmitting = ref(false)

const progressPct = computed(() => (step.value / (STEPS.length - 1)) * 100)

// ─── Form state ───────────────────────────────────────────────────────────────
const accountType = ref(null) // 'adult' | 'parent'

const studentForm = reactive({
    name:          '',
    email:         '',
    phone:         '',
    date_of_birth: '',
    password:      '',
    password_confirmation: '',
})

const parentForm = reactive({
    name:         '',
    email:        '',
    phone:        '',
    relationship: 'mother',
})

// ─── Inline field errors ──────────────────────────────────────────────────────
const errors = reactive({})

function clearErr(field) { delete errors[field] }

function validateStudent() {
    const e = {}
    if (!studentForm.name.trim())          e['student.name']          = 'Name is required'
    if (!studentForm.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/))
                                           e['student.email']         = 'Valid email required'
    if (!studentForm.phone)                e['student.phone']         = 'Phone is required'
    if (!studentForm.date_of_birth)        e['student.date_of_birth'] = 'Date of birth required'
    if (studentForm.password.length < 8)   e['student.password']      = 'Min 8 characters'
    if (studentForm.password !== studentForm.password_confirmation)
                                           e['student.password']      = 'Passwords do not match'
    if (accountType.value === 'parent') {
        if (!parentForm.name.trim())       e['parent.name']           = 'Parent name required'
        if (!parentForm.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/))
                                           e['parent.email']          = 'Valid parent email required'
        if (parentForm.email === studentForm.email)
                                           e['parent.email']          = 'Parent email must differ from student email'
        if (!parentForm.phone)             e['parent.phone']          = 'Parent phone required'
    }
    Object.assign(errors, e)
    return Object.keys(e).length === 0
}

// ─── Navigation ───────────────────────────────────────────────────────────────
function selectType(type) {
    accountType.value = type
    step.value = 1
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

function goBack() {
    if (step.value > 0) { step.value--; window.scrollTo({ top: 0, behavior: 'smooth' }) }
}

function goReview() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!validateStudent()) return
    step.value = 2
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ─── Submit to EnrollmentController@submit ────────────────────────────────────
// POST /batches/{batchSlug}/enroll
// Returns Inertia.location → Paystack authorization_url
function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!validateStudent()) { step.value = 1; return }

    isSubmitting.value = true

    const isMinor = isStudentMinor()
    const payload = {
        student: {
            name:          studentForm.name,
            email:         studentForm.email,
            phone:         studentForm.phone,
            date_of_birth: studentForm.date_of_birth,
            password:      studentForm.password,
            password_confirmation: studentForm.password_confirmation,
        },
    }

    if (isMinor) {
        payload.parent = {
            name:         parentForm.name,
            email:        parentForm.email,
            phone:        parentForm.phone,
            relationship: parentForm.relationship,
        }
    }

    router.post(window.location.pathname, payload, {
        onError: (errs) => {
            Object.assign(errors, errs)
            if (errs['student.email'] || errs['student.name'] || errs.enrollment) {
                step.value = 1
            }
        },
        onFinish: () => { isSubmitting.value = false },
    })
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function isStudentMinor() {
    if (!studentForm.date_of_birth) return false
    return (new Date().getFullYear() - new Date(studentForm.date_of_birth).getFullYear()) < 18
}

function dobMax() {
    return new Date().toISOString().split('T')[0]
}

function fmtNaira(n) {
    if (!n) return 'Free'
    return '₦' + Number(n).toLocaleString('en-NG')
}

function fmtTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const showPassword       = ref(false)
const showConfirmPw      = ref(false)
const showParentPassword = ref(false)

const RELATIONSHIPS = [
    { value: 'mother',      label: 'Mother'      },
    { value: 'father',      label: 'Father'      },
    { value: 'guardian',    label: 'Guardian'    },
    { value: 'grandmother', label: 'Grandmother' },
    { value: 'grandfather', label: 'Grandfather' },
    { value: 'uncle',       label: 'Uncle'       },
    { value: 'aunt',        label: 'Aunt'        },
    { value: 'other',       label: 'Other'       },
]
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Top bar -->
        <div class="border-b bg-card">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-4">
                <button type="button"
                    class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
                    @click="router.visit(`/batches/${batch.slug}`)">
                    <ArrowLeft class="h-4 w-4" />Back
                </button>
                <div class="flex items-center gap-2 ml-auto text-sm text-muted-foreground">
                    <span class="font-semibold text-foreground truncate max-w-[200px]">{{ batch.name }}</span>
                    <span>·</span>
                    <span class="font-bold text-foreground">{{ fmtNaira(batch.price) }}</span>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-6">

            <!-- Progress -->
            <div v-if="step < STEPS.length">
                <div class="flex justify-between mb-2">
                    <div v-for="(s, i) in STEPS" :key="s.id"
                        class="flex items-center gap-2 text-xs font-medium transition-colors"
                        :class="i <= step ? 'text-foreground' : 'text-muted-foreground'">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold"
                            :class="i < step ? 'bg-primary text-primary-foreground' : i === step ? 'border-2 border-primary text-primary' : 'border border-muted-foreground text-muted-foreground'">
                            <CheckCircle v-if="i < step" class="h-3.5 w-3.5" />
                            <span v-else>{{ i + 1 }}</span>
                        </span>
                        <span class="hidden sm:inline">{{ s.label }}</span>
                    </div>
                </div>
                <Progress :model-value="progressPct" class="h-1.5" />
            </div>

            <!-- Card content -->
            <Card>
                <CardContent class="p-6 sm:p-8">

                    <!-- Step 0: Account type -->
                    <div v-if="step === 0" class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-foreground">Who is enrolling?</h2>
                            <p class="text-muted-foreground mt-2 text-sm">
                                Enrolling in <strong>{{ batch.name }}</strong>
                            </p>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <button v-for="type in [
                                { value: 'adult',  icon: GraduationCap, label: 'I\'m an Adult Student', desc: '18 years or older, enrolling for myself' },
                                { value: 'parent', icon: Users,         label: 'I\'m a Parent/Guardian', desc: 'Enrolling my child (under 18)' },
                            ]" :key="type.value" type="button"
                                class="text-left rounded-xl border-2 p-5 transition-all duration-200 hover:-translate-y-0.5"
                                :class="accountType === type.value
                                    ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                    : 'border-border hover:border-primary/40 hover:shadow-md'"
                                @click="selectType(type.value)">
                                <component :is="type.icon"
                                    class="h-10 w-10 mb-3"
                                    :class="accountType === type.value ? 'text-primary' : 'text-muted-foreground'" />
                                <p class="font-semibold text-foreground mb-1">{{ type.label }}</p>
                                <p class="text-xs text-muted-foreground">{{ type.desc }}</p>
                            </button>
                        </div>
                    </div>

                    <!-- Step 1: Registration -->
                    <div v-else-if="step === 1" class="space-y-6">
                        <!-- Student section -->
                        <div>
                            <div class="text-center mb-5">
                                <h2 class="text-xl font-bold text-foreground">
                                    {{ accountType === 'parent' ? "Child's Information" : "Your Information" }}
                                </h2>
                                <p class="text-muted-foreground text-sm mt-1">Create a student account</p>
                            </div>
                            <div class="space-y-4">
                                <!-- Name -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">
                                        {{ accountType === 'parent' ? "Child's Full Name" : "Full Name" }}
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <input v-model="studentForm.name" type="text"
                                        :placeholder="accountType === 'parent' ? 'Child\'s full name' : 'Your full name'"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                        :class="errors['student.name'] && 'border-destructive'"
                                        @input="clearErr('student.name')" />
                                    <p v-if="errors['student.name']" class="text-xs text-destructive">{{ errors['student.name'] }}</p>
                                </div>

                                <!-- Email -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">
                                        {{ accountType === 'parent' ? "Child's Email" : "Email Address" }}
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <input v-model="studentForm.email" type="email"
                                        :placeholder="accountType === 'parent' ? 'child@example.com' : 'you@example.com'"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                        :class="errors['student.email'] && 'border-destructive'"
                                        @input="clearErr('student.email')" />
                                    <p v-if="errors['student.email']" class="text-xs text-destructive">{{ errors['student.email'] }}</p>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <!-- Phone -->
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium">Phone Number <span class="text-destructive">*</span></label>
                                        <input v-model="studentForm.phone" type="tel" placeholder="+234 801 234 5678"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                            :class="errors['student.phone'] && 'border-destructive'"
                                            @input="clearErr('student.phone')" />
                                        <p v-if="errors['student.phone']" class="text-xs text-destructive">{{ errors['student.phone'] }}</p>
                                    </div>
                                    <!-- DOB -->
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium">Date of Birth <span class="text-destructive">*</span></label>
                                        <input v-model="studentForm.date_of_birth" type="date" :max="dobMax()"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                            :class="errors['student.date_of_birth'] && 'border-destructive'"
                                            @input="clearErr('student.date_of_birth')" />
                                        <p v-if="errors['student.date_of_birth']" class="text-xs text-destructive">{{ errors['student.date_of_birth'] }}</p>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Password <span class="text-destructive">*</span></label>
                                    <div class="relative">
                                        <input v-model="studentForm.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Min 8 characters"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 pr-10 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                            :class="errors['student.password'] && 'border-destructive'"
                                            @input="clearErr('student.password')" />
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                                            @click="showPassword = !showPassword">
                                            <span class="text-xs">{{ showPassword ? 'Hide' : 'Show' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="errors['student.password']" class="text-xs text-destructive">{{ errors['student.password'] }}</p>
                                </div>

                                <!-- Confirm password -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Confirm Password <span class="text-destructive">*</span></label>
                                    <div class="relative">
                                        <input v-model="studentForm.password_confirmation"
                                            :type="showConfirmPw ? 'text' : 'password'"
                                            placeholder="Repeat password"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 pr-10 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                            @input="clearErr('student.password')" />
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                                            @click="showConfirmPw = !showConfirmPw">
                                            <span class="text-xs">{{ showConfirmPw ? 'Hide' : 'Show' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent section (shown when accountType === 'parent' OR student is minor) -->
                        <div v-if="accountType === 'parent'" class="border-t border-border pt-6 space-y-4">
                            <div class="text-center mb-4">
                                <h3 class="text-lg font-semibold text-foreground">Parent/Guardian Information</h3>
                                <p class="text-muted-foreground text-sm">Your account details as parent</p>
                            </div>

                            <!-- Parent Name -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Your Full Name <span class="text-destructive">*</span></label>
                                <input v-model="parentForm.name" type="text" placeholder="Parent/guardian name"
                                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                    :class="errors['parent.name'] && 'border-destructive'"
                                    @input="clearErr('parent.name')" />
                                <p v-if="errors['parent.name']" class="text-xs text-destructive">{{ errors['parent.name'] }}</p>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <!-- Parent Email -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Your Email <span class="text-destructive">*</span></label>
                                    <input v-model="parentForm.email" type="email" placeholder="parent@email.com"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                        :class="errors['parent.email'] && 'border-destructive'"
                                        @input="clearErr('parent.email')" />
                                    <p v-if="errors['parent.email']" class="text-xs text-destructive">{{ errors['parent.email'] }}</p>
                                </div>
                                <!-- Parent Phone -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Your Phone <span class="text-destructive">*</span></label>
                                    <input v-model="parentForm.phone" type="tel" placeholder="+234 xxx xxx xxxx"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none"
                                        :class="errors['parent.phone'] && 'border-destructive'"
                                        @input="clearErr('parent.phone')" />
                                    <p v-if="errors['parent.phone']" class="text-xs text-destructive">{{ errors['parent.phone'] }}</p>
                                </div>
                            </div>

                            <!-- Relationship -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Relationship to child <span class="text-destructive">*</span></label>
                                <select v-model="parentForm.relationship"
                                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 outline-none">
                                    <option v-for="r in RELATIONSHIPS" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex justify-between pt-2">
                            <Button variant="outline" class="gap-2" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />Back
                            </Button>
                            <Button class="gap-2" @click="goReview">
                                Review & Pay →
                            </Button>
                        </div>
                    </div>

                    <!-- Step 2: Review -->
                    <div v-else-if="step === 2" class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-xl font-bold text-foreground">Review your enrollment</h2>
                            <p class="text-muted-foreground text-sm mt-1">Confirm details before payment</p>
                        </div>

                        <!-- Batch summary -->
                        <div class="rounded-xl border border-border bg-muted/30 p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Programme</p>
                            <p class="font-bold text-foreground">{{ batch.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ batch.courses?.map(c => c.title).join(' · ') }}</p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Starts</span>
                                <span class="font-medium">{{ batch.start_date }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-border pt-3">
                                <span class="text-sm text-muted-foreground">Enrollment fee</span>
                                <span class="text-xl font-bold text-foreground">{{ fmtNaira(batch.price) }}</span>
                            </div>
                            <p v-if="batch.price_note" class="text-xs text-muted-foreground">{{ batch.price_note }}</p>
                        </div>

                        <!-- Student summary -->
                        <div class="rounded-xl border border-border bg-card p-4 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ accountType === 'parent' ? 'Student' : 'You' }}
                            </p>
                            <p class="font-medium text-foreground">{{ studentForm.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ studentForm.email }}</p>
                            <p class="text-sm text-muted-foreground">{{ studentForm.phone }}</p>
                        </div>

                        <!-- Parent summary -->
                        <div v-if="accountType === 'parent'" class="rounded-xl border border-border bg-card p-4 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Parent/Guardian</p>
                            <p class="font-medium text-foreground">{{ parentForm.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ parentForm.email }}</p>
                            <p class="text-sm text-muted-foreground capitalize">{{ parentForm.relationship }}</p>
                        </div>

                        <!-- Info box -->
                        <div class="rounded-xl bg-primary/5 border border-primary/20 p-4 space-y-1.5 text-sm">
                            <p class="font-semibold text-foreground">What happens next</p>
                            <p class="text-muted-foreground text-xs">
                                Clicking "Pay Now" takes you to Paystack's secure payment page.
                                After successful payment, your enrollment is confirmed automatically
                                and you'll receive a welcome email with login details.
                            </p>
                        </div>

                        <!-- Paystack notice -->
                        <p class="text-center text-xs text-muted-foreground">
                            Payments are secured by Paystack. We accept debit cards, bank transfer, and USSD.
                        </p>

                        <!-- Navigation -->
                        <div class="flex justify-between pt-2">
                            <Button variant="outline" class="gap-2" :disabled="isSubmitting" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />Back
                            </Button>
                            <Button size="lg" class="gap-2 min-w-[140px]" :disabled="isSubmitting" @click="handleSubmit">
                                <RefreshCw v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                                <span>{{ isSubmitting ? 'Processing…' : `Pay ${fmtNaira(batch.price)}` }}</span>
                            </Button>
                        </div>
                    </div>

                </CardContent>
            </Card>

            <!-- School branding footer -->
            <div class="text-center text-xs text-muted-foreground">
                Enrolling with <strong>{{ tenant.name }}</strong> · Powered by Teach
            </div>
        </div>
    </div>
</template>