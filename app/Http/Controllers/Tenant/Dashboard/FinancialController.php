<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentPayment;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $year     = (int) ($request->get('year', now()->year));
        $month    = $request->get('month', '');

        // Monthly revenue for the selected year (completed payments only)
        $monthlyRevenue = EnrollmentPayment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereYear('paid_at', $year)
            ->select(
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(amount_kobo) / 100 as gross'),
                DB::raw('SUM(platform_fee_kobo) / 100 as platform_fee'),
                DB::raw('SUM(school_amount_kobo) / 100 as school_amount'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Build full 12-month chart data
        $months = collect(range(1, 12))->map(fn ($m) => [
            'month'        => $m,
            'month_label'  => date('M', mktime(0, 0, 0, $m, 1)),
            'gross'        => (float) ($monthlyRevenue->get($m)?->gross ?? 0),
            'platform_fee' => (float) ($monthlyRevenue->get($m)?->platform_fee ?? 0),
            'school_amount'=> (float) ($monthlyRevenue->get($m)?->school_amount ?? 0),
            'transactions' => (int) ($monthlyRevenue->get($m)?->transactions ?? 0),
        ]);

        // Summary stats
        $totalGross        = EnrollmentPayment::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'completed')->sum('amount_kobo') / 100;
        $totalPlatformFee  = EnrollmentPayment::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'completed')->sum('platform_fee_kobo') / 100;
        $totalSchoolAmount = EnrollmentPayment::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'completed')->sum('school_amount_kobo') / 100;
        $thisMonth         = EnrollmentPayment::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('school_amount_kobo') / 100;
        $lastMonth         = EnrollmentPayment::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('status', 'completed')
            ->whereMonth('paid_at', now()->subMonth()->month)->whereYear('paid_at', now()->subMonth()->year)->sum('school_amount_kobo') / 100;
        $growthPct         = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null;

        // Revenue by batch
        $byBatch = EnrollmentPayment::withoutGlobalScopes()
            ->where('enrollment_payments.tenant_id', $tenantId)
            ->where('enrollment_payments.status', 'completed')
            ->join('batches', 'batches.id', '=', 'enrollment_payments.batch_id')
            ->select(
                'batches.id as batch_id',
                'batches.batch_name',
                'batches.status as batch_status',
                DB::raw('SUM(enrollment_payments.amount_kobo) / 100 as gross'),
                DB::raw('SUM(enrollment_payments.school_amount_kobo) / 100 as school_amount'),
                DB::raw('COUNT(*) as enrollments')
            )
            ->groupBy('batches.id', 'batches.batch_name', 'batches.status')
            ->orderByDesc('school_amount')
            ->limit(20)
            ->get();

        // Recent transactions
        $recentPayments = EnrollmentPayment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with(['student.user', 'batch'])
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'reference'       => $p->paystack_reference,
                'student_name'    => $p->student?->user?->full_name ?? '—',
                'batch_name'      => $p->batch?->batch_name ?? '—',
                'amount'          => (float) $p->amount_naira,
                'platform_fee'    => (float) $p->platform_fee_naira,
                'school_amount'   => (float) $p->school_amount_naira,
                'channel'         => $p->channel,
                'paid_at'         => $p->paid_at?->format('M j, Y g:i A'),
            ]);

        return Inertia::render('School/Dashboard/Financial/Index', [
            'year'           => $year,
            'months'         => $months,
            'by_batch'       => $byBatch,
            'recent'         => $recentPayments,
            'stats' => [
                'total_gross'         => (float) $totalGross,
                'total_platform_fee'  => (float) $totalPlatformFee,
                'total_school_amount' => (float) $totalSchoolAmount,
                'this_month'          => (float) $thisMonth,
                'last_month'          => (float) $lastMonth,
                'growth_pct'          => $growthPct,
                'total_transactions'  => EnrollmentPayment::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            ],
        ]);
    }
}