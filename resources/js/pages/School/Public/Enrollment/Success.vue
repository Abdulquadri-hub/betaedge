<script setup>
import { router } from '@inertiajs/vue3'
import {
    CheckCircle2, Mail, BookOpen, Calendar,
    MessageCircle, ExternalLink, ArrowRight,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

// Props from EnrollmentController@paystackCallback
const props = defineProps({
    tenant:     { type: Object, required: true },
    batch:      { type: Object, required: true },
    enrollment: { type: Object, required: true },
})

if (typeof document !== 'undefined') {
    document.title = `Enrolled! — ${props.tenant.name}`
}
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full space-y-6">

            <!-- Success icon -->
            <div class="text-center space-y-2">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950 mx-auto">
                    <CheckCircle2 class="h-10 w-10 text-emerald-600" />
                </div>
                <h1 class="text-2xl font-bold text-foreground">Enrollment confirmed!</h1>
                <p class="text-muted-foreground text-sm">
                    <strong>{{ enrollment.student_name }}</strong> is now enrolled in
                    <strong>{{ enrollment.batch_name }}</strong>.
                </p>
            </div>

            <!-- What's next -->
            <Card>
                <CardContent class="p-5 space-y-4">
                    <div class="grid gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                <Mail class="h-4 w-4 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Check your email</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Login details and class information sent. Check spam if not found.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                <Calendar class="h-4 w-4 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">First class</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Programme starts <strong>{{ enrollment.start_date }}</strong>.
                                    Class links will appear in your student dashboard before each session.
                                </p>
                            </div>
                        </div>

                        <div v-if="enrollment.whatsapp_link" class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950">
                                <MessageCircle class="h-4 w-4 text-emerald-600" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Join the WhatsApp group</p>
                                <a :href="enrollment.whatsapp_link" target="_blank" rel="noopener noreferrer"
                                    class="text-xs text-emerald-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                    Click to join <ExternalLink class="h-3 w-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick ref -->
            <Card class="bg-muted/40">
                <CardContent class="p-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground">School</p>
                        <p class="font-medium text-foreground">{{ tenant.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Programme</p>
                        <p class="font-medium text-foreground truncate">{{ enrollment.batch_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Payment</p>
                        <p class="font-medium text-emerald-600">Confirmed ✓</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Student</p>
                        <p class="font-medium text-foreground truncate">{{ enrollment.student_name }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- CTA -->
            <div class="flex flex-col gap-3">
                <Button size="lg" class="gap-2" as-child>
                    <a :href="enrollment.login_url">
                        Go to Student Dashboard <ArrowRight class="h-4 w-4" />
                    </a>
                </Button>
                <Button variant="outline" @click="router.visit('/batches')">
                    Back to Programmes
                </Button>
            </div>
        </div>
    </div>
</template>