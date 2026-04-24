<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    ArrowLeft, Calendar, Clock, Users,
    MessageCircle, Video, BookOpen,
    ArrowRight, FileText, Link2, AlertCircle, ChevronDown,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent} from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

const props = defineProps({
    tenant:    { type: Object, required: true },
    batch:     { type: Object, required: true },
    materials: { type: Array,  default: () => [] },
    meta:      { type: Object, default: () => ({}) },
})

function fmtNaira(n) {
    if (!n) return 'Free'
    return '₦' + Number(n).toLocaleString('en-NG')
}

function fmtTime(t) {
    if (!t) return ''
    const [h, m] = t.split(':')
    const hour = parseInt(h)
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const spotsLeft = computed(() =>
    props.batch.spots_left ?? (props.batch.max_students - (props.batch.current_count ?? 0))
)

const spotsColor = computed(() => {
    const left = spotsLeft.value
    if (left <= 3) return 'text-destructive font-bold'
    if (left <= 10) return 'text-amber-600 font-semibold'
    return 'text-emerald-600'
})

const fillPct = computed(() =>
    Math.min(100, ((props.batch.current_count ?? 0) / (props.batch.max_students ?? 1)) * 100)
)

const expandedCourses = ref([])

const canEnroll = computed(() =>
    props.batch.enrollment_status === 'open' && !props.batch.is_full
)

function toggleCourse(courseId) {
    const idx = expandedCourses.value.indexOf(courseId)
    if (idx === -1) expandedCourses.value.push(courseId)
    else expandedCourses.value.splice(idx, 1)
}

function goEnroll() {
    router.visit(`/batches/${props.batch.slug}/enroll`)
}

function matIcon(type) {
    return { link: Link2, video: Video }[type] ?? FileText
}

// SEO head
if (typeof document !== 'undefined') {
    document.title = props.meta.title ?? props.batch.name
}
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Top nav -->
        <div class="bg-muted border-b sticky top-0 z-10">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-4">
                <button type="button"
                    class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                    @click="router.visit('/batches')">
                    <ArrowLeft class="h-4 w-4" />
                    All programmes
                </button>
                <div class="flex items-center gap-2 ml-auto">
                    <img v-if="tenant.logo" :src="tenant.logo" :alt="tenant.name" class="h-8 w-8 rounded-lg object-cover border" />
                    <span class="text-sm font-semibold text-foreground">{{ tenant.name }}</span>
                </div>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <div class="grid lg:grid-cols-3 gap-8">

                <!-- Main content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Hero image from first course -->
                    <div v-if="batch.courses?.[0]?.thumbnail"
                        class="h-64 rounded-2xl overflow-hidden bg-muted">
                        <img :src="batch.courses[0].thumbnail" :alt="batch.name"
                            class="h-full w-full object-cover" />
                    </div>

                    <!-- Batch name + badges -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge :variant="canEnroll ? 'default' : 'secondary'" class="text-xs">
                                {{ canEnroll ? 'Enrolling Now' : 'Enrollment Closed' }}
                            </Badge>
                            <Badge v-if="batch.subject_count > 1" variant="outline" class="text-xs">
                                {{ batch.subject_count }} Subjects
                            </Badge>
                        </div>
                        <h1 class="text-3xl font-bold text-foreground">{{ batch.name }}</h1>
                        <p v-if="batch.description" class="text-muted-foreground leading-relaxed">
                            {{ batch.description }}
                        </p>
                    </div>

                    <!-- Key info row -->
                    <div class="flex flex-wrap gap-5 text-sm text-muted-foreground">
                        <span class="flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            {{ batch.start_date }} → {{ batch.end_date }}
                        </span>
                        <span v-if="batch.duration_weeks" class="flex items-center gap-2">
                            <Clock class="h-4 w-4" />
                            {{ batch.duration_weeks }} weeks
                        </span>
                        <span class="flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            {{ batch.max_students }} students max
                        </span>
                    </div>

                    <Separator />

                    <!-- Courses + schedules -->
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold text-foreground flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-primary" />
                            What's included
                        </h2>
                        <div class="space-y-3">
                            <div v-for="course in batch.courses" :key="course.id"
                                class="rounded-xl border border-border bg-card p-4">
                                <div class="flex items-start gap-4">
                                    <div v-if="course.thumbnail"
                                        class="h-14 w-20 shrink-0 rounded-lg overflow-hidden bg-muted">
                                        <img :src="course.thumbnail" :alt="course.title"
                                            class="h-full w-full object-cover" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                                            <div>
                                                <p class="font-semibold text-foreground text-sm">{{ course.title }}</p>
                                                <Badge v-if="course.academic_level" variant="outline" class="text-xs">
                                                    {{ course.academic_level }}
                                                </Badge>
                                            </div>
                                            <button type="button"
                                                class="rounded-full p-2 text-muted-foreground hover:bg-muted transition"
                                                @click="toggleCourse(course.id)">
                                                <ChevronDown class="h-4 w-4 transition-transform"
                                                    :class="expandedCourses.includes(course.id) ? 'rotate-180' : ''" />
                                            </button>
                                        </div>
                                        <p v-if="course.description" class="text-xs text-muted-foreground mb-2 line-clamp-2">
                                            {{ course.description }}
                                        </p>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            <span v-if="course.session_day" class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />{{ course.session_day }}
                                            </span>
                                            <span v-if="course.session_time" class="flex items-center gap-1">
                                                <Clock class="h-3 w-3" />
                                                {{ fmtTime(course.session_time) }}
                                                <span v-if="course.duration_minutes">· {{ course.duration_minutes }}min</span>
                                            </span>
                                            <span v-if="course.platform_label" class="flex items-center gap-1">
                                                <Video class="h-3 w-3" />{{ course.platform_label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="expandedCourses.includes(course.id)" class="mt-4 rounded-xl border-t border-border pt-4 text-sm space-y-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Curriculum</p>
                                        <div v-if="course.materials?.length" class="space-y-2 mt-3">
                                            <div v-for="(material, index) in course.materials" :key="`${course.id}-${index}`"
                                                class="flex items-start gap-3 rounded-xl bg-muted/50 p-3">
                                                <component :is="matIcon(material.material_type)"
                                                    class="h-4 w-4 text-muted-foreground shrink-0" />
                                                <div class="min-w-0">
                                                    <p class="font-medium text-foreground">{{ material.title }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ material.module }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p v-else class="text-xs text-muted-foreground mt-2">No curriculum items available for this subject yet.</p>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Instructor</p>
                                        <div v-if="course.instructor" class="rounded-xl bg-muted p-3 text-sm">
                                            <p class="font-medium text-foreground">{{ course.instructor.name }}</p>
                                            <p v-if="course.instructor.qualification" class="text-xs text-muted-foreground">
                                                {{ course.instructor.qualification }}
                                            </p>
                                            <p v-if="course.instructor.bio" class="text-xs text-muted-foreground mt-1 line-clamp-3">
                                                {{ course.instructor.bio }}
                                            </p>
                                        </div>
                                        <p v-else class="text-xs text-muted-foreground">Instructor details will be published soon.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- What's included list -->
                    <!-- <div class="space-y-2">
                        <h2 class="text-lg font-bold text-foreground">What you get</h2>
                        <div class="grid sm:grid-cols-2 gap-2">
                            <div v-for="item in [
                                'Live interactive sessions',
                                'Course materials & PDFs',
                                'Assignments & feedback',
                                'Progress tracking',
                                'Certificate on completion',
                                batch.whatsapp_link ? 'WhatsApp group access' : null,
                            ].filter(Boolean)" :key="item"
                                class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                {{ item }}
                            </div>
                        </div>
                    </div> -->

                    <!-- Sample materials -->
                    <!-- <div v-if="materials.length" class="space-y-3">
                        <h2 class="text-lg font-bold text-foreground">Course materials preview</h2>
                        <div class="space-y-2">
                            <div v-for="(mat, i) in materials" :key="i"
                                class="flex items-center gap-3 p-3 rounded-lg border border-border bg-card/50 text-sm">
                                <component :is="matIcon(mat.material_type)"
                                    class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="text-foreground">{{ mat.title }}</span>
                                <span class="ml-auto text-xs text-muted-foreground">{{ mat.module }}</span>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Sticky sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-4">
                        <Card class="border-2" :class="canEnroll ? 'border-primary/30' : 'border-border'">
                            <CardContent class="p-5 space-y-4">
                                <!-- Price -->
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-foreground">{{ fmtNaira(batch.price) }}</p>
                                    <p v-if="batch.price_note" class="text-xs text-muted-foreground mt-0.5">
                                        {{ batch.price_note }}
                                    </p>
                                </div>

                                <!-- Spots progress -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-muted-foreground">
                                            {{ batch.current_count ?? 0 }} / {{ batch.max_students }} enrolled
                                        </span>
                                        <span :class="spotsColor">{{ spotsLeft }} spot{{ spotsLeft !== 1 ? 's' : '' }} left</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted overflow-hidden">
                                        <div class="h-full rounded-full transition-all"
                                            :class="fillPct >= 90 ? 'bg-destructive' : fillPct >= 70 ? 'bg-amber-500' : 'bg-primary'"
                                            :style="`width:${fillPct}%`" />
                                    </div>
                                </div>

                                <!-- Dates quick info -->
                                <div class="space-y-1.5 text-sm text-muted-foreground">
                                    <div class="flex justify-between">
                                        <span>Starts</span>
                                        <span class="font-medium text-foreground">{{ batch.start_date }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Ends</span>
                                        <span class="font-medium text-foreground">{{ batch.end_date }}</span>
                                    </div>
                                    <div v-if="batch.duration_weeks" class="flex justify-between">
                                        <span>Duration</span>
                                        <span class="font-medium text-foreground">{{ batch.duration_weeks }} weeks</span>
                                    </div>
                                </div>

                                <!-- Full warning -->
                                <div v-if="batch.is_full"
                                    class="flex items-center gap-2 p-2.5 rounded-lg bg-destructive/10 border border-destructive/20">
                                    <AlertCircle class="h-4 w-4 text-destructive shrink-0" />
                                    <p class="text-xs text-destructive font-medium">This batch is full.</p>
                                </div>

                                <!-- Urgency message -->
                                <div v-else-if="spotsLeft > 0 && spotsLeft <= 5"
                                    class="px-3 py-2 rounded-lg bg-orange-50 dark:bg-orange-950/30 border border-orange-200 text-center">
                                    <p class="text-xs font-bold text-orange-700 dark:text-orange-300">
                                        Only {{ spotsLeft }} spot{{ spotsLeft !== 1 ? 's' : '' }} left! Enroll now.
                                    </p>
                                </div>

                                <!-- CTA -->
                                <Button size="lg" class="w-full gap-2" :disabled="!canEnroll" @click="goEnroll">
                                    <span>{{ canEnroll ? 'Enroll Now' : 'Enrollment Closed' }}</span>
                                    <ArrowRight v-if="canEnroll" class="h-4 w-4" />
                                </Button>

                                <!-- WhatsApp -->
                                <a v-if="batch.whatsapp_link && canEnroll" :href="batch.whatsapp_link"
                                    target="_blank" rel="noopener noreferrer"
                                    class="flex items-center justify-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 font-medium py-2">
                                    <MessageCircle class="h-4 w-4" />
                                    Join WhatsApp group
                                </a>
                            </CardContent>
                        </Card>

                        <!-- Instructor cards -->
                        <div v-if="batch.courses?.some(c => c.instructor)" class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Instructors</p>
                            <div v-for="course in batch.courses.filter(c => c.instructor)" :key="course.id"
                                class="flex items-start gap-3 p-3 rounded-xl border border-border bg-card text-sm">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-sm">
                                    {{ course.instructor.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-foreground truncate">{{ course.instructor.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ course.title }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>