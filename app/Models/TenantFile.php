<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFile extends Model
{
    protected $fillable = [
        'path',
        'size',
        'tenant_id',
        'type'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
}
