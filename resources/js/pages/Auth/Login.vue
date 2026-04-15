<script setup>
import { ref } from 'vue';
import { Copyright, GraduationCap } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import RoleSelector from '@/components/Auth/RoleSelector.vue';
import LoginForm from '@/components/Auth/LoginForm.vue';

const step = ref('role');
const selectedRole = ref(null);

defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    },
    copyrightText: {
        type: String,
        default: '2026 BetaEdge Platform. All rights reserved'
    }
});

const handleRoleSelect = (role) => {
    selectedRole.value = role;
    step.value = 'login';
};

const handleChangeRole = () => {
    step.value = 'role';
    selectedRole.value = null;
};

</script>

<template>
    <div class="min-h-screen flex">
        <!-- left side branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary relative overflow-hidden">

            <div class="absolute inset-0 bg-gradient-to-br from-primary to-primary/80" />

            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-20  w-96 h-96 bg-white rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 flex flex-col justify-between p-12 text-primary-foreground">

                <Link href="/" class="flex items-center gap-2">
                    <GraduationCap class="w-8 h-8" />
                    <span class="text-2xl font-bold">{{ name }}</span>
                </Link>

                <div class="space-y-6">
                    <h1 class="text-4xl font-bold leading-tight">
                        Welcome back to the future of online education
                    </h1>
                    <p class="text-lg opacity-90">
                        Manage your school, courses, and students all in one place. Join thousands of educators
                        transforming learning.
                    </p>
                    <div class="flex items-center gap-8 pt-4">
                        <div class="">
                            <p class="text-3xl font-bold">
                                10,000+
                            </p>
                            <p class="text-sm opacity-80">
                                Active
                            </p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold">500k+</p>
                            <p class="text-sm opacity-80">Students</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold">50K+</p>
                            <p class="text-sm opacity-80">Courses</p>
                        </div>
                    </div>

                </div>

                <p class="flex items-center gap-3 text-sm opacity-70">
                    <Copyright class="w-4 h-4 " />{{ copyrightText }}
                </p>
            </div>
        </div>

        <!-- right side - Role Selection / Login Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md space-y-8">
                <div class="lg:hidden flex justify-center">
                    <a href="/" class="flex items-center gap-2 text-primary">
                        <GraduationCap class="h-8 w-8" />
                        <span class="text-2xl font-bold">{{ name }}</span>
                    </a>
                </div>

                <div class="text-center space-y-2">
                    <h2 class="text-2xl font-bold">
                        {{ step === 'role' ? 'Sign in to your account' : 'Enter your credentials' }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ step === 'role' ? 'Choose your role to continue' : 'Access your dashboard' }}
                    </p>
                </div>

                <RoleSelector 
                    v-if="step === 'role'" 
                    @role-selected="handleRoleSelect" 
                />

                <LoginForm 
                    v-else 
                    :selected-role="selectedRole" 
                    @change-role="handleChangeRole" 
                />

            </div>
        </div>
    </div>
</template>

<style lang="css" scoped></style>