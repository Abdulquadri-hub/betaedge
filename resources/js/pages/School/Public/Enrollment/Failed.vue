<script setup>
import { router } from '@inertiajs/vue3'
import { AlertCircle, RefreshCw, ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

const props = defineProps({
    tenant:    { type: Object, required: true },
    reference: { type: String, default: '' },
    message:   { type: String, default: 'Your payment could not be completed.' },
    batch_url: { type: String, required: true },
})
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full space-y-6 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10 mx-auto">
                <AlertCircle class="h-10 w-10 text-destructive" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-bold text-foreground">Payment not completed</h1>
                <p class="text-muted-foreground text-sm">{{ message }}</p>
            </div>

            <Card class="text-left">
                <CardContent class="p-4 space-y-2 text-sm">
                    <p class="font-medium text-foreground">What to do:</p>
                    <ul class="space-y-1.5 text-muted-foreground text-xs list-disc list-inside">
                        <li>Check your internet connection and try again.</li>
                        <li>Make sure your card/account has sufficient funds.</li>
                        <li>Try a different payment method (bank transfer, USSD).</li>
                        <li>Contact your bank if the problem persists.</li>
                    </ul>
                    <p v-if="reference" class="text-xs text-muted-foreground/70 pt-2 border-t border-border">
                        Reference: <span class="font-mono">{{ reference }}</span>
                    </p>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-3">
                <Button size="lg" class="gap-2" as-child>
                    <a :href="batch_url + '/enroll'">
                        <RefreshCw class="h-4 w-4" />Try Again
                    </a>
                </Button>
                <Button variant="outline" class="gap-2" as-child>
                    <a :href="batch_url">
                        <ArrowLeft class="h-4 w-4" />Back to Programme
                    </a>
                </Button>
            </div>

            <p class="text-xs text-muted-foreground">
                Need help? Contact <strong>{{ tenant.name }}</strong> directly.
            </p>
        </div>
    </div>
</template>