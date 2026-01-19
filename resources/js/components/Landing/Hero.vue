<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Play, ArrowRight, Users, BookOpen, Globe } from 'lucide-vue-next'

const PlayIcon = Play
const ArrowRightIcon = ArrowRight
const UsersIcon = Users
const BookOpenIcon = BookOpen
const GlobeIcon = Globe

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_schools: 10000,
            total_students: 50000,
            countries: 15
        })
    }
})

const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(0) + 'K'
    }
    return num.toString()
}
</script>


<template>
    <section class="relative pt-24 md:pt-32 pb-16 md:pb-24 overflow-hidden">
        <div class="absolute inset-0 gradient-surface" />
        <div class="absolute top-20 right-0 w-96 h-96 bg-primary/10 rounded-full blur-3xl animate-pulse-soft" />
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-secondary/10 rounded-full blur-3xl animate-pulse-soft" />

        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <!-- Badge -->
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-accent border border-accent-foreground/20 text-accent-foreground text-sm font-medium mb-8 animate-slide-up">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse" />
                    The #1 Education Platform for Africa
                </div>

                <!-- Headline -->
                <h1
                    class="font-display text-4xl md:text-5xl lg:text-6xl font-bold text-foreground leading-tight mb-6 animate-slide-up">
                    Launch Your Online School
                    <span class="text-gradient-primary">in Minutes</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-lg md:text-xl text-muted-foreground max-w-2xl mx-auto mb-10 animate-slide-up-delayed">
                    The all-in-one platform for educators to create, manage, and grow thriving online academies.
                    Like Shopify for education — simple, powerful, and built for African schools.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 animate-slide-up-delayed">
                    <Link href="/onboarding">
                        <button
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-md text-base font-medium h-12 px-6 gradient-hero text-primary-foreground hover:opacity-90 transition-opacity">
                            Start Your School Free
                            <ArrowRightIcon class="w-5 h-5" />
                        </button>
                    </Link>
                    <button
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-md text-base font-medium h-12 px-6 border border-input bg-background hover:bg-accent hover:text-accent-foreground">
                        <PlayIcon class="w-5 h-5" />
                        Watch Demo
                    </button>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 md:gap-8 max-w-2xl mx-auto animate-scale-in">
                    <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl bg-primary/10">
                            <UsersIcon class="w-6 h-6 text-primary" />
                        </div>
                        <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                            {{ formatNumber(stats.total_schools) }}+
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Active Schools
                        </div>
                    </div>
                    <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl bg-secondary/10">
                            <BookOpenIcon class="w-6 h-6 text-secondary" />
                        </div>
                        <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                            {{ formatNumber(stats.total_students) }}+
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Students Learning
                        </div>
                    </div>
                    <div class="text-center p-4 rounded-2xl bg-card shadow-sm border border-border">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-xl bg-primary/10">
                            <GlobeIcon class="w-6 h-6 text-primary" />
                        </div>
                        <div class="font-display text-2xl md:text-3xl font-bold text-foreground">
                            {{ stats.countries }}+
                        </div>
                        <div class="text-sm text-muted-foreground">
                            African Countries
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>