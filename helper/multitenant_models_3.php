<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MarketplaceListing Model - Schools listed in central marketplace
 */
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


/**
 * MarketplaceReview Model - Student reviews of schools
 */
class MarketplaceReview extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'rating',
        'review',
        'is_verified',
        'is_published',
        'helpful_count',
        'reported_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'helpful_count' => 'integer',
        'reported_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper Methods
    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function report(): void
    {
        $this->increment('reported_count');
    }
}


/**
 * MarketplaceClick Model - Track clicks to tenant sites
 */
class MarketplaceClick extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'clicked_at',
        'user_agent',
        'ip_address',
        'referrer',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relationships
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


/**
 * TenantAnalytics Model - Daily analytics snapshot
 */
class TenantAnalytics extends Model
{
    protected $fillable = [
        'tenant_id',
        'date',
        'active_students',
        'active_instructors',
        'total_enrollments',
        'new_enrollments',
        'completed_courses',
        'revenue',
        'class_sessions_held',
        'average_attendance_rate',
        'storage_used_bytes',
    ];

    protected $casts = [
        'date' => 'date',
        'active_students' => 'integer',
        'active_instructors' => 'integer',
        'total_enrollments' => 'integer',
        'new_enrollments' => 'integer',
        'completed_courses' => 'integer',
        'revenue' => 'decimal:2',
        'class_sessions_held' => 'integer',
        'average_attendance_rate' => 'decimal:2',
        'storage_used_bytes' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function captureSnapshot(Tenant $tenant): void
    {
        $date = today();

        self::updateOrCreate(
            ['tenant_id' => $tenant->id, 'date' => $date],
            [
                'active_students' => $tenant->students()->where('enrollment_status', 'active')->count(),
                'active_instructors' => $tenant->instructors()->where('status', 'active')->count(),
                'total_enrollments' => Enrollment::where('tenant_id', $tenant->id)->count(),
                'new_enrollments' => Enrollment::where('tenant_id', $tenant->id)
                    ->whereDate('enrolled_at', $date)
                    ->count(),
                'completed_courses' => Enrollment::where('tenant_id', $tenant->id)
                    ->where('status', 'completed')
                    ->count(),
                'class_sessions_held' => ClassSession::where('tenant_id', $tenant->id)
                    ->whereDate('started_at', $date)
                    ->where('status', 'completed')
                    ->count(),
                'average_attendance_rate' => self::calculateAttendanceRate($tenant, $date),
                'storage_used_bytes' => $tenant->calculateStorageUsage(),
            ]
        );
    }

    protected static function calculateAttendanceRate(Tenant $tenant, $date): float
    {
        $sessions = ClassSession::where('tenant_id', $tenant->id)
            ->whereDate('started_at', $date)
            ->where('status', 'completed')
            ->get();

        if ($sessions->isEmpty()) return 0;

        $totalRate = 0;
        foreach ($sessions as $session) {
            $enrolled = $session->course->activeEnrollments()->count();
            if ($enrolled > 0) {
                $attended = $session->attendances()->where('status', 'present')->count();
                $totalRate += ($attended / $enrolled) * 100;
            }
        }

        return round($totalRate / $sessions->count(), 2);
    }
}


/**
 * PlatformRevenue Model - Track platform earnings
 */
class PlatformRevenue extends Model
{
    protected $fillable = [
        'tenant_id',
        'payment_id',
        'enrollment_id',
        'gross_amount',
        'platform_fee',
        'platform_percentage',
        'net_amount',
        'currency',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year);
    }

    // Helper Methods
    public static function calculatePlatformFee(float $amount): float
    {
        $percentage = config('platform.fee_percentage', 5); // Default 5%
        return round(($amount * $percentage) / 100, 2);
    }

    public function getFormattedFeeAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->platform_fee, 2);
    }
}
