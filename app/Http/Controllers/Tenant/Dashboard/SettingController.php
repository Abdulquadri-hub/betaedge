<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;
use App\Contracts\Repositories\School\TenantPaymentConfigRepositoryInterface;
use App\Contracts\Repositories\School\TenantNotificationPreferenceRepositoryInterface;
use App\Models\TenantPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected AcademicLevelRepositoryInterface $academicLevelRepo,
        protected TenantPaymentConfigRepositoryInterface $paymentConfigRepo,
        protected TenantNotificationPreferenceRepositoryInterface $notifPrefRepo,
    ) {}

    public function index(): Response
    {
        $tenant   = app('tenant');
        $tenantId = session('active_tenant_id');

        $academicLevels = $this->academicLevelRepo->getPaginated(100)->items();
        $paymentConfig  = $this->paymentConfigRepo->getByTenant($tenantId);
        $notifPrefs     = $this->notifPrefRepo->getByTenant($tenantId);

        $currentSubscription = $tenant->getCurrentSubscription()?->load('plan');

        $plans = SubscriptionPlan::active()->ordered()->get()->map(fn ($plan) => [
            'key'            => $plan->slug,
            'name'           => $plan->name,
            'price'          => (int) $plan->price_monthly,
            'duration_label' => $plan->description ?? $plan->name,
            'per_month'      => (int) $plan->price_monthly,
            'saving'         => null,
            'current'        => $currentSubscription?->plan_id === $plan->id,
            'popular'        => (bool) $plan->is_popular,
            'features'       => $plan->features ?? [],
            'plan_id'        => $plan->id,
        ]);

        $billingHistory = TenantPayment::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->with('subscription.plan')
            ->latest('paid_at')
            ->take(20)
            ->get()
            ->map(fn ($payment) => [
                'id'          => $payment->invoice_number,
                'date'        => $payment->paid_at?->toDateString(),
                'amount'      => (int) $payment->amount,
                'plan'        => $payment->subscription?->plan?->name ?? '—',
                'expires'     => $payment->subscription?->current_period_end?->toDateString(),
                'status'      => 'paid',
                'invoice_url' => '#',
            ]);

        return Inertia::render('School/Dashboard/Settings/Index', [
            'tenant'              => $tenant,
            'academicLevels'      => $academicLevels,
            'paystackConfig'      => $paymentConfig ? [
                'public_key'     => $paymentConfig->public_key ?? '',
                'bank_name'      => $paymentConfig->bank_name ?? '',
                'account_number' => $paymentConfig->account_number ?? '',
                'account_name'   => $paymentConfig->account_name ?? '',
            ] : null,
            'currentSubscription' => $currentSubscription ? [
                'name'       => $currentSubscription->plan?->name,
                'price'      => (int) $currentSubscription->amount,
                'expires_at' => $currentSubscription->current_period_end?->toDateString(),
                'status'     => $currentSubscription->status,
            ] : null,
            'plans'          => $plans,
            'notifPrefs'     => $notifPrefs,
            'billingHistory' => $billingHistory,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'tagline'         => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'whatsapp'        => 'nullable|string|max:20',
            'website'         => 'nullable|url',
            'address'         => 'nullable|string|max:255',
            'timezone'        => 'nullable|string',
            'primary_color'   => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'enrollment_mode' => 'nullable|in:automatic,manual',
        ]);

        $tenant->update($validated);

        return redirect()->back()->with('success', 'School profile updated successfully');
    }

    public function updatePaystack(Request $request)
    {
        $validated = $request->validate([
            'public_key'     => 'nullable|string',
            'secret_key'     => 'nullable|string',
            'bank_name'      => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'account_name'   => 'nullable|string|max:255',
        ]);

        $tenantId = session('active_tenant_id');
        $data     = array_filter($validated, fn ($v) => $v !== null && $v !== '');

        $this->paymentConfigRepo->upsert($tenantId, $data);

        return redirect()->back()->with('success', 'Paystack settings updated');
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_new_enrollment'   => 'boolean',
            'email_payment_received' => 'boolean',
            'email_batch_complete'   => 'boolean',
            'email_complaint'        => 'boolean',
            'email_weekly_summary'   => 'boolean',
            'sms_new_enrollment'     => 'boolean',
            'sms_payment_received'   => 'boolean',
            'sms_complaint'          => 'boolean',
        ]);

        $this->notifPrefRepo->upsert(session('active_tenant_id'), $validated);

        return redirect()->back()->with('success', 'Notification preferences saved');
    }
}