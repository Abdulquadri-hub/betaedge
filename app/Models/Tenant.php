<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            if(empty($tenant->slug)) {
                $tenant->slug = self::generateUniqueSlug($tenant->name);
            }

            if(empty($tenant->subdomain)) {
                $appUrl = config("app.url", 'https://betaedge.test');
                $parsedDomain = parse_url($appUrl, PHP_URL_HOST);

                $tenant->subdomain = $tenant->slug . '.' . $parsedDomain;
            }
        });
         
    }

    public function owner(): BelongsTo 
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany {
        return $this->hasMany(TenantUser::class);
    }

    public function subscription(): BelongsTo {
        return $this->belongsTo(TenantSubscription::class, 'id', 'tenant_id');
    }

    public function subscriptionHistory(): HasMany {
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

    public function files(): HasMany {
        return $this->hasMany(TenantFile::class, 'tenant_id');
    }

    public function onboardingProcess(): HasOne {
        return $this->hasOne(onboardingProcess::class, 'tenant_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query) {
        return $query->where('is_verified', 'true');
    }

    public function scopeOnTrial($query) {
        return $query->where('trial_ends_at', '>', now())
                    ->whereNull('deleted_at');
    }

    public function getFullDomainAttribute(): string {
        return $this->custom_domain ?? $this->subdomain;
    }

    public function hasCustomDomain(): bool {
        return !empty($this->custom_domain);
    }

    public function isOnTrial(): bool {
        return $this->trial_ends_at && 
        $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int {
        if (!$this->trial_ends_at) return 0;
        
        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    public function getCurrentSubscription(): ?TenantSubscription {
        return TenantSubscription::where('tenant_id', $this->id)
            ->where('status', 'active')
            ->first();
    }

    public function hasReachedLimit(string $limitType): bool {
        $subscription = $this->getCurrentSubscription();

        if(!$subscription) return true;

        return match ($limitType) {
            'students' => $this->students()->count() >= $subscription->plan->max_students,
            'instructor'=> $this->instructors()->count() >= $subscription->plan->max_instructors,
            'courses' => $this->courses()->count() >= $subscription->plan->max_courses,
            'storage' => $this->calculateStorageUsage() >= $subscription->plan->storage_gb * 1024 * 1024 * 1024,
            default => false
        };
    }

    public function  getUsageStats(): array {

        $subscription = $this->getCurrentSubscription();

        return [
            'students' => [
                'current' => $this->students()->count(),
                'limit' => $subscription?->plan->students,
                'percentage' => $this->calculateUsagePercentage('students')
            ],
            'instructors' => [
                'current' => $this->instructors()->count(),
                'limit' => $subscription?->plan->instructors,
                'percentage' => $this->calculateUsagePercentage('instructors')
            ],
            'courses' => [
                'current' => $this->courses()->count(),
                'limit' => $subscription?->plan->courses,
                'percentage' => $this->calculateUsagePercentage('courses')
            ],
            'storage' => [
                'current' => $this->calculateStorageUsage(),
                'limit' => ($subscription?->plan->storage_gb ?? 0) * 1024 * 1024 * 1024,
                'percentage' => $this->calculateUsagePercentage('storage')
            ]
        ];

    }

    protected  function calculateUsagePercentage(string $type): float {

        $subscription = $this->getCurrentSubscription();

        if(!$subscription) return 0;

        $current = match ($type) {
            'students' => $this->students()->count(),
            'instructors' => $this->instructors()->count(),
            'courses' => $this->courses()->count(),
            'storage' => $this->calculateStorageUsage(),
            default => 0,
        };

        $limit = match($type) {
            'students' => $subscription?->plan->students,
            'instructors' => $subscription?->plan->instructors,
            'courses' => $subscription?->plan->courses,
            'storage' => $subscription->plan->storage_gb * 1024 * 1024 * 1024,
            default => 1
        };

        if($limit === 0) return 0;

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

    private function calculateStorgeUsage(): int {
        return $this->files()->sum('size');
    }

    private static function generateUniqueSlug(string $name) {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

}
