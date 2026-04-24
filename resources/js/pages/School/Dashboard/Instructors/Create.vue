<script setup>
import { ref, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, ArrowLeft, Mail, User, DollarSign, CheckSquare,
} from 'lucide-vue-next'
//import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    batches: { type: Array, default: () => [] },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const isCreating = ref(false)

const form = reactive({
    email:          '',
    name:           '',
    phone:          '',
    batch_ids:      [],
    payment_type:   'per_batch',
    payment_amount: '',
    payment_terms:  '',
})

const PAYMENT_TYPES = [
    { value: 'per_batch',   label: 'Fixed per Batch',    desc: 'Paid when a batch completes'   },
    { value: 'per_student', label: 'Fixed per Student',  desc: 'Based on enrollment count'     },
    { value: 'monthly',     label: 'Monthly Salary',     desc: 'Fixed monthly payment'         },
    { value: 'custom',      label: 'Custom Arrangement', desc: 'Define your own terms'         },
]

function paymentLabel(type) {
    const found = PAYMENT_TYPES.find(p => p.value === type)
    return found?.label ?? type
}

function paymentSuffix(type) {
    return { per_batch: '/ batch', per_student: '/ student', monthly: '/ month', custom: '' }[type] ?? ''
}

function toggleBatch(id) {
    const idx = form.batch_ids.indexOf(id)
    if (idx === -1) form.batch_ids.push(id)
    else form.batch_ids.splice(idx, 1)
}

function handleSubmit() {
    const e = {}
    if (!form.email?.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) e.email = 'Valid email required'
    if (!form.payment_type) e.payment_type = 'Select a payment type'
    if (!form.payment_amount || Number(form.payment_amount) < 0) e.payment_amount = 'Enter payment amount'
    if (Object.keys(e).length) { alert(Object.values(e).join('\n')); return }

    isCreating.value = true
    router.post('/dashboard/instructors/invite', form, {
        onFinish: () => isCreating.value = false,
    })
}
</script>

<template>
    <DashboardLayout>
        <div class="min-h-screen bg-gray-50/50">
            <div class="max-w-4xl mx-auto p-6 space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="ghost" size="sm" @click="router.visit('/dashboard/instructors')" class="hover:bg-white">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Instructors
                        </Button>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Send Instructor Invite</h1>
                            <p class="text-gray-600 mt-1">Invite a new instructor to join your school</p>
                        </div>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-8">
                        <div class="space-y-8">
                            <!-- Basic Info Section -->
                            <div class="space-y-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2  rounded-lg">
                                        <User class="h-5 w-5 text-secondary" />
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900">Instructor Information</h2>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="email" class="text-sm font-medium text-gray-700">Email Address *</Label>
                                        <Input id="email" v-model="form.email" type="email" placeholder="instructor@example.com"
                                               class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="name" class="text-sm font-medium text-gray-700">Full Name</Label>
                                        <Input id="name" v-model="form.name" placeholder="John Doe"
                                               class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="phone" class="text-sm font-medium text-gray-700">Phone Number</Label>
                                        <Input id="phone" v-model="form.phone" placeholder="+1 (555) 123-4567"
                                               class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Agreement Section -->
                            <div class="border-t border-gray-200 pt-8 space-y-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg">
                                        <DollarSign class="h-5 w-5 text-secondary" />
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900">Payment Agreement</h2>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium text-gray-700">Payment Type *</Label>
                                        <Select v-model="form.payment_type">
                                            <SelectTrigger class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                                <SelectValue placeholder="Select payment type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="type in PAYMENT_TYPES" :key="type.value" :value="type.value">
                                                    <div class="flex flex-col">
                                                        <span class="font-medium">{{ type.label }}</span>
                                                        <span class="text-xs text-gray-500">{{ type.desc }}</span>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="amount" class="text-sm font-medium text-gray-700">Amount *</Label>
                                        <Input id="amount" v-model="form.payment_amount" type="number" step="0.01"
                                               :placeholder="'0.00' + paymentSuffix(form.payment_type)"
                                               class="h-11 border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label for="terms" class="text-sm font-medium text-gray-700">Payment Terms</Label>
                                    <Textarea id="terms" v-model="form.payment_terms" placeholder="Describe the payment terms and conditions..."
                                              class="min-h-[100px] border-gray-300 focus:border-blue-500 focus:ring-blue-500 resize-none" />
                                </div>
                            </div>

                            <!-- Batch Assignment Section -->
                            <div class="border-t border-gray-200 pt-8 space-y-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg">
                                        <CheckSquare class="h-5 w-5 text-secondary" />
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900">Batch Assignment</h2>
                                </div>

                                <div class="space-y-4">
                                    <p class="text-sm text-gray-600">Select the batches this instructor will be assigned to once they accept the invitation</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-for="batch in batches" :key="batch.id"
                                             class="flex items-start space-x-3 p-4 border border-gray-200 rounded-lg hover:border-primary hover:bg-secondary-50/50 transition-colors">
                                            <Checkbox :id="'batch-' + batch.id"
                                                      :checked="form.batch_ids.includes(batch.id)"
                                                      @update:checked="toggleBatch(batch.id)"
                                                      class="mt-0.5" />
                                            <Label :for="'batch-' + batch.id" class="flex-1 cursor-pointer">
                                                <div class="font-medium text-gray-900">{{ batch.name }}</div>
                                                <div class="text-sm text-gray-600 mt-1">{{ batch.subjects }}</div>
                                                <div class="text-xs text-gray-500 mt-1">{{ batch.count }} students • {{ batch.max }} max</div>
                                            </Label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="border-t border-gray-200 pt-8">
                                <div class="flex justify-end gap-4">
                                    <Button variant="outline" @click="router.visit('/dashboard/instructors')"
                                            class="px-6 h-11 border-gray-300 hover:bg-gray-50">
                                        Cancel
                                    </Button>
                                    <Button @click="handleSubmit" :disabled="isCreating"
                                            class="px-6 h-11 bg-primary hover:bg-secondary text-white">
                                        <Mail class="h-4 w-4 mr-2" />
                                        {{ isCreating ? 'Sending Invite...' : 'Send Invite' }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>