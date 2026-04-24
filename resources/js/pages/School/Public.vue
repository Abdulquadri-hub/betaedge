<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    MapPin, Users, BookOpen, Clock, GraduationCap,
    Phone, Mail, Calendar, MessageCircle, ArrowRight,
    CheckCircle2, Video,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Separator } from '@/components/ui/separator'

const props = defineProps({
    tenant:       { type: Object, required: true },
    courses:      { type: Array,  default: () => [] },
    open_batches: { type: Array,  default: () => [] },
    instructors:  { type: Array,  default: () => [] },
    stats:        { type: Object, default: () => ({}) },
})

const activeTab = ref('courses')

// Navigate to batch listing — no Ziggy needed for plain URL
function goToBatches() {
    router.visit('/batches')
}

function goToBatch(batch) {
    router.visit(`/batches/${batch.slug}`)
}

// function fmtNaira(n) {
//     if (!n) return 'Free'
//     return '₦' + Number(n).toLocaleString('en-NG')
// }

function spotsLeft(batch) {
    return Math.max(0, (batch.max_students ?? 0) - (batch.current_count ?? 0))
}

function fillPct(batch) {
    if (!batch.max_students) return 0
    return Math.min(100, ((batch.current_count ?? 0) / batch.max_students) * 100)
}

// Set page title
if (typeof document !== 'undefined') {
    document.title = `${props.tenant.name} — Teach`
}
</script>

<template>
    <div class="min-h-screen bg-background">

        <!-- ── Cover image + school header ──────────────────────────────────── -->
        <div class="relative">
            <!-- Cover -->
            <div class="relative h-56 md:h-72 overflow-hidden bg-gradient-to-br from-primary/20 to-primary/5">
                <img v-if="tenant.cover_image"
                    :src="tenant.cover_image"
                    :alt="tenant.name"
                    class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
            </div>

            <!-- Header overlay -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative -mt-20 pb-6">
                    <div class="flex flex-col md:flex-row gap-5 items-start">

                        <!-- Logo -->
                        <Avatar class="h-28 w-28 border-4 border-background shadow-xl rounded-2xl shrink-0">
                            <AvatarImage v-if="tenant.logo" :src="tenant.logo" :alt="tenant.name" />
                            <AvatarFallback class="text-3xl font-bold bg-primary text-primary-foreground rounded-2xl">
                                {{ tenant.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <!-- Info -->
                        <div class="flex-1 min-w-0 pt-20 md:pt-4">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-foreground md:text-primary-foreground tracking-tight">
                                {{ tenant.name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-1 text-sm text-muted-foreground md:text-primary-foreground/80">
                                <span v-if="tenant.city" class="flex items-center gap-1.5">
                                    <MapPin class="h-4 w-4" />{{ tenant.city }}, Nigeria
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Users class="h-4 w-4" />{{ stats.total_students ?? 0 }} students
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <BookOpen class="h-4 w-4" />{{ stats.total_courses ?? 0 }} courses
                                </span>
                            </div>
                            <p v-if="tenant.description" class="mt-3 text-sm text-muted-foreground max-w-2xl">
                                {{ tenant.description }}
                            </p>
                        </div>

                        <!-- CTA buttons -->
                        <div class="flex flex-col gap-2 shrink-0 pt-20 md:pt-6">
                            <Button size="lg" class="gap-2 min-w-[140px]" @click="goToBatches">
                                Enroll Now
                            </Button>
                            <a v-if="tenant.phone" :href="`tel:${tenant.phone}`">
                                <Button variant="outline" size="lg" class="w-full">
                                    Contact School
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 space-y-6">

            <!-- ── Contact bar ─────────────────────────────────────────────── -->
            <Card>
                <CardContent class="py-4">
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <a v-if="tenant.phone" :href="`tel:${tenant.phone}`"
                            class="flex items-center gap-2 text-sm hover:text-primary transition-colors">
                            <Phone class="h-4 w-4 text-muted-foreground" />{{ tenant.phone }}
                        </a>
                        <a v-if="tenant.email" :href="`mailto:${tenant.email}`"
                            class="flex items-center gap-2 text-sm hover:text-primary transition-colors">
                            <Mail class="h-4 w-4 text-muted-foreground" />{{ tenant.email }}
                        </a>
                        <a v-if="tenant.whatsapp" :href="`https://wa.me/${tenant.whatsapp?.replace(/\D/g,'')}`"
                            target="_blank" rel="noopener noreferrer"
                            class="flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 transition-colors">
                            <MessageCircle class="h-4 w-4" />WhatsApp
                        </a>
                        <span v-if="tenant.year_established" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock class="h-4 w-4" />Est. {{ tenant.year_established }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- ── Open batches (shown when any are enrolling) ─────────────── -->
            <section v-if="open_batches.length > 0" class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" />
                        <h2 class="text-base font-bold text-foreground">Enrolling Now</h2>
                    </div>
                    <button type="button"
                        class="text-sm text-primary hover:underline font-medium flex items-center gap-1"
                        @click="goToBatches">
                        View all <ArrowRight class="h-3.5 w-3.5" />
                    </button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="batch in open_batches" :key="batch.id"
                        class="group rounded-xl border-2 border-emerald-200 dark:border-emerald-900 bg-card hover:border-primary/50 hover:shadow-md transition-all cursor-pointer p-4 space-y-3"
                        @click="goToBatch(batch)">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-sm text-foreground group-hover:text-primary transition-colors leading-snug">
                                {{ batch.name }}
                            </h3>
                            <Badge variant="default" class="text-xs shrink-0">Enrolling</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground truncate">
                            {{ batch.courses.join(' · ') }}
                        </p>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1 text-muted-foreground">
                                <Calendar class="h-3 w-3" />{{ batch.start_date }}
                            </span>
                            <span class="font-bold text-foreground">{{ batch.price_formatted }}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>{{ batch.current_count ?? 0 }} / {{ batch.max_students }}</span>
                                <span :class="spotsLeft(batch) <= 5 ? 'text-orange-600 font-semibold' : 'text-emerald-600'">
                                    {{ spotsLeft(batch) }} left
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full bg-primary"
                                    :style="`width:${fillPct(batch)}%`" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <Tabs v-model="activeTab">
                <TabsList class="grid w-full grid-cols-3 max-w-md">
                    <TabsTrigger value="courses" class="cursor-pointer">Courses</TabsTrigger>
                    <TabsTrigger value="instructors" class="cursor-pointer">Instructors</TabsTrigger>
                    <TabsTrigger value="about" class="cursor-pointer">About</TabsTrigger>
                </TabsList>

                <!-- Courses -->
                <TabsContent value="courses" class="mt-6">
                    <div v-if="courses.length === 0"
                        class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                        <BookOpen class="h-10 w-10 text-muted-foreground/30 mb-3" />
                        <p class="text-sm text-muted-foreground">No courses published yet</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="course in courses" :key="course.id"
                            class="overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 group cursor-pointer"
                            @click="goToBatches">

                            <!-- Thumbnail -->
                            <div class="relative h-44 overflow-hidden bg-gradient-to-br from-muted to-muted/50">
                                <img v-if="course.thumbnail"
                                    :src="course.thumbnail"
                                    :alt="course.title"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                <GraduationCap v-else
                                    class="h-12 w-12 text-muted-foreground/20 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" />
                            </div>

                            <CardHeader class="pb-2">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <Badge variant="outline" class="text-xs">{{ course.academic_level ?? 'General' }}</Badge>
                                    <span v-if="course.duration_weeks" class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Clock class="h-3.5 w-3.5" />{{ course.duration_weeks }}w
                                    </span>
                                </div>
                                <CardTitle class="text-base group-hover:text-primary transition-colors leading-snug">
                                    {{ course.title }}
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <p v-if="course.description"
                                    class="text-sm text-muted-foreground line-clamp-2 mb-4">
                                    {{ course.description }}
                                </p>

                                <Separator class="mb-4" />

                                <!-- What students get -->
                                <!-- <div class="space-y-1.5 mb-4">
                                    <div v-for="feat in ['Live interactive classes', 'Course materials included', 'Certificate on completion']"
                                        :key="feat"
                                        class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                        {{ feat }}
                                    </div>
                                </div> -->

                                <Button class="w-full gap-2 group-hover:bg-primary transition-colors" @click.stop="goToBatches">
                                    <Video class="h-4 w-4" />View Batches
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Instructors -->
                <TabsContent value="instructors" class="mt-6">
                    <div v-if="instructors.length === 0"
                        class="flex flex-col items-center py-16 text-center rounded-xl border border-dashed border-border">
                        <Users class="h-10 w-10 text-muted-foreground/30 mb-3" />
                        <p class="text-sm text-muted-foreground">No instructors listed yet</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Card v-for="instructor in instructors" :key="instructor.id">
                            <CardContent class="pt-6 pb-6">
                                <div class="flex flex-col items-center text-center">
                                    <Avatar class="h-20 w-20 mb-4 shadow-md">
                                        <AvatarImage v-if="instructor.avatar" :src="instructor.avatar" />
                                        <AvatarFallback class="text-xl bg-primary text-primary-foreground font-bold">
                                            {{ instructor.name.charAt(0) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <h3 class="font-bold text-foreground text-base">{{ instructor.name }}</h3>
                                    <p v-if="instructor.qualification"
                                        class="text-xs text-muted-foreground mt-0.5">
                                        {{ instructor.qualification }}
                                    </p>
                                    <p v-if="instructor.bio"
                                        class="text-sm text-muted-foreground mt-2 line-clamp-3">
                                        {{ instructor.bio }}
                                    </p>
                                    <Badge v-if="instructor.courses_count" variant="secondary" class="mt-3 gap-1">
                                        <GraduationCap class="h-3 w-3" />
                                        {{ instructor.courses_count }} course{{ instructor.courses_count !== 1 ? 's' : '' }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- About -->
                <TabsContent value="about" class="mt-6">
                    <Card>
                        <CardContent class="py-8">
                            <h3 class="text-xl font-bold text-foreground mb-4">About {{ tenant.name }}</h3>
                            <p class="text-muted-foreground mb-8 leading-relaxed">
                                {{ tenant.description ?? 'A dedicated learning institution.' }}
                            </p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
                                <div class="text-center">
                                    <p class="text-4xl font-extrabold text-primary">{{ stats.total_students ?? 0 }}</p>
                                    <p class="text-sm text-muted-foreground mt-1">Students</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-4xl font-extrabold text-primary">{{ stats.total_courses ?? 0 }}</p>
                                    <p class="text-sm text-muted-foreground mt-1">Courses</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-4xl font-extrabold text-primary">{{ stats.total_instructors ?? 0 }}</p>
                                    <p class="text-sm text-muted-foreground mt-1">Instructors</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-4xl font-extrabold text-primary">{{ stats.total_batches ?? 0 }}</p>
                                    <p class="text-sm text-muted-foreground mt-1">Programmes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

        </div>
    </div>
</template>