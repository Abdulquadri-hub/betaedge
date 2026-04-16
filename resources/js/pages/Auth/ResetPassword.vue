<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { GraduationCap, Lock, Eye, EyeOff, Loader2, CheckCircle, Copyright } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner';

// props
const props = defineProps({
    email: {
        type: String,
        required: true
    },
    token: {
        type: String,
        required: true
    },
    name: {
        type: String,
        default: 'BetaEdge'
    },
    copyrightText: {
        type: String,
        default: '2026 BetaEdge Platform. All rights reserved'
    }
})

// Form state using Inertia
const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: ''
})

// UI state
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const resetSuccess = ref(false)

// methods
const handleSubmit = () => {
    form.post(route('password.update'), {
        onSuccess: () => {
            resetSuccess.value = true
            toast.success('Success', {
                description: 'Your password has been reset successfully'
            })
            setTimeout(() => {
                window.location.href = route('login.index')
            }, 2000)
        },
        onError: () => {
            toast.error('Error', {
                description: form.errors.token || 'Failed to reset password'
            })
        }
    })
}

</script>

<template>
    <div class="min-h-screen flex">
        <!-- left handside -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-primary/80" />
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 w-72 h-72 bg-white rounded-full blur-3xl" />
                <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl" />
            </div>
            <div class="relative z-10 flex flex-col justify-between p-12 text-primary-foreground">
                <Link href="/" class="flex items-center gap-2">
                    <GraduationCap class="h-8 w-8" />
                    <span class="text-2xl font-bold">{{ name }}</span>
                </Link>

                <div class="space-y-6">
                    <h1 class="text-4xl font-bold leading-tight">
                        Create a new password
                    </h1>
                    <p class="text-lg opacity-90">
                        Choose a strong password to secure your account and protect your data.
                    </p>
                </div>

                <p class="flex items-center gap-2 text-sm opacity-70">
                    <Copyright class="w-4 h-4" /> {{ copyrightText }}
                </p>
            </div>
        </div>

        <!-- Right side - Reset Password Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md space-y-8">
                <div class="lg:hidden flex justify-center">
                    <Link href="/" class="flex items-center gap-2 text-primary">
                        <GraduationCap class="h-8 w-8" />
                        <span class="text-2xl font-bold">{{ name }}</span>
                    </Link>
                </div>

                <div v-if="!resetSuccess">
                    <div class="text-center space-y-2 mb-8">
                        <h2 class="text-2xl font-bold">Set new password</h2>
                        <p class="text-muted-foreground">
                            Your new password must be different from previously used passwords
                        </p>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Email Display (read-only) -->
                        <div class="space-y-2">
                            <Label>Email address</Label>
                            <div class="p-3 bg-muted rounded-lg text-sm">
                                {{ email }}
                            </div>
                        </div>

                        <!-- General Error -->
                        <div v-if="form.errors.token"
                            class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                            {{ form.errors.token }}
                        </div>

                        <!-- New Password Field -->
                        <div class="space-y-2">
                            <Label for="password">New password</Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input id="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                                    v-model="form.password"
                                    :class="['pl-10 pr-10', form.errors.password ? 'border-destructive' : '']" />
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                            <p v-else class="text-xs text-muted-foreground">
                                Must be at least 8 characters
                            </p>
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                                    placeholder="••••••••" v-model="form.password_confirmation"
                                    :class="['pl-10 pr-10', form.errors.password_confirmation ? 'border-destructive' : '']" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                    <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                            <template v-if="form.processing">
                                <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                Resetting password...
                            </template>
                            <template v-else>
                                Reset password
                            </template>
                        </Button>
                    </form>
                </div>

                <!-- Success State -->
                <div v-else class="text-center space-y-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto">
                        <CheckCircle class="h-8 w-8 text-primary" />
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold">Password reset successful</h2>
                        <p class="text-muted-foreground">
                            Your password has been successfully reset.<br />
                            Redirecting you to login...
                        </p>
                    </div>

                    <Button @click="window.location.href = route('login.index')" class="w-full">
                        Continue to login
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="css" scoped></style>