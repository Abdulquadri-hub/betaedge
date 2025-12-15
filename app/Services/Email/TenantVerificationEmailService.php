<?php

namespace App\Services\Email;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\VerifyEmailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantVerificationEmailService {

    public function send(Tenant $tenant) {
        
        if ($tenant->isEmailVerified()) {
            return;
        }

        $token = $tenant->generateVerificationToken();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['token' => $token]
        );

        $tenant->owner->notify(new VerifyEmailNotification($url));

        Log::info("Verification email sent", [
            'tenant_id' => $tenant->id,
            'email' => $tenant->owner_email,
        ]);
    }
}
