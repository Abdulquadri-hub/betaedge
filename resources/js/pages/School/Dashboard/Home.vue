<script setup>
import {computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    Users, BookOpen, UsersRound, Video, Wallet,
    TrendingUp, TrendingDown, Minus, ArrowRight,
    Clock, RefreshCw, ClipboardList, AlertCircle,
    CheckCircle2, MessageSquareWarning, Calendar,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback} from '@/components/ui/avatar'
import { Skeleton } from '@/components/ui/skeleton'
import { useDashboardStats } from '@/composables/useDashboardStats'
import { useUserContext } from '@/composables/useUserContext'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'



const { user, tenant } = useUserContext()
const {
    stats,
    recentActivity,
    upcomingSessions,
    revenueChart,
    isLoading,
    formattedRevenue,
    trendColor,
    //trendIcon,
    refresh,
} = useDashboardStats()

// ─── Stat cards config ────────────────────────────────────────────────────────
const statCards = computed(() => [
    {
        title: 'Total Students',
        value: stats.value.totalStudents.value,
        change: stats.value.totalStudents.change,
        trend: stats.value.totalStudents.trend,
        icon: Users,
        iconBg: 'bg-primary/10',
        iconColor: 'text-primary',
        href: '/dashboard/students',
        suffix: '',
    },
    {
        title: 'Active Batches',
        value: stats.value.activeBatches.value,
        change: stats.value.activeBatches.change,
        trend: stats.value.activeBatches.trend,
        icon: UsersRound,
        iconBg: 'bg-secondary/10',
        iconColor: 'text-secondary',
        href: '/dashboard/batches',
        suffix: '',
    },
    {
        title: 'Monthly Revenue',
        value: formattedRevenue.value,
        change: stats.value.monthRevenue.change,
        trend: stats.value.monthRevenue.trend,
        icon: Wallet,
        iconBg: 'bg-emerald-100 dark:bg-emerald-950',
        iconColor: 'text-emerald-600',
        href: '/dashboard/financials',
        suffix: '',
        isRevenue: true,
    },
    {
        title: 'Pending Enrollments',
        value: stats.value.pendingEnrollments.value,
        change: stats.value.pendingEnrollments.change,
        trend: stats.value.pendingEnrollments.trend,
        icon: ClipboardList,
        iconBg: 'bg-amber-100 dark:bg-amber-950',
        iconColor: 'text-amber-600',
        href: '/dashboard/enrollments',
        suffix: ' requests',
    },
])

// ─── Quick actions ────────────────────────────────────────────────────────────
const quickActions = [
    { label: 'New Batch', href: '/dashboard/batches', icon: UsersRound, color: 'text-primary' },
    { label: 'New Course', href: '/dashboard/courses', icon: BookOpen, color: 'text-secondary' },
    { label: 'Schedule Session', href: '/dashboard/live-sessions', icon: Video, color: 'text-emerald-600' },
    { label: 'View Reports', href: '/dashboard/reports', icon: TrendingUp, color: 'text-amber-600' },
]

// ─── Activity type config ────────────────────────────────────────────────────
const activityConfig = {
    enrollment: { icon: Users, bg: 'bg-primary/10', color: 'text-primary' },
    payment: { icon: Wallet, bg: 'bg-emerald-100 dark:bg-emerald-950', color: 'text-emerald-600' },
    session: { icon: Video, bg: 'bg-secondary/10', color: 'text-secondary' },
    complaint: { icon: MessageSquareWarning, bg: 'bg-red-100 dark:bg-red-950', color: 'text-red-600' },
}

// ─── Platform label ───────────────────────────────────────────────────────────
function platformLabel(platform) {
    return { jitsi: 'Jitsi Meet', zoom: 'Zoom', custom: 'Custom' }[platform] ?? platform
}

// ─── Time until session ───────────────────────────────────────────────────────
function timeUntil(isoDate) {
    const diff = new Date(isoDate) - Date.now()
    if (diff < 0) return 'Now'
    const mins = Math.floor(diff / 60000)
    if (mins < 60) return `in ${mins}m`
    const hrs = Math.floor(mins / 60)
    const rem = mins % 60
    if (rem === 0) return `in ${hrs}h`
    return `in ${hrs}h ${rem}m`
}

// ─── Revenue chart bar heights ────────────────────────────────────────────────
const maxRevenue = computed(() =>
    Math.max(...revenueChart.value.map(d => d.revenue))
)

function barHeight(revenue) {
    return Math.round((revenue / maxRevenue.value) * 100)
}

function formatRevenue(amount) {
    if (amount >= 1000000) return '₦' + (amount / 1000000).toFixed(1) + 'M'
    if (amount >= 1000) return '₦' + (amount / 1000).toFixed(0) + 'K'
    return '₦' + amount
}

// ─── Greeting ─────────────────────────────────────────────────────────────────
const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Good morning'
    if (hour < 17) return 'Good afternoon'
    return 'Good evening'
})

const firstName = computed(() => user.value.name.split(' ')[0])
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- ── Page header ─────────────────────────────────────────────────────── -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground tracking-tight">
                        {{ greeting }}, {{ firstName }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Here's what's happening at <span class="font-medium text-foreground">{{ tenant.name }}</span>
                        today.
                    </p>
                </div>
                <Button variant="outline" size="sm" class="shrink-0 gap-2" :disabled="isLoading" @click="refresh">
                    <RefreshCw :class="['h-3.5 w-3.5', isLoading && 'animate-spin']" />
                    Refresh
                </Button>
            </div>

            <!-- ── Stat cards ──────────────────────────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <template v-if="isLoading">
                    <Card v-for="i in 4" :key="i">
                        <CardContent class="p-5 space-y-3">
                            <Skeleton class="h-4 w-24" />
                            <Skeleton class="h-8 w-16" />
                            <Skeleton class="h-3 w-20" />
                        </CardContent>
                    </Card>
                </template>

                <template v-else>
                    <Link v-for="card in statCards" :key="card.title" :href="card.href" class="block group">
                        <Card
                            class="transition-all duration-200 hover:shadow-md hover:border-primary/30 group-hover:-translate-y-0.5">
                            <CardContent class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-muted-foreground font-medium">{{ card.title }}</p>
                                        <p class="text-2xl font-bold text-foreground mt-1 tracking-tight">
                                            {{ card.isRevenue ? card.value : card.value.toLocaleString() }}{{
                                            card.suffix }}
                                        </p>

                                        <!-- Trend -->
                                        <div class="flex items-center gap-1 mt-2">
                                            <component
                                                :is="card.trend === 'up' ? TrendingUp : card.trend === 'down' ? TrendingDown : Minus"
                                                class="h-3.5 w-3.5" :class="trendColor(card.trend)" />
                                            <span class="text-xs" :class="trendColor(card.trend)">
                                                {{ card.change > 0 ? '+' : '' }}{{ card.change }}%
                                            </span>
                                            <span class="text-xs text-muted-foreground">vs last month</span>
                                        </div>
                                    </div>

                                    <div
                                        :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ml-3', card.iconBg]">
                                        <component :is="card.icon" :class="['h-5 w-5', card.iconColor]" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </template>
            </div>

            <!-- ── Main content grid ───────────────────────────────────────────────── -->
            <div class="grid gap-6 lg:grid-cols-3">

                <!-- Left column (2/3) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Revenue chart -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base">Revenue Overview</CardTitle>
                                    <CardDescription class="text-xs mt-0.5">Last 6 months — school earnings after
                                        platform fee
                                    </CardDescription>
                                </div>
                                <Link href="/dashboard/financials">
                                    <Button variant="ghost" size="sm"
                                        class="gap-1.5 text-xs text-primary hover:text-primary">
                                        Full report
                                        <ArrowRight class="h-3 w-3" />
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0">
                            <!-- Bar chart (CSS only — no library needed for Phase 3) -->
                            <div class="flex items-end gap-3 h-36 mt-2">
                                <div v-for="(bar, i) in revenueChart" :key="bar.month"
                                    class="flex-1 flex flex-col items-center gap-1.5 group">
                                    <!-- Bar -->
                                    <div class="relative w-full flex items-end justify-center" style="height: 112px">
                                        <div class="w-full rounded-t-md transition-all duration-500 relative overflow-hidden"
                                            :class="i === revenueChart.length - 1
                                                ? 'bg-primary'
                                                : 'bg-primary/25 group-hover:bg-primary/40'" :style="{ height: barHeight(bar.revenue) + '%' }">
                                            <!-- Tooltip on hover -->
                                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 hidden group-hover:flex
                      bg-foreground text-background text-[10px] px-2 py-1 rounded whitespace-nowrap z-10 shadow">
                                                {{ formatRevenue(bar.revenue) }}
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Month label -->
                                    <span class="text-[10px] font-medium"
                                        :class="i === revenueChart.length - 1 ? 'text-primary' : 'text-muted-foreground'">
                                        {{ bar.month }}
                                    </span>
                                </div>
                            </div>

                            <!-- Summary row -->
                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-border">
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground">This month</p>
                                    <p class="text-sm font-bold text-foreground">{{ formattedRevenue }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground">New students</p>
                                    <p class="text-sm font-bold text-foreground">
                                        +{{ revenueChart[revenueChart.length - 1]?.students }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground">Growth</p>
                                    <p class="text-sm font-bold text-emerald-600">
                                        +{{ stats.monthRevenue?.change ?? 18 }}%
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent activity -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">Recent Activity</CardTitle>
                                <Badge variant="secondary" class="text-xs">Live</Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0">
                            <div class="space-y-1">
                                <div v-for="activity in recentActivity" :key="activity.id"
                                    class="flex items-start gap-3 rounded-lg p-2.5 hover:bg-muted/50 transition-colors">
                                    <!-- Icon -->
                                    <div
                                        :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-full', activityConfig[activity.type]?.bg ?? 'bg-muted']">
                                        <component :is="activityConfig[activity.type]?.icon ?? AlertCircle"
                                            :class="['h-3.5 w-3.5', activityConfig[activity.type]?.color ?? 'text-muted-foreground']" />
                                    </div>

                                    <!-- Text -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-foreground leading-snug">
                                            <span class="font-medium">{{ activity.actor }}</span>
                                            {{ activity.action }}
                                            <span class="font-medium">{{ activity.target }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{{ activity.time }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>

                <!-- Right column (1/3) -->
                <div class="space-y-6">

                    <!-- Quick actions -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div class="grid grid-cols-2 gap-2">
                                <Link v-for="action in quickActions" :key="action.label" :href="action.href"
                                    class="group">
                                    <div class="flex flex-col items-center gap-2 rounded-lg border border-border p-3
                  hover:border-primary/40 hover:bg-primary/5 transition-all duration-150 cursor-pointer">
                                        <component :is="action.icon"
                                            :class="['h-5 w-5 transition-transform group-hover:scale-110', action.color]" />
                                        <span
                                            class="text-xs text-center text-muted-foreground group-hover:text-foreground font-medium leading-tight">
                                            {{ action.label }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Upcoming sessions -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">Upcoming Sessions</CardTitle>
                                <Link href="/dashboard/live-sessions">
                                    <Button variant="ghost" size="sm"
                                        class="gap-1 text-xs text-primary hover:text-primary h-7">
                                        All
                                        <ArrowRight class="h-3 w-3" />
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0 space-y-3">
                            <div v-for="session in upcomingSessions" :key="session.id"
                                class="rounded-lg border border-border p-3 hover:border-primary/30 transition-colors">
                                <!-- Time badge -->
                                <div class="flex items-center justify-between mb-2">
                                    <Badge variant="outline"
                                        class="text-[10px] gap-1 px-1.5 h-5 border-primary/30 text-primary">
                                        <Clock class="h-2.5 w-2.5" />
                                        {{ timeUntil(session.scheduledAt) }}
                                    </Badge>
                                    <span class="text-[10px] text-muted-foreground capitalize">{{
                                        platformLabel(session.platform)
                                        }}</span>
                                </div>

                                <!-- Title -->
                                <p class="text-xs font-semibold text-foreground leading-snug line-clamp-2">
                                    {{ session.title }}
                                </p>
                                <p class="text-[11px] text-muted-foreground mt-0.5 truncate">{{ session.course }}</p>

                                <!-- Footer -->
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-border">
                                    <div class="flex items-center gap-1.5">
                                        <Avatar class="h-5 w-5">
                                            <AvatarFallback class="text-[8px] bg-primary/10 text-primary font-bold">
                                                {{session.instructor?.split(' ').map(n => n[0]).join('').slice(0, 2) ??
                                                'IN' }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span class="text-[10px] text-muted-foreground truncate max-w-[80px]">{{
                                            session.instructor
                                            }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] text-muted-foreground">
                                        <Users class="h-3 w-3" />
                                        {{ session.enrolledCount }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="upcomingSessions.length === 0" class="py-6 text-center">
                                <Calendar class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                                <p class="text-xs text-muted-foreground">No upcoming sessions</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- School health indicator -->
                    <Card class="border-dashed">
                        <CardContent class="p-4">
                            <p class="text-xs font-semibold text-foreground mb-3">School Health</p>
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    <span class="text-xs text-muted-foreground">Payments up to date</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    <span class="text-xs text-muted-foreground">{{ stats.activeBatches.value }} active
                                        batches
                                        running</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <AlertCircle
                                        :class="['h-3.5 w-3.5 shrink-0', stats.pendingEnrollments.value > 0 ? 'text-amber-500' : 'text-emerald-600']" />
                                    <span class="text-xs text-muted-foreground">
                                        {{ stats.pendingEnrollments.value > 0
                                            ? `${stats.pendingEnrollments.value} enrollment requests pending`
                                            : 'No pending enrollments'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>

        </div>
    </DashboardLayout>
</template>