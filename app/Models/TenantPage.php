<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPage extends Model
{
    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'content',
        'page_type',
        'is_active',
        'meta_title',
        'meta_description'
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    // Helper Methods
    public function isLanding(): bool
    {
        return $this->page_type === 'landing';
    }

    public function isRegistration(): bool
    {
        return in_array($this->page_type, ['register_student', 'register_parent']);
    }

    public function getUrl(): string
    {
        $domain = $this->tenant->full_domain;
        return $this->slug === '/' ? "https://{$domain}" : "https://{$domain}/{$this->slug}";
    }
}
