<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'logo',
        'banner_image',
        'featured_courses',
        'category',
        'tags',
        'location',
        'country',
        'rating',
        'total_reviews',
        'total_students',
        'is_featured',
        'is_active',
        'visibility',
        'featured_until',
    ];

    protected $casts = [
        'featured_courses' => 'array',
        'tags' => 'array',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_students' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'featured_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'listing_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(MarketplaceClick::class, 'listing_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where('featured_until', '>', now());
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    public function scopePopular($query)
    {
        return $query->orderBy('total_students', 'desc')
                    ->orderBy('rating', 'desc');
    }

    // Helper Methods
    public function updateStats(): void
    {
        $this->update([
            'total_students' => $this->tenant->students()->count(),
            'rating' => $this->reviews()->avg('rating') ?? 0,
            'total_reviews' => $this->reviews()->count(),
        ]);
    }

    public function incrementClicks(): void
    {
        $this->clicks()->create([
            'clicked_at' => now(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]);
    }

    public function isFeatured(): bool
    {
        return $this->is_featured && 
               $this->featured_until && 
               $this->featured_until->isFuture();
    }

    public function makeFeatured(int $days = 30): void
    {
        $this->update([
            'is_featured' => true,
            'featured_until' => now()->addDays($days),
        ]);
    }

    public function removeFeatured(): void
    {
        $this->update([
            'is_featured' => false,
            'featured_until' => null,
        ]);
    }
}
