<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Contracts\Repositories\School\KycSubmissionRepositoryInterface;
use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function __construct(
        protected KycSubmissionRepositoryInterface $kycSubmissionRepository,
        protected TenantRepositoryInterface $tenantRepository
    ) {}

    public function index(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $tenant   = $this->tenantRepository->getById($tenantId);

        if (! $tenant) {
            abort(404);
        }

        $submission = $this->kycSubmissionRepository->latestForTenant($tenantId);

        return Inertia::render('School/Dashboard/Verification/Index', [
            'tenant'     => [
                'name'                => $tenant->name,
                'is_verified'         => $tenant->is_verified,
                'verification_status' => $tenant->verification_status ?? 'unverified',
            ],
            'submission' => $submission ? [
                'id'               => $submission->id,
                'id_type'          => $submission->id_type,
                'status'           => $submission->status,
                'submitted_at'     => $submission->submitted_at?->format('M j, Y'),
                'reviewed_at'      => $submission->reviewed_at?->format('M j, Y'),
                'rejection_reason' => $submission->rejection_reason,
            ] : null,
        ]);
    }

    public function submit(Request $request)
    {
        $tenantId = (int) session('active_tenant_id');
        $tenant   = $this->tenantRepository->getById($tenantId);
        $user = $request->user();

        if (! $tenant) {
            abort(404);
        }

        if ($tenant->is_verified && $tenant->verification_status != 'unverified') {
            return redirect()->back()->with('success', 'Your school is already verified.');
        }

        $existing = $this->kycSubmissionRepository->pendingForTenant($tenantId);

        if ($existing) {
            return redirect()->back()
                ->withErrors(['message' => 'You already have a pending verification. Please wait for review.']);
        }

        $idType = $request->input('id_type');

        // Validate per ID type
        $rules = ['id_type' => 'required|in:nin,bvn,passport,drivers_license,cac,voters_card'];

        switch ($idType) {
            case 'nin':
                $rules['id_number']  = 'required|string|digits:11';
                $rules['first_name'] = 'required|string|max:100';
                $rules['last_name']  = 'required|string|max:100';
                break;

            case 'bvn':
                $rules['id_number']  = 'required|string|digits:11';
                $rules['first_name'] = 'required|string|max:100';
                $rules['last_name']  = 'required|string|max:100';
                $rules['date_of_birth'] = 'required|date';
                break;

            case 'passport':
                $rules['passport_number'] = 'required|string|max:20';
                $rules['first_name']      = 'required|string|max:100';
                $rules['last_name']       = 'required|string|max:100';
                $rules['expiry_date']     = 'required|date|after:today';
                break;

            case 'drivers_license':
                $rules['id_number']     = 'required|string|max:20';
                $rules['first_name']    = 'required|string|max:100';
                $rules['last_name']     = 'required|string|max:100';
                $rules['date_of_birth'] = 'required|date';
                break;

            case 'cac':
                $rules['rc_number']     = 'required|string|max:20';
                $rules['business_name'] = 'required|string|max:255';
                break;

            case 'voters_card':
                $rules['id_number']  = 'required|string|max:20';
                $rules['first_name'] = 'required|string|max:100';
                $rules['last_name']  = 'required|string|max:100';
                break;
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $tenantId, $user, $tenant) {
            $this->kycSubmissionRepository->deleteForTenant($tenantId);

            $this->kycSubmissionRepository->createForTenant($tenantId, array_merge($validated, [
                'submitted_by' => $user->id,
            ]));

            $this->tenantRepository->updateVerificationStatus($tenant, 'pending');
        });

        return redirect()->back()->with(
            'success',
            'Verification submitted! We will review your documents within 24 hours.'
        );
    }
}