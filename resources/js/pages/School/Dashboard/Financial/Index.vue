<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    TrendingUp, TrendingDown,
    BarChart3,  Minus,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
// import { Badge } from '@/components/ui/badge'
// import {
//     Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
// } from '@/components/ui/table'
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const props = defineProps({
    year:     { type: Number, required: true },
    months:   { type: Array,  default: () => [] },
    by_batch: { type: Array,  default: () => [] },
    recent:   { type: Array,  default: () => [] },
    stats:    { type: Object, default: () => ({}) },
})

function fmtNaira(n) {
    if (!n) return '₦0'
    const num = Number(n)
    if (num >= 1_000_000) return '₦' + (num / 1_000_000).toFixed(1) + 'M'
    if (num >= 1_000)     return '₦' + (num / 1_000).toFixed(0) + 'k'
    return '₦' + num.toLocaleString('en-NG')
}

function fmtFull(n) {
    if (!n) return '₦0'
    return '₦' + Number(n).toLocaleString('en-NG')
}

const maxBar = computed(() => Math.max(...props.months.map(m => m.gross), 1))

const currentYear = ref(props.year)

function changeYear(y) {
    currentYear.value = y
    router.get(window.location.pathname, { year: y }, { preserveState: true })
}

const years = computed(() => {
    const yr = new Date().getFullYear()
    return [yr, yr - 1, yr - 2]
})

const channelLabel = (c) => ({ card: 'Card', bank: 'Bank Transfer', ussd: 'USSD', qr: 'QR' })[c] ?? c ?? '—'
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Financials</h1>
                    <p class="text-sm text-muted-foreground mt-1">Revenue from student enrollment payments</p>
                </div>
                <Select :model-value="String(currentYear)" @update:model-value="v => changeYear(Number(v))">
                    <SelectTrigger class="w-32">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="y in years" :key="y" :value="String(y)">{{ y }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Top stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Your Earnings (Total)</p>
                        <p class="text-2xl font-bold text-foreground mt-1">{{ fmtNaira(stats.total_school_amount) }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">After 10% platform fee</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">This Month</p>
                        <p class="text-2xl font-bold text-primary mt-1">{{ fmtNaira(stats.this_month) }}</p>
                        <div v-if="stats.growth_pct !== null" class="flex items-center gap-1 mt-1 text-xs"
                            :class="stats.growth_pct >= 0 ? 'text-emerald-600' : 'text-destructive'">
                            <component :is="stats.growth_pct > 0 ? TrendingUp : stats.growth_pct < 0 ? TrendingDown : Minus"
                                class="h-3.5 w-3.5" />
                            {{ stats.growth_pct >= 0 ? '+' : '' }}{{ stats.growth_pct }}% vs last month
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Gross Payments</p>
                        <p class="text-2xl font-bold text-foreground mt-1">{{ fmtNaira(stats.total_gross) }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">Before platform fee</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs font-medium text-muted-foreground">Transactions</p>
                        <p class="text-2xl font-bold text-foreground mt-1">{{ stats.total_transactions ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">Completed payments</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Monthly bar chart (plain CSS bars — no library needed) -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <BarChart3 class="h-4 w-4 text-primary" />
                        Monthly Revenue {{ currentYear }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-2 h-40">
                        <div v-for="m in months" :key="m.month"
                            class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="text-[10px] text-transparent group-hover:text-muted-foreground transition-colors">
                                {{ fmtNaira(m.school_amount) }}
                            </div>
                            <div class="w-full rounded-t-sm bg-primary/20 group-hover:bg-primary transition-colors"
                                :style="`height:${Math.round((m.gross / maxBar) * 100)}%`" />
                            <span class="text-[10px] text-muted-foreground">{{ m.month_label }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid lg:grid-cols-2 gap-6">

                <!-- Revenue by batch -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Revenue by Batch</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div v-if="by_batch.length === 0" class="py-8 text-center">
                            <p class="text-sm text-muted-foreground">No payment data yet</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="b in by_batch" :key="b.batch_id"
                                class="flex items-center justify-between gap-4 py-2 border-b border-border last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ b.batch_name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ b.enrollments }} enrollment{{ b.enrollments !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-foreground">{{ fmtNaira(b.school_amount) }}</p>
                                    <p class="text-xs text-muted-foreground">gross {{ fmtNaira(b.gross) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent transactions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Recent Payments</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div v-if="recent.length === 0" class="py-8 text-center">
                            <p class="text-sm text-muted-foreground">No payments yet</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="p in recent" :key="p.id"
                                class="flex items-start justify-between gap-3 py-2 border-b border-border last:border-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ p.student_name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ p.batch_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ p.paid_at }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-secondary">{{ fmtFull(p.amount) }}</p>
                                    <p class="text-[10px] text-muted-foreground capitalize">{{ channelLabel(p.channel) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Platform fee explanation -->
            <!-- <Card class="bg-muted/30">
                <CardContent class="p-4 flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                        <CreditCard class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-sm">
                        <p class="font-semibold text-foreground">Revenue split</p>
                        <p class="text-muted-foreground text-xs mt-0.5">
                            Students pay full amount via Paystack. Teach retains 10% as a platform fee.
                            You receive 90% of each enrollment payment.
                            Instructor payments are handled directly by you outside the platform.
                        </p>
                    </div>
                </CardContent>
            </Card> -->
        </div>
    </DashboardLayout>
</template>