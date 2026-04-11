<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap, Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';

const GraduationCapIcon = GraduationCap
const MenuIcon = Menu
const XIcon = X

const props = defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    }
})

const isOpen = ref(false)

const navLinks = [
    { label: 'Features', href: '#features' },
    { label: 'Pricing', href: '#pricing' },
    { label: 'Marketplace', href: '/marketplace' },
    { label: 'About', href: '#about' },
]

</script>

<template>
    <nav class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-lg border-b border-border">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16 md:h-20">
                <Link href="/" class="flex items-center gap-2 group">
                    <div
                        class="w-10 h-10 rounded-xl gradient-hero flex items-center justify-center shadow-md group-hover:shadow-glow transition-shadow duration-300">
                        <GraduationCapIcon class="w-6 h-6 text-primary-foreground" />
                    </div>
                    <span class="font-display font-bold tex-xl text-foreground">
                        {{ props.name }}
                    </span>
                </Link>

                <!-- Desktop navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href"
                        class="text-muted-foreground hover:text-foreground font-medium transition-colors duration-200">
                        {{ link.label }}
                    </Link>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    <Link href="auth/login">
                        <button
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 py-2 hover:bg-accent cursor-pointer hover:text-accent-foreground">
                            Log In
                        </button>
                    </Link>
                    <Link href="/onboarding">
                        <button
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 py-2 bg-primary cursor-pointer text-primary-foreground hover:bg-primary/90">
                            Start Your School
                        </button>
                    </Link>
                    <Link href="/onboarding/instructor">
                        <button
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 py-2 border-2 border-secondary cursor-pointer text-secondary hover:bg-secondary/90 hover:text-white">
                            Become a Tutor
                        </button>
                    </Link>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-foreground" @click="isOpen = !isOpen" aria-label="Toggle menu">
                    <MenuIcon v-if="!isOpen" class="w-6 h-6" />
                    <XIcon v-else class="w-6 h-6" />
                </button>
            </div>

            <!-- Mobile Menu -->
            <transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2">
                <div v-if="isOpen" class="md:hidden py-4 border-t border-border">
                    <div class="flex flex-col gap-4">
                        <a v-for="link in navLinks" :key="link.label" :href="link.href"
                            class="text-muted-foreground hover:text-foreground font-medium py-2 transition-colors duration-200"
                            @click="isOpen = false">
                            {{ link.label }}
                        </a>
                        <div class="flex flex-col gap-2 pt-4 border-t border-border">
                            <Link href="auth/login">
                                <button
                                    class="w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 py-2 hover:bg-accent hover:text-accent-foreground">
                                    Log In
                                </button>
                            </Link>
                            <Link href="/onboarding">
                                <button
                                    class="w-full inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90">
                                    Start Your School
                                </button>
                            </Link>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </nav>
</template>