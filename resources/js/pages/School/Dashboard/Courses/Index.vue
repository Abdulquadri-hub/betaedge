<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Plus, Search, BookOpen,
    MoreVertical, Eye, Edit, Copy, Archive,
    Trash2, Star, Clock, Calendar,
    Video, AlertCircle, RefreshCw,
    Globe, Lock,
} from 'lucide-vue-next'
// import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { toast } from "vue-sonner"
import { useDashboardCourses } from '@/composables/useDashboardCourses'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const {
    filteredCourses, isLoading, error,
    search, filterStatus, statusCounts,
    totalRevenue, totalStudents,
    formatNaira, formatTime, platformLabel,
    publishCourse, archiveCourse, deleteCourse, duplicateCourse,
} = useDashboardCourses()


const statusConfig = {
    published: { label: 'Published', variant: 'default', icon: Globe },
    draft: { label: 'Draft', variant: 'secondary', icon: Lock },
    archived: { label: 'Archived', variant: 'outline', icon: Archive },
}

const categoryColors = [
    'bg-primary/10 text-primary',
    'bg-secondary/10 text-secondary',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    'bg-amber-100   text-amber-700   dark:bg-amber-950   dark:text-amber-400',
    'bg-violet-100  text-violet-700  dark:bg-violet-950  dark:text-violet-400',
]

function courseColor(id) {
    const idx = id ? parseInt(id.replace(/\D/g, '').slice(-1)) % categoryColors.length : 0
    return categoryColors[idx]
}

function initials(title) {
    return title.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

// function fmtDate(iso) {
//     if (!iso) return '—'
//     return new Date(iso).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
// }


function goToBuilder(course) {
    router.visit(`/dashboard/courses/${course.id}/edit`)
}

function viewCourse(course) {
    router.visit(`/dashboard/courses/${course.id}`)
}


const isPublishing = ref(null) // course id being published

async function handlePublish(course) {
    isPublishing.value = course.id
    try {
        const result = await publishCourse(course.id)
        if (result?.success !== false) {
            toast({ title: 'Course published', description: `${course.title} is now live and accepting students.` })
        }
    } finally {
        isPublishing.value = null
    }
}


async function handleArchive(course) {
    const result = await archiveCourse(course.id)
    if (result?.success !== false) {
        toast({ title: 'Course archived', description: `${course.title} is no longer visible to students.` })
    }
}


async function handleDuplicate(course) {
    const result = await duplicateCourse(course.id)
    if (result?.success !== false) {
        toast({ title: 'Course duplicated', description: 'A draft copy has been created.' })
    }
}


const showDeleteDialog = ref(false)
const deletingCourse = ref(null)
const isDeleting = ref(false)

function confirmDelete(course) {
    deletingCourse.value = course
    showDeleteDialog.value = true
}

async function handleDelete() {
    if (!deletingCourse.value) return
    isDeleting.value = true
    try {
        const result = await deleteCourse(deletingCourse.value.id)
        if (result?.success !== false) {
            toast({ title: 'Course deleted', description: `${deletingCourse.value.title} has been permanently removed.` })
            showDeleteDialog.value = false
            deletingCourse.value = null
        }
    } finally {
        isDeleting.value = false
    }
}
</script>

<template>
    <DashboardLayout>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground tracking-tight">Courses</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Build, publish, and manage your course catalogue.
                </p>
            </div>
            <Button class="gap-2 shrink-0" @click="router.visit('/dashboard/courses/create')">
                <Plus class="h-4 w-4" />
                New Course
            </Button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-border bg-card p-4">
                <p class="text-xs text-muted-foreground font-medium">Total Courses</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ statusCounts.all }}</p>
                <p class="text-xs text-muted-foreground">{{ statusCounts.published }} published</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <p class="text-xs text-muted-foreground font-medium">Total Students</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ totalStudents.toLocaleString() }}</p>
                <p class="text-xs text-muted-foreground">across all courses</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <p class="text-xs text-muted-foreground font-medium">Total Revenue</p>
                <p class="text-2xl font-bold text-foreground mt-1">{{ formatNaira(totalRevenue) }}</p>
                <p class="text-xs text-muted-foreground">school earnings (after 10% fee)</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <p class="text-xs text-muted-foreground font-medium">Drafts</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ statusCounts.draft }}</p>
                <p class="text-xs text-muted-foreground">not yet published</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <Tabs :model-value="filterStatus" class="flex-1" @update:model-value="filterStatus = $event">
                <TabsList>
                    <TabsTrigger value="all">
                        All
                        <Badge variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">{{ statusCounts.all }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="published">
                        Published
                        <Badge variant="default" class="ml-1.5 h-5 px-1.5 text-xs">{{ statusCounts.published }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="draft">
                        Drafts
                        <Badge variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">{{ statusCounts.draft }}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="archived">
                        Archived
                        <Badge variant="outline" class="ml-1.5 h-5 px-1.5 text-xs">{{ statusCounts.archived }}</Badge>
                    </TabsTrigger>
                </TabsList>
            </Tabs>

            <div class="relative w-full sm:w-64">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input v-model="search" placeholder="Search courses..." class="pl-9" />
            </div>
        </div>

        <div v-if="error"
            class="flex items-center gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3">
            <AlertCircle class="h-4 w-4 text-destructive shrink-0" />
            <p class="text-sm text-destructive">{{ error }}</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">

            <template v-if="isLoading">
                <div v-for="i in 5" :key="i" class="rounded-xl border border-border bg-card animate-pulse">
                    <div class="h-32 bg-muted rounded-t-xl" />
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-muted rounded w-3/4" />
                        <div class="h-3 bg-muted rounded w-1/2" />
                    </div>
                </div>
            </template>

            <div v-else-if="filteredCourses.length === 0"
                class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
                <BookOpen class="h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="text-sm font-medium text-foreground">No courses found</p>
                <p class="text-xs text-muted-foreground mt-1">
                    {{ search ? 'Try a different search term.' : 'Create your first course to get started.' }}
                </p>
                <Button class="mt-4 gap-2" size="sm" @click="router.visit('/dashboard/courses/create')">
                    <Plus class="h-4 w-4" />Create Course
                </Button>
            </div>

            <div v-for="course in filteredCourses" :key="course.id"
                class="group rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:shadow-lg hover:border-primary/30 hover:-translate-y-0.5 cursor-pointer"
                @click="viewCourse(course)">
                <!-- Thumbnail / colour block -->
                <div class="relative h-28 flex items-center justify-center overflow-hidden"
                    :class="course.thumbnail ? '' : courseColor(course.id)">
                    <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title"
                        class="w-full h-full object-cover" />
                    <span v-else class="text-4xl font-black opacity-30 select-none tracking-tighter">
                        {{ initials(course.title) }}
                    </span>

                    <div class="absolute top-2.5 left-2.5">
                        <Badge :variant="statusConfig[course.status]?.variant ?? 'outline'"
                            class="text-xs gap-1 shadow-sm">
                            <component :is="statusConfig[course.status]?.icon" class="h-3 w-3" />
                            {{ statusConfig[course.status]?.label }}
                        </Badge>
                    </div>

                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="secondary" size="icon" class="h-7 w-7 shadow-sm" @click.stop>
                                    <MoreVertical class="h-3.5 w-3.5" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" @click.stop>
                                <DropdownMenuItem @click.stop="viewCourse(course)">
                                    <Eye class="mr-2 h-4 w-4" />View Course
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="goToBuilder(course)">
                                    <Edit class="mr-2 h-4 w-4" />Edit / Builder
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="handleDuplicate(course)">
                                    <Copy class="mr-2 h-4 w-4" />Duplicate
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <DropdownMenuItem v-if="course.status === 'draft'" @click.stop="handlePublish(course)">
                                    <Globe class="mr-2 h-4 w-4 text-primary" />
                                    Publish Course
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="course.status === 'published'"
                                    @click.stop="handleArchive(course)">
                                    <Archive class="mr-2 h-4 w-4" />Archive Course
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <DropdownMenuItem class="text-destructive focus:text-destructive"
                                    :disabled="course.active_batches > 0" @click.stop="confirmDelete(course)">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    {{ course.active_batches > 0 ? 'Cannot delete (active batches)' : 'Delete Course' }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div class="p-4">
                    <!-- Title -->
                    <h3
                        class="font-semibold text-foreground text-sm leading-snug group-hover:text-primary transition-colors line-clamp-2 mb-1">
                        {{ course.title }}
                    </h3>

                    <!-- Level + Duration -->
                    <div class="flex items-center gap-2 mb-3 flex-wrap">
                        <Badge variant="outline" class="text-[10px] h-4 px-1.5">{{ course.academic_level }}</Badge>
                        <span class="text-[10px] text-muted-foreground flex items-center gap-1">
                            <Clock class="h-3 w-3" />{{ course.duration_weeks }} weeks
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-muted-foreground mb-1">
                        <Calendar class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ course.session_day }} · {{ formatTime(course.session_time) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground mb-4">
                        <Video class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ platformLabel(course.session_platform) }} · {{ course.session_duration_minutes }} min
                            sessions</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-border">
                        <div class="text-center">
                            <p class="text-sm font-bold text-foreground">{{ course.total_batches }}</p>
                            <p class="text-[10px] text-muted-foreground">Batches</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-foreground">{{ course.total_students }}</p>
                            <p class="text-[10px] text-muted-foreground">Students</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-foreground">{{ formatNaira(course.price_per_student) }}</p>
                            <p class="text-[10px] text-muted-foreground">Per student</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-border">
                        <div class="flex items-center gap-1">
                            <Star v-if="course.avg_rating" class="h-3.5 w-3.5 text-amber-500 fill-amber-500" />
                            <span class="text-xs font-medium text-foreground">
                                {{ course.avg_rating ?? 'No reviews yet' }}
                            </span>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600">
                            {{ formatNaira(course.total_revenue) }} earned
                        </span>
                    </div>

                    <div v-if="course.status === 'draft'" class="mt-3">
                        <Button size="sm" class="w-full gap-2 text-xs h-8" :disabled="isPublishing === course.id"
                            @click.stop="handlePublish(course)">
                            <RefreshCw v-if="isPublishing === course.id" class="h-3.5 w-3.5 animate-spin" />
                            <Globe v-else class="h-3.5 w-3.5" />
                            {{ isPublishing === course.id ? 'Publishing...' : 'Publish Course' }}
                        </Button>
                    </div>

                    <div v-else-if="course.status === 'published'" class="mt-3">
                        <Button size="sm" variant="outline" class="w-full gap-2 text-xs h-8"
                            @click.stop="goToBuilder(course)">
                            <Edit class="h-3.5 w-3.5" />
                            Open Builder
                        </Button>
                    </div>
                </div>
            </div>

        </div>

        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this course?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <strong class="text-foreground">{{ deletingCourse?.title }}</strong> and all its materials
                        will be permanently deleted. Completed batch records and student data will be retained
                        for compliance. This cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="isDeleting">Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        :disabled="isDeleting" @click="handleDelete">
                        <RefreshCw v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isDeleting ? 'Deleting...' : 'Delete Course' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </div>
    </DashboardLayout>
</template>