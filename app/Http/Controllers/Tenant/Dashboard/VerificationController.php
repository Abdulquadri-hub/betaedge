<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Contracts\Services\Verification\NinVerificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        protected NinVerificationServiceInterface $verificationService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $verificationStatus = $this->verificationService->getStatus(
            $user,
            session('active_tenant_id')
        );

        return Inertia::render('School/Dashboard/Verification/Index', [
            'verificationStatus' => $verificationStatus,
            'user' => $user,
            'isRequired' => in_array($user->user_type, ['school_owner', 'instructor']),
        ]);
    }

    public function submit(Request $request): Response
    {
        $user = auth()->user();
        $tenantId = session('active_tenant_id');

        $validated = $request->validate([
            'id_type' => 'required|in:nin,license,passport',
            'id_number' => 'required|string|min:8|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
        ]);

        $result = $this->verificationService->submitVerification(
            $user,
            $tenantId,
            $validated
        );

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'id_number' => $result['message'],
            ]);
        }

        $verificationStatus = $this->verificationService->getStatus($user, $tenantId);

        return Inertia::render('School/Dashboard/Verification/Index', [
            'verificationStatus' => $verificationStatus,
            'user' => $user,
            'isRequired' => in_array($user->user_type, ['school_owner', 'instructor']),
            'success' => $result['message'],
        ]);
    }
}
