<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Model - Core of Multi-Tenancy
 * Each school/academy is a tenant with isolated data
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'subdomain',
        'owner_id',
        'school_type',
        'website',
        'year_established',
        'address',
        'city',
        'description',
        'logo',
        'primary_color',
        'secondary_color',
        'timezone',
        'country',
        'currency',
        'status',
        'is_verified',
        'trial_ends_at',
        'setup_completed',
        'onboarding_step',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'setup_completed' => 'boolean',
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = self::generateUniqueSlug($tenant->name);
            }
            
            if (empty($tenant->subdomain)) {
                $tenant->subdomain = $tenant->slug . '.teach.com';
            }
        });
    }

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'id', 'tenant_id');
    }

    public function subscriptionHistory(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function academicLevels(): HasMany
    {
        return $this->hasMany(AcademicLevel::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(Instructor::class);
    }

    public function domainVerifications(): HasMany
    {
        return $this->hasMany(DomainVerification::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeOnTrial($query)
    {
        return $query->where('trial_ends_at', '>', now())
                    ->whereNull('deleted_at');
    }

    // Helper Methods
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getFullDomainAttribute(): string
    {
        return $this->custom_domain ?? $this->subdomain;
    }

    public function hasCustomDomain(): bool
    {
        return !empty($this->custom_domain);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->trial_ends_at) return 0;
        
        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    public function getCurrentSubscription(): ?TenantSubscription
    {
        return TenantSubscription::where('tenant_id', $this->id)
            ->where('status', 'active')
            ->first();
    }

    public function hasReachedLimit(string $limitType): bool
    {
        $subscription = $this->getCurrentSubscription();
        
        if (!$subscription) return true;

        return match($limitType) {
            'students' => $this->students()->count() >= $subscription->plan->max_students,
            'instructors' => $this->instructors()->count() >= $subscription->plan->max_instructors,
            'courses' => $this->courses()->count() >= $subscription->plan->max_courses,
            'storage' => $this->calculateStorageUsage() >= $subscription->plan->storage_gb * 1024 * 1024 * 1024,
            default => false,
        };
    }

    public function calculateStorageUsage(): int
    {
        // Calculate total storage in bytes
        // This would sum up file sizes from materials, submissions, etc.
        return 0; // Implement based on your file storage strategy
    }

    public function getUsageStats(): array
    {
        $subscription = $this->getCurrentSubscription();
        
        return [
            'students' => [
                'current' => $this->students()->count(),
                'limit' => $subscription?->plan->max_students ?? 0,
                'percentage' => $this->calculateUsagePercentage('students'),
            ],
            'instructors' => [
                'current' => $this->instructors()->count(),
                'limit' => $subscription?->plan->max_instructors ?? 0,
                'percentage' => $this->calculateUsagePercentage('instructors'),
            ],
            'courses' => [
                'current' => $this->courses()->count(),
                'limit' => $subscription?->plan->max_courses ?? 0,
                'percentage' => $this->calculateUsagePercentage('courses'),
            ],
            'storage' => [
                'current' => $this->calculateStorageUsage(),
                'limit' => ($subscription?->plan->storage_gb ?? 0) * 1024 * 1024 * 1024,
                'percentage' => $this->calculateUsagePercentage('storage'),
            ],
        ];
    }

    protected function calculateUsagePercentage(string $type): float
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) return 0;

        $current = match($type) {
            'students' => $this->students()->count(),
            'instructors' => $this->instructors()->count(),
            'courses' => $this->courses()->count(),
            'storage' => $this->calculateStorageUsage(),
            default => 0,
        };

        $limit = match($type) {
            'students' => $subscription->plan->max_students,
            'instructors' => $subscription->plan->max_instructors,
            'courses' => $subscription->plan->max_courses,
            'storage' => $subscription->plan->storage_gb * 1024 * 1024 * 1024,
            default => 1,
        };

        if ($limit == 0) return 0;

        return round(($current / $limit) * 100, 2);
    }

    public function completeOnboarding(): void
    {
        $this->update([
            'setup_completed' => true,
            'onboarding_step' => 'completed',
        ]);
    }

    public function updateOnboardingStep(string $step): void
    {
        $this->update(['onboarding_step' => $step]);
    }
}


/**
 * TenantUser Model - Links users to tenants with roles
 * Allows instructors to work at multiple schools
 */
class TenantUser extends Model
{
    protected $table = 'tenant_users';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'permissions',
        'status',
        'joined_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOwners($query)
    {
        return $query->where('role', 'owner');
    }

    public function scopeInstructors($query)
    {
        return $query->where('role', 'instructor');
    }

    // Helper Methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function updateLastAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }
}


/**
 * SubscriptionPlan Model - Defines available subscription tiers
 */
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

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }

    // Helper Methods
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

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getFormattedMonthlyPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->price_monthly, 2);
    }

    public function getFormattedYearlyPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->price_yearly, 2);
    }
}
