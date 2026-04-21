<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    Plus, Search, MoreVertical, Eye, Edit, Copy, Trash2,
    Globe, Archive, BookOpen, Users, RefreshCw,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
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
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    courses:    { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
    stats:      { type: Object, default: () => ({}) },
    pagination: { type: Object, default: null },
})

const page = usePage()
watch(() => page.props.flash, (f) => {
    if (f?.success) toast.success(f.success)
    if (f?.error)   toast.error(f.error)
}, { deep: true })

const search       = ref(props.filters.search ?? '')
const filterStatus = ref(props.filters.status ?? 'all')

const filteredCourses = computed(() => {
    let list = props.courses
    if (filterStatus.value !== 'all') {
        list = list.filter(c => c.status === filterStatus.value)
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(c =>
            c.title?.toLowerCase().includes(q) ||
            c.course_code?.toLowerCase().includes(q) ||
            c.academic_level?.toLowerCase().includes(q)
        )
    }
    return list
})

const statusCounts = computed(() => ({
    all:      props.courses.length,
    active:   props.courses.filter(c => c.status === 'active').length,
    draft:    props.courses.filter(c => c.status === 'draft').length,
    archived: props.courses.filter(c => c.status === 'archived').length,
}))

const statusConfig = {
    active:   { label: 'Published', variant: 'default'   },
    draft:    { label: 'Draft',     variant: 'secondary' },
    archived: { label: 'Archived',  variant: 'outline'   },
}

const showDeleteDialog = ref(false)
const deletingCourse   = ref(null)
const isDeleting       = ref(false)

function confirmDelete(course) {
    deletingCourse.value   = course
    showDeleteDialog.value = true
}

function handleDelete() {
    if (!deletingCourse.value) return
    isDeleting.value = true
    router.delete(`/dashboard/courses/${deletingCourse.value.id}`, {
        onSuccess: () => { showDeleteDialog.value = false; deletingCourse.value = null },
        onFinish:  () => { isDeleting.value = false },
    })
}

function handlePublish(course) {
    router.post(`/dashboard/courses/${course.id}/publish`, {}, { preserveScroll: true })
}

function handleDuplicate(course) {
    router.post(`/dashboard/courses/${course.id}/duplicate`, {})
}
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Courses</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Subjects you teach. Attach them to batches to offer them to students.
                    </p>
                </div>
                <Button class="gap-2 shrink-0" @click="router.visit('/dashboard/courses/create')">
                    <Plus class="h-4 w-4" />New Course
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Total Courses</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ props.stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Published</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ props.stats?.active ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Drafts</p>
                    <p class="text-2xl font-bold text-foreground mt-1">{{ props.stats?.draft ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-xs font-medium text-muted-foreground">Archived</p>
                    <p class="text-2xl font-bold text-muted-foreground mt-1">{{ props.stats?.archived ?? 0 }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <Tabs :model-value="filterStatus" @update:model-value="filterStatus = $event">
                    <TabsList>
                        <TabsTrigger value="all">All
                            <Badge variant="secondary" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ statusCounts.all }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="active">Published
                            <Badge variant="default" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ statusCounts.active }}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="draft">Drafts
                            <Badge variant="secondary" class="ml-1.5 h-4 px-1.5 text-[10px]">{{ statusCounts.draft }}</Badge>
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
                <div class="relative flex-1 sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search courses…" class="pl-9" />
                </div>
            </div>

            <!-- Empty -->
            <div v-if="filteredCourses.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
                <BookOpen class="h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="text-sm font-medium text-foreground">No courses found</p>
                <p class="text-xs text-muted-foreground mt-1">
                    {{ search ? 'Try a different search.' : 'Create your first course to get started.' }}
                </p>
                <Button class="mt-4 gap-2" size="sm" @click="router.visit('/dashboard/courses/create')">
                    <Plus class="h-4 w-4" />New Course
                </Button>
            </div>

            <!-- Grid -->
            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="course in filteredCourses" :key="course.id"
                    class="group overflow-hidden transition-all duration-200 hover:shadow-md hover:border-primary/20 cursor-pointer"
                    @click="router.visit(`/dashboard/courses/${course.id}`)">

                    <!-- Thumbnail -->
                    <div class="relative h-36 bg-gradient-to-br from-muted to-muted/60 overflow-hidden">
                        <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        <div v-else
                            class="flex h-full w-full items-center justify-center">
                            <BookOpen class="h-10 w-10 text-muted-foreground/30" />
                        </div>
                        <div class="absolute top-3 left-3">
                            <Badge :variant="statusConfig[course.status]?.variant ?? 'secondary'" class="text-xs shadow-sm">
                                {{ statusConfig[course.status]?.label ?? course.status }}
                            </Badge>
                        </div>
                        <div class="absolute top-3 right-3" @click.stop>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="secondary" size="icon"
                                        class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity shadow-sm">
                                        <MoreVertical class="h-3.5 w-3.5" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="router.visit(`/dashboard/courses/${course.id}`)">
                                        <Eye class="mr-2 h-4 w-4" />View
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="router.visit(`/dashboard/courses/${course.id}/edit`)">
                                        <Edit class="mr-2 h-4 w-4" />Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="handleDuplicate(course)">
                                        <Copy class="mr-2 h-4 w-4" />Duplicate
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem v-if="course.status === 'draft'" @click="handlePublish(course)">
                                        <Globe class="mr-2 h-4 w-4 text-primary" />Publish
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="course.status === 'active'" @click="router.post(`/dashboard/courses/${course.id}/archive`, {})">
                                        <Archive class="mr-2 h-4 w-4" />Archive
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-destructive focus:text-destructive"
                                        @click="confirmDelete(course)">
                                        <Trash2 class="mr-2 h-4 w-4" />Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <CardContent class="p-4">
                        <p class="font-semibold text-foreground text-sm leading-snug group-hover:text-primary transition-colors mb-1">
                            {{ course.title }}
                        </p>
                        <p class="text-xs text-muted-foreground mb-3">
                            {{ course.academic_level ?? '—' }}
                            <span v-if="course.duration_weeks"> · {{ course.duration_weeks }}w</span>
                        </p>

                        <div class="flex items-center justify-between text-xs text-muted-foreground border-t border-border pt-3">
                            <span class="flex items-center gap-1">
                                <Users class="h-3.5 w-3.5" />
                                {{ course.batch_count ?? 0 }} batch{{ (course.batch_count ?? 0) !== 1 ? 'es' : '' }}
                            </span>
                            <span class="font-mono text-muted-foreground/60 text-[10px]">{{ course.course_code }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Delete dialog -->
            <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete this course?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong class="text-foreground">{{ deletingCourse?.title }}</strong>
                            will be permanently deleted. Remove it from all batches first.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="isDeleting">Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            :disabled="isDeleting" @click="handleDelete">
                            <RefreshCw v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isDeleting ? 'Deleting…' : 'Delete Course' }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </DashboardLayout>
</template>