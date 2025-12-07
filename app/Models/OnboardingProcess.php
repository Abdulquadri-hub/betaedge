<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProcess extends Model
{
    protected $fillable = [
        'profile',
        'tenant_id',
        'plan',
        'payment',
        'status'
    ];

    protected $casts = [
        'profile' => 'array',
        'plan' => 'array',
        'payment' => 'array',
    ];

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

    public function updateStatus(Tenant $tenant, string $status): void {
        $this->update([
            'tenant_id' => $tenant->id,
            'status' => $status
        ]);
    }

    public function getProfile(): ?array {

        if(empty($this->profile)) return [];
        
        return json_decode($this->profile, true);
    }

    public function getPlan(): array {
        if(empty($this->plan)) return [];
        
        return json_decode($this->plan, true);
    }

    public function getPayment(): array {
        if(empty($this->payment)) return [];
        
        return json_decode($this->payment, true);
    }

}
