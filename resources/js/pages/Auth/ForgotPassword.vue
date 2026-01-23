<script setup>
import { ref } from 'vue';
import { GraduationCap, Mail, Loader2, ArrowLeft, Copyright } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner';

// props
defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    },
    copyrightText: {
        type: String,
        default: '2026 BetaEdge Platform. All rights reserved'
    }
})

// reactive variables
const formData = ref({
    email: ''
})
const isLoading = ref(false)
const emailSent = ref(false)
const errors  = ref({})

// validation
const validateForm = () => {
    const newErrors = {}

    if(!formData.value.email) {
        newErrors.email = 'Email is required'
    } else if (!/\S+@\S+\.\S+/.test(formData.value.email)) {
        newErrors.email = 'Please enter a valid email'
    }

    errors.value = newErrors
    return Object.keys(newErrors).length === 0
}

// methods
const handleSubmit = async () => {

    if (!validateForm()) return

    isLoading.value = true
    errors.value = {}

    try {
        // make api call to the backend
        console.log('Forgot password payload:', formData.value);

        await new Promise(resolve => setTimeout(resolve, 1500))

        emailSent.value = true

        toast.success('Success', {
            description: 'Password reset link sent to your email'
        })
        
    } catch(error) {
        console.log(error);
        
        errors.value.general = 'Failed to send reset link. Please try again.'

        toast.error('Error', {
            description: 'Failed to send reset link',
            variant: 'destructive'
        })
    } finally {
        isLoading.value = false
    }
}


</script>

<template>
    <div class="min-h-screen flex">
        <!-- left hand side  -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary relative overflow-hidden">

            <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-primary/80" />

            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 flex flex-col justify-between p-12 text-primary-foreground">

                <Link href="/" class="flex items-center gap-2">
                    <GraduationCap class="w-8 h-8" />
                    <span class="text-2xl font-bold">{{ name }}</span>
                </Link>

                <div class="space-y-6">
                    <h1 class="text-4xl font-bold leading-right">
                        We'll help you get back in
                    </h1>
                    <p class="text-lg opacity-90">
                        Enter your email address and we'll send you a link to reset your password
                    </p>
                </div>

                <p class="flex items-center gap-2 text-sm opacity-70">
                    <Copyright class="w-4 h-4" /> {{ copyrightText }}
                </p>
            </div>

        </div>

        <!-- right hand side -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md space-y-8">
                <div class="lg:hidden flex justify-center">
                    <Link href="/" class="flex items-center gap-2 text-primary">
                        <GraduationCap class="h-8 w-8" />
                        <span class="text-2xl font-bold">{{ name }}</span>
                    </Link>
                </div>

                <div v-if="!emailSent">
                    <div class="text-center space-y-2 mb-8">
                        <h2 class="text-2xl font-bold">Forgot your password?</h2>
                        <p class="text-muted-foreground">
                            No worries, we'll send you reset instructions
                        </p>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- General Error -->
                        <div v-if="errors.general"
                            class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                            {{ errors.general }}
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email">Email address</Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input id="email" type="email" placeholder="you@example.com" v-model="formData.email"
                                    :class="['pl-10', errors.email ? 'border-destructive' : '']" />
                            </div>
                            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
                        </div>

                        <!-- Submit Button -->
                        <Button type="submit" class="w-full" size="lg" :disabled="isLoading">
                            <template v-if="isLoading">
                                <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                Sending...
                            </template>
                            <template v-else>
                                Send reset link
                            </template>
                        </Button>

                        <!-- Back to Login -->
                        <a href="/auth/login"
                            class="flex items-center justify-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="h-4 w-4" />
                            Back to login
                        </a>
                    </form>
                </div>

                <!-- Success State -->
                <div v-else class="text-center space-y-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto">
                        <Mail class="h-8 w-8 text-primary" />
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold">Check your email</h2>
                        <p class="text-muted-foreground">
                            We've sent a password reset link to<br />
                            <span class="font-medium text-foreground">{{ formData.email }}</span>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Didn't receive the email? Check your spam folder or
                        </p>
                        <Button @click="emailSent = false" variant="outline" class="w-full">
                            Try another email
                        </Button>
                    </div>

                    <Link href="/auth/login"
                        class="flex items-center justify-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="h-4 w-4" />
                        Back to login
                </Link>
                </div>
            </div>
        </div>

    </div>
</template>

<style lang="css" scoped></style>