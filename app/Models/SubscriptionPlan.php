<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
     protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_students',
        'max_instructors',
        'max_courses',
        'storage_gb',
        'features',
        'has_custom_domain',
        'has_analytics',
        'has_api_access',
        'sort_order',
        'is_active',
        'is_popular',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_students' => 'integer',
        'max_instructors' => 'integer',
        'max_courses' => 'integer',
        'storage_gb' => 'integer',
        'features' => 'array',
        'has_custom_domain' => 'boolean',
        'has_analytics' => 'boolean',
        'has_api_access' => 'boolean',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public function subscriptions(): HasMany {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query) {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }

    public function getMonthlyPrice(): float
    {
        return $this->price_monthly;
    }

    public function getYearlyPrice(): float
    {
        return $this->price_yearly;
    }

    public function getYearlySavings(): float
    {
        return ($this->price_monthly * 12) - $this->price_yearly;
    }

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function hasFeature(string $feature): bool {
        return in_array($feature, $this->features ?? []);
    }

    public function getFormattedMonthlyPriceAttribute(): string {
        return $this->currency . ' ' . number_format($this->price_monthly, 2);
    }

    public function getFormattedYearlyPriceAttribute(): string {
        return $this->currency . ' ' . number_format($this->price_yearly, 2);
    }

}
