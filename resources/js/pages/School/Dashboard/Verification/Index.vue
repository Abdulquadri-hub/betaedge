<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    ShieldCheck, ShieldAlert, Clock, CheckCircle2,
    XCircle, AlertCircle, RefreshCw, Info,
    CreditCard, Globe, FileText,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    tenant:     { type: Object, required: true },
    submission: { type: Object, default: null  },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const isSubmitting = ref(false)

// ── ID types with their specific fields ───────────────────────────────────────
const ID_TYPES = [
    {
        value: 'nin',
        label: 'National ID (NIN)',
        placeholder_number: 'e.g., 12345678901',
        number_label: 'NIN (11 digits)',
        fields: ['id_number', 'first_name', 'last_name'],
    },
    {
        value: 'bvn',
        label: 'Bank Verification Number (BVN)',
        placeholder_number: 'e.g., 22345678901',
        number_label: 'BVN (11 digits)',
        fields: ['id_number', 'first_name', 'last_name', 'date_of_birth'],
    },
    {
        value: 'passport',
        label: 'International Passport',
        placeholder_number: 'e.g., A12345678',
        number_label: 'Passport Number',
        fields: ['passport_number', 'first_name', 'last_name', 'expiry_date'],
    },
    {
        value: 'drivers_license',
        label: "Driver's License",
        placeholder_number: 'e.g., FRN123A4567B',
        number_label: 'License Number',
        fields: ['id_number', 'first_name', 'last_name', 'date_of_birth'],
    },
    {
        value: 'cac',
        label: 'CAC (Business Registration)',
        placeholder_number: 'e.g., RC123456',
        number_label: 'RC Number',
        fields: ['rc_number', 'business_name'],
    },
    {
        value: 'voters_card',
        label: "Voter's Card",
        placeholder_number: 'e.g., 1234567890AB',
        number_label: 'VIN (Voter ID Number)',
        fields: ['id_number', 'first_name', 'last_name'],
    },
]

const form = reactive({
    id_type:        props.submission?.id_type ?? 'nin',
    id_number:      '',
    passport_number:'',
    rc_number:      '',
    business_name:  '',
    first_name:     '',
    last_name:      '',
    date_of_birth:  '',
    expiry_date:    '',
})

const errors = reactive({})

const selectedType = computed(() =>
    ID_TYPES.find(t => t.value === form.id_type) ?? ID_TYPES[0]
)

const hasField = (field) => selectedType.value.fields.includes(field)

function clearErr(f) { delete errors[f] }

function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])
    const e = {}
    if (!form.id_type) e.id_type = 'Select an ID type'

    if (hasField('id_number')      && !form.id_number?.trim())      e.id_number      = 'Required'
    if (hasField('passport_number')&& !form.passport_number?.trim())e.passport_number= 'Required'
    if (hasField('rc_number')      && !form.rc_number?.trim())       e.rc_number      = 'Required'
    if (hasField('business_name')  && !form.business_name?.trim())   e.business_name  = 'Required'
    if (hasField('first_name')     && !form.first_name?.trim())      e.first_name     = 'Required'
    if (hasField('last_name')      && !form.last_name?.trim())       e.last_name      = 'Required'
    if (hasField('date_of_birth')  && !form.date_of_birth)           e.date_of_birth  = 'Required'
    if (hasField('expiry_date')    && !form.expiry_date)             e.expiry_date    = 'Required'

    if (Object.keys(e).length) { Object.assign(errors, e); return }

    isSubmitting.value = true
    router.post('/dashboard/verification', { ...form }, {
        onError: (errs) => { Object.assign(errors, errs) },
        onFinish: () => { isSubmitting.value = false },
    })
}

const statusConfig = computed(() => {
    if (props.tenant.is_verified && props.tenant.verification_status != 'unverified') {
        return {
            icon: ShieldCheck,
            color: 'emerald',
            badge: 'Verified',
            title: 'Your school is verified',
            message: 'You can accept payments and appear on the BetaEdge marketplace.',
        }
    }

    const status = props.submission?.status ?? props.tenant.verification_status

    const map = {
        pending: {
            icon: Clock,
            color: 'amber',
            badge: 'Under Review',
            title: 'Verification pending',
            message: "Your documents are being reviewed. We'll notify you within 24 hours.",
        },
        under_review: {
            icon: Clock,
            color: 'blue',
            badge: 'Under Review',
            title: 'Documents under review',
            message: "Our team is reviewing your submission. This usually takes a few hours.",
        },
        rejected: {
            icon: XCircle,
            color: 'red',
            badge: 'Rejected',
            title: 'Verification rejected',
            message: props.submission?.rejection_reason ?? 'Your documents could not be verified. Please resubmit with correct details.',
        },
        unverified: {
            icon: ShieldAlert,
            color: 'muted',
            badge: 'Not Verified',
            title: 'Verify your school',
            message: 'Submit your ID to get verified. Verified schools can accept payments and appear on the marketplace.',
        },
    }

    return map[status] ?? map.unverified
})

const canResubmit = computed(() =>
    !props.tenant.is_verified &&
    !['pending', 'under_review'].includes(props.submission?.status)
)

const showForm = computed(() =>
    canResubmit.value || !props.submission
)
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-2xl mx-auto space-y-6">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground">ID Verification</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    School owners and instructors must be verified to process payments
                </p>
            </div>

            <!-- Status card -->
            <Card :class="[
                'border-2',
                tenant.is_verified && props.tenant.verification_status != 'unverified' ? 'border-emerald-300 bg-emerald-50/30 dark:bg-emerald-950/10' :
                submission?.status === 'pending' || submission?.status === 'under_review' ? 'border-amber-200 bg-amber-50/30' :
                submission?.status === 'rejected' ? 'border-red-200 bg-red-50/30' :
                'border-border'
            ]">
                <CardContent class="p-5">
                    <div class="flex items-start gap-4">
                        <div :class="[
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                            tenant.is_verified && props.tenant.verification_status != 'unverified' ? 'bg-emerald-100 dark:bg-emerald-900' :
                            submission?.status === 'rejected' ? 'bg-red-100 dark:bg-red-900' :
                            'bg-amber-100 dark:bg-amber-900'
                        ]">
                            <component :is="statusConfig.icon"
                                :class="[
                                    'h-6 w-6',
                                    tenant. is_verified && props.tenant.verification_status != 'unverified' ? 'text-emerald-600' :
                                    submission?.status === 'rejected' ? 'text-red-600' :
                                    submission?.status ? 'text-amber-600' : 'text-muted-foreground'
                                ]" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-semibold text-foreground">{{ statusConfig.title }}</p>
                                <Badge :variant="tenant.is_verified && props.tenant.verification_status != 'unverified' ? 'default' : 'secondary'" class="text-xs">
                                    {{ statusConfig.badge }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ statusConfig.message }}</p>
                            <p v-if="submission?.submitted_at" class="text-xs text-muted-foreground mt-1">
                                Submitted {{ submission.submitted_at }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- What verification unlocks -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base flex items-center gap-2">
                        <Info class="h-4 w-4 text-primary" />Why verification matters
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 pt-0">
                    <div class="flex items-start gap-3">
                        <CreditCard class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-foreground">Accept Payments</p>
                            <p class="text-xs text-muted-foreground">Configure Paystack and receive enrollment payments directly.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <Globe class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-foreground">Marketplace Listing</p>
                            <p class="text-xs text-muted-foreground">Your school appears on the BetaEdge marketplace, more student discovery.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <ShieldCheck class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-foreground">Verified Badge</p>
                            <p class="text-xs text-muted-foreground">Students see a verified badge, builds trust and increases enrollments.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Submission form (only shown if can submit) -->
            <Card v-if="showForm">
                <CardHeader>
                    <CardTitle class="text-base">Submit for Verification</CardTitle>
                    <CardDescription class="text-xs">
                        Your details are kept private and used only for identity verification.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">

                    <!-- ID type selector -->
                    <div class="space-y-1.5">
                        <Label>ID Type <span class="text-destructive">*</span></Label>
                        <Select :model-value="form.id_type"
                            @update:model-value="v => { form.id_type = v; Object.keys(errors).forEach(k => delete errors[k]) }">
                            <SelectTrigger :class="errors.id_type && 'border-destructive'">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in ID_TYPES" :key="t.value" :value="t.value">
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.id_type" class="text-xs text-destructive">{{ errors.id_type }}</p>
                    </div>

                    <!-- NIN / BVN / VIN / License fields -->
                    <div v-if="hasField('id_number')" class="space-y-1.5">
                        <Label>{{ selectedType.number_label }} <span class="text-destructive">*</span></Label>
                        <Input v-model="form.id_number"
                            :placeholder="selectedType.placeholder_number"
                            :class="errors.id_number && 'border-destructive'"
                            @input="clearErr('id_number')" />
                        <p v-if="errors.id_number" class="text-xs text-destructive">{{ errors.id_number }}</p>
                    </div>

                    <!-- Passport number -->
                    <div v-if="hasField('passport_number')" class="space-y-1.5">
                        <Label>Passport Number <span class="text-destructive">*</span></Label>
                        <Input v-model="form.passport_number"
                            :placeholder="selectedType.placeholder_number"
                            :class="errors.passport_number && 'border-destructive'"
                            @input="clearErr('passport_number')" />
                        <p v-if="errors.passport_number" class="text-xs text-destructive">{{ errors.passport_number }}</p>
                    </div>

                    <!-- CAC fields -->
                    <div v-if="hasField('rc_number')" class="space-y-1.5">
                        <Label>RC Number <span class="text-destructive">*</span></Label>
                        <Input v-model="form.rc_number"
                            placeholder="e.g., RC123456"
                            :class="errors.rc_number && 'border-destructive'"
                            @input="clearErr('rc_number')" />
                        <p v-if="errors.rc_number" class="text-xs text-destructive">{{ errors.rc_number }}</p>
                    </div>

                    <div v-if="hasField('business_name')" class="space-y-1.5">
                        <Label>Registered Business Name <span class="text-destructive">*</span></Label>
                        <Input v-model="form.business_name"
                            placeholder="As registered with CAC"
                            :class="errors.business_name && 'border-destructive'"
                            @input="clearErr('business_name')" />
                        <p v-if="errors.business_name" class="text-xs text-destructive">{{ errors.business_name }}</p>
                    </div>

                    <!-- Name fields (shown for NIN, BVN, Passport, License, VIN) -->
                    <div v-if="hasField('first_name')" class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label>First Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.first_name" placeholder="First name"
                                :class="errors.first_name && 'border-destructive'"
                                @input="clearErr('first_name')" />
                            <p v-if="errors.first_name" class="text-xs text-destructive">{{ errors.first_name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Last Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.last_name" placeholder="Last name"
                                :class="errors.last_name && 'border-destructive'"
                                @input="clearErr('last_name')" />
                            <p v-if="errors.last_name" class="text-xs text-destructive">{{ errors.last_name }}</p>
                        </div>
                    </div>

                    <!-- Date of birth (BVN, License) -->
                    <div v-if="hasField('date_of_birth')" class="space-y-1.5">
                        <Label>Date of Birth <span class="text-destructive">*</span></Label>
                        <Input v-model="form.date_of_birth" type="date"
                            :max="new Date().toISOString().split('T')[0]"
                            :class="errors.date_of_birth && 'border-destructive'"
                            @input="clearErr('date_of_birth')" />
                        <p v-if="errors.date_of_birth" class="text-xs text-destructive">{{ errors.date_of_birth }}</p>
                    </div>

                    <!-- Passport expiry -->
                    <div v-if="hasField('expiry_date')" class="space-y-1.5">
                        <Label>Passport Expiry Date <span class="text-destructive">*</span></Label>
                        <Input v-model="form.expiry_date" type="date"
                            :min="new Date().toISOString().split('T')[0]"
                            :class="errors.expiry_date && 'border-destructive'"
                            @input="clearErr('expiry_date')" />
                        <p v-if="errors.expiry_date" class="text-xs text-destructive">{{ errors.expiry_date }}</p>
                        <p class="text-xs text-muted-foreground">Passport must not be expired.</p>
                    </div>

                    <!-- Security note -->
                    <div class="flex items-start gap-2 rounded-lg bg-muted/40 border border-border p-3">
                        <ShieldCheck class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                        <p class="text-xs text-muted-foreground">
                            Your details are encrypted and used only for identity verification.
                            We will never share them with third parties.
                        </p>
                    </div>

                    <Button size="lg" class="w-full" :disabled="isSubmitting" @click="handleSubmit">
                        <RefreshCw v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Submitting…' : 'Submit for Verification' }}
                    </Button>
                </CardContent>
            </Card>

            <!-- Already verified -->
            <div v-else-if="tenant.is_verified && props.tenant.verification_status != 'unverified'"
                class="flex flex-col items-center py-10 text-center rounded-xl border border-emerald-200 bg-emerald-50/30">
                <ShieldCheck class="h-12 w-12 text-emerald-600 mb-3" />
                <p class="text-base font-bold text-foreground">Verified ✓</p>
                <p class="text-sm text-muted-foreground mt-1">Your school is fully verified and can accept payments.</p>
            </div>

        </div>
    </DashboardLayout>
</template>