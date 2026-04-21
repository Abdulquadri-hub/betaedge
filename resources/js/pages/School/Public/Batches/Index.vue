<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Search, Users, Calendar, BookOpen,
    GraduationCap, ArrowRight, Filter, 
} from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
// import { Card, CardContent } from '@/components/ui/card'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'

// ── Props from PublicBatchController@index ────────────────────────────────────
const props = defineProps({
    tenant:  { type: Object, required: true },
    batches: { type: Array,  default: () => [] },
    levels:  { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({ search: '', level: '' }) },
    meta:    { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const level  = ref(props.filters.level  ?? 'all')

function applyFilters() {
    const params = {}
    if (search.value?.trim()) params.q     = search.value.trim()
    if (level.value && level.value !== 'all') params.level = level.value
    router.get('/batches', params, { preserveState: true, preserveScroll: true })
}

function clearFilters() {
    search.value = ''
    level.value  = 'all'
    router.get('/batches', {}, { preserveState: true })
}

// Navigate to batch detail page using plain URL (no Ziggy needed for public pages)
function goToBatch(batch) {
    router.visit(`/batches/${batch.slug}`)
}

const enrolling = computed(() => props.batches.filter(b => b.enrollment_status === 'open'))
const others    = computed(() => props.batches.filter(b => b.enrollment_status !== 'open'))

function fmtNaira(n) {
    if (n === null || n === undefined) return 'Free'
    const num = Number(n)
    if (!num) return 'Free'
    return '₦' + num.toLocaleString('en-NG')
}

function fillPct(batch) {
    if (!batch.max_students) return 0
    return Math.min(100, (batch.current_count / batch.max_students) * 100)
}

function spotsText(batch) {
    const left = batch.spots_left ?? (batch.max_students - batch.current_count)
    if (left <= 0) return 'Full'
    if (left === 1) return '1 spot left'
    return `${left} spots left`
}

function spotsClass(batch) {
    const left = batch.spots_left ?? (batch.max_students - batch.current_count)
    if (left <= 0)  return 'text-destructive font-bold'
    if (left <= 5)  return 'text-orange-600 font-semibold'
    if (left <= 10) return 'text-amber-600 font-medium'
    return 'text-emerald-600'
}

// Set SEO title
if (typeof document !== 'undefined') {
    document.title = props.meta?.title ?? `${props.tenant.name} — Programmes`
}
</script>

<template>
    <div class="min-h-screen bg-background">

        <!-- ── School header ─────────────────────────────────────────────────── -->
        <div class="border-b bg-card shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-5">
                <div class="flex items-center gap-4">
                    <!-- Logo -->
                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-border bg-muted flex items-center justify-center">
                        <img v-if="tenant.logo" :src="tenant.logo" :alt="tenant.name"
                            class="h-full w-full object-cover" />
                        <GraduationCap v-else class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg font-bold text-foreground truncate">{{ tenant.name }}</h1>
                        <p class="text-xs text-muted-foreground">
                            {{ tenant.tagline ?? tenant.description ?? 'Explore available programmes below' }}
                        </p>
                    </div>
                    <a v-if="tenant.phone" :href="`tel:${tenant.phone}`"
                        class="hidden sm:flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors">
                        {{ tenant.phone }}
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">

            <!-- ── Hero ──────────────────────────────────────────────────────── -->
            <div class="text-center space-y-3 py-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-foreground tracking-tight">
                    Available Programmes
                </h2>
                <p class="text-muted-foreground max-w-lg mx-auto text-sm sm:text-base">
                    Each programme is a batch you enroll in.
                    One payment covers all subjects in the batch.
                </p>
            </div>

            <!-- ── Search + filter ───────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <Input v-model="search"
                        placeholder="Search programmes…"
                        class="pl-9"
                        @keydown.enter="applyFilters" />
                </div>

                <!-- Level filter — NOTE: SelectItem value must never be empty string -->
                <Select v-if="levels.length > 0" :model-value="level"
                    @update:model-value="v => { level = v; applyFilters() }">
                    <SelectTrigger class="w-full sm:w-44">
                        <SelectValue placeholder="All levels" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All levels</SelectItem>
                        <SelectItem v-for="l in levels" :key="l" :value="l">{{ l }}</SelectItem>
                    </SelectContent>
                </Select>

                <Button class="gap-2 shrink-0" @click="applyFilters">
                    <Filter class="h-4 w-4" />Search
                </Button>
            </div>

            <!-- ── No results ─────────────────────────────────────────────────── -->
            <div v-if="batches.length === 0"
                class="flex flex-col items-center py-20 text-center rounded-2xl border border-dashed border-border">
                <BookOpen class="h-12 w-12 text-muted-foreground/30 mb-4" />
                <p class="text-base font-semibold text-foreground">No programmes found</p>
                <p class="text-sm text-muted-foreground mt-1">
                    {{ (filters.search || filters.level) ? 'Try different search terms.' : 'Check back soon — new programmes are added regularly.' }}
                </p>
                <Button v-if="filters.search || (filters.level && filters.level !== 'all')"
                    variant="outline" size="sm" class="mt-4" @click="clearFilters">
                    Clear filters
                </Button>
            </div>

            <!-- ── Enrolling now ──────────────────────────────────────────────── -->
            <section v-if="enrolling.length > 0" class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" />
                    <h3 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                        Enrolling Now
                    </h3>
                    <span class="text-xs text-muted-foreground">({{ enrolling.length }} programme{{ enrolling.length !== 1 ? 's' : '' }})</span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="batch in enrolling"
                        :key="batch.id"
                        class="group rounded-2xl border-2 border-border bg-card hover:border-primary/50 hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden"
                        @click="goToBatch(batch)"
                        role="button"
                        :aria-label="`View ${batch.name}`">

                        <!-- Thumbnail from first course -->
                        <div class="relative h-40 bg-gradient-to-br from-primary/10 to-primary/5 overflow-hidden">
                            <img v-if="batch.courses?.[0]?.thumbnail"
                                :src="batch.courses[0].thumbnail"
                                :alt="batch.name"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            <GraduationCap v-else class="h-12 w-12 text-primary/20 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" />

                            <!-- Status badge -->
                            <div class="absolute top-3 left-3">
                                <Badge class="bg-emerald-600 text-white text-xs font-semibold shadow">
                                    Enrolling
                                </Badge>
                            </div>

                            <!-- Urgency -->
                            <div v-if="(batch.spots_left ?? batch.max_students) <= 5"
                                class="absolute top-3 right-3">
                                <Badge variant="destructive" class="text-xs shadow">
                                    {{ spotsText(batch) }}!
                                </Badge>
                            </div>
                        </div>

                        <div class="p-5 space-y-3">
                            <!-- Name + subjects -->
                            <div>
                                <h4 class="font-bold text-foreground text-base group-hover:text-primary transition-colors leading-snug">
                                    {{ batch.name }}
                                </h4>
                                <p v-if="batch.courses?.length" class="text-xs text-muted-foreground mt-1 truncate">
                                    {{ batch.courses.map(c => c.title).join(' · ') }}
                                </p>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Calendar class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ batch.start_date }}</span>
                                <span v-if="batch.duration_weeks" class="text-muted-foreground/60">
                                    · {{ batch.duration_weeks }}w
                                </span>
                            </div>

                            <!-- Spots progress -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs">
                                    <span class="flex items-center gap-1 text-muted-foreground">
                                        <Users class="h-3 w-3" />
                                        {{ batch.current_count }} / {{ batch.max_students }}
                                    </span>
                                    <span :class="spotsClass(batch)">{{ spotsText(batch) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                        :class="fillPct(batch) >= 90 ? 'bg-destructive' : fillPct(batch) >= 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                                        :style="`width:${fillPct(batch)}%`" />
                                </div>
                            </div>

                            <!-- Price + CTA -->
                            <div class="flex items-center justify-between pt-2 border-t border-border">
                                <div>
                                    <span class="text-xl font-extrabold text-foreground">
                                        {{ batch.price_formatted ?? fmtNaira(batch.price) }}
                                    </span>
                                    <p v-if="batch.price_note"
                                        class="text-[10px] text-muted-foreground leading-none mt-0.5">
                                        {{ batch.price_note }}
                                    </p>
                                </div>
                                <span class="flex items-center gap-1 text-sm text-primary font-semibold group-hover:gap-2 transition-all">
                                    Enroll <ArrowRight class="h-4 w-4" />
                                </span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- ── Other programmes (closed / upcoming) ───────────────────────── -->
            <section v-if="others.length > 0" class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                    Upcoming & Full Programmes
                </h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="batch in others"
                        :key="batch.id"
                        class="rounded-2xl border border-border bg-card/70 p-5 space-y-2 opacity-75">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-semibold text-sm text-foreground">{{ batch.name }}</h4>
                            <Badge variant="secondary" class="text-xs capitalize shrink-0">
                                {{ batch.enrollment_status === 'closed' ? 'Closed' : batch.status }}
                            </Badge>
                        </div>
                        <p v-if="batch.courses?.length" class="text-xs text-muted-foreground truncate">
                            {{ batch.courses.map(c => c.title).join(' · ') }}
                        </p>
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Calendar class="h-3.5 w-3.5" />
                            {{ batch.start_date ?? '—' }}
                        </div>
                        <p class="text-sm font-bold text-foreground">
                            {{ batch.price_formatted ?? fmtNaira(batch.price) }}
                        </p>
                    </article>
                </div>
            </section>

        </div>

        <!-- ── Footer ─────────────────────────────────────────────────────────── -->
        <footer class="border-t border-border mt-16 py-6 text-center text-xs text-muted-foreground">
            <p>{{ tenant.name }} · Powered by <strong>Teach</strong></p>
            <p v-if="tenant.phone" class="mt-1">{{ tenant.phone }}</p>
        </footer>
    </div>
</template>