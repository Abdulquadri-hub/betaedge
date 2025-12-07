<?php

// ============================================================================
// CONTRACTS (app/Contracts/Marketplace/)
// ============================================================================

namespace App\Contracts\Marketplace;

interface MarketplaceRepositoryInterface
{
    public function getAllListings(array $filters = []);
    public function getListingByTenant(int $tenantId);
    public function createListing(array $data);
    public function updateListing(int $id, array $data);
    public function getFeaturedListings(int $limit = 10);
    public function searchListings(string $query, array $filters = []);
}

interface MarketplaceReviewRepositoryInterface
{
    public function getListingReviews(int $listingId, int $perPage = 10);
    public function createReview(array $data);
    public function updateReview(int $id, array $data);
    public function deleteReview(int $id);
    public function getUserReview(int $listingId, int $userId);
}

interface MarketplaceAnalyticsRepositoryInterface
{
    public function recordClick(int $listingId, array $data);
    public function getClickStats(int $listingId, string $period = '30days');
    public function getPopularListings(int $limit = 10);
    public function getConversionRate(int $listingId);
}


// ============================================================================
// REPOSITORIES (app/Repositories/Marketplace/)
// ============================================================================

namespace App\Repositories\Marketplace;

use App\Models\{MarketplaceListing, MarketplaceReview, MarketplaceClick};
use App\Contracts\Marketplace\{MarketplaceRepositoryInterface, MarketplaceReviewRepositoryInterface, MarketplaceAnalyticsRepositoryInterface};
use Illuminate\Support\Facades\{Cache, DB};

class MarketplaceRepository implements MarketplaceRepositoryInterface
{
    public function getAllListings(array $filters = [])
    {
        $cacheKey = 'marketplace:listings:' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 1800, function () use ($filters) {
            $query = MarketplaceListing::query()
                ->with(['tenant', 'reviews'])
                ->where('is_active', true)
                ->where('visibility', 'public');

            // Apply filters
            if (!empty($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (!empty($filters['location'])) {
                $query->where('location', 'like', "%{$filters['location']}%");
            }

            if (!empty($filters['country'])) {
                $query->where('country', $filters['country']);
            }

            if (!empty($filters['tags'])) {
                $query->whereJsonContains('tags', $filters['tags']);
            }

            if (!empty($filters['min_rating'])) {
                $query->where('rating', '>=', $filters['min_rating']);
            }

            // Sorting
            $sort = $filters['sort'] ?? 'popular';
            match($sort) {
                'popular' => $query->orderBy('total_students', 'desc')->orderBy('rating', 'desc'),
                'rating' => $query->orderBy('rating', 'desc')->orderBy('total_reviews', 'desc'),
                'newest' => $query->latest(),
                'name' => $query->orderBy('title'),
                default => $query->orderBy('total_students', 'desc'),
            };

            return $query->paginate($filters['per_page'] ?? 12);
        });
    }

    public function getListingByTenant(int $tenantId)
    {
        return Cache::remember("marketplace:listing:tenant:{$tenantId}", 3600, function () use ($tenantId) {
            return MarketplaceListing::where('tenant_id', $tenantId)
                ->with(['tenant', 'reviews'])
                ->first();
        });
    }

    public function createListing(array $data)
    {
        return DB::transaction(function () use ($data) {
            $listing = MarketplaceListing::create($data);
            
            Cache::tags(['marketplace'])->flush();
            
            return $listing;
        });
    }

    public function updateListing(int $id, array $data)
    {
        $listing = MarketplaceListing::findOrFail($id);
        $listing->update($data);
        
        Cache::forget("marketplace:listing:{$id}");
        Cache::forget("marketplace:listing:tenant:{$listing->tenant_id}");
        Cache::tags(['marketplace'])->flush();
        
        return $listing->fresh();
    }

    public function getFeaturedListings(int $limit = 10)
    {
        return Cache::remember("marketplace:featured:{$limit}", 3600, function () use ($limit) {
            return MarketplaceListing::active()
                ->featured()
                ->with(['tenant'])
                ->orderBy('total_students', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function searchListings(string $query, array $filters = [])
    {
        $cacheKey = "marketplace:search:" . md5($query . serialize($filters));
        
        return Cache::remember($cacheKey, 900, function () use ($query, $filters) {
            return MarketplaceListing::query()
                ->where('is_active', true)
                ->where('visibility', 'public')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhereJsonContains('tags', $query);
                })
                ->when(!empty($filters['category']), fn($q) => $q->where('category', $filters['category']))
                ->when(!empty($filters['location']), fn($q) => $q->where('location', 'like', "%{$filters['location']}%"))
                ->with(['tenant', 'reviews'])
                ->orderBy('rating', 'desc')
                ->paginate(12);
        });
    }
}

class MarketplaceReviewRepository implements MarketplaceReviewRepositoryInterface
{
    public function getListingReviews(int $listingId, int $perPage = 10)
    {
        return Cache::remember("marketplace:reviews:{$listingId}:page:{$perPage}", 600, function () use ($listingId, $perPage) {
            return MarketplaceReview::where('listing_id', $listingId)
                ->published()
                ->with('user:id,first_name,last_name,avatar')
                ->latest()
                ->paginate($perPage);
        });
    }

    public function createReview(array $data)
    {
        return DB::transaction(function () use ($data) {
            $review = MarketplaceReview::create($data);
            
            // Update listing stats
            $this->updateListingStats($data['listing_id']);
            
            Cache::forget("marketplace:reviews:{$data['listing_id']}:page:10");
            Cache::forget("marketplace:listing:{$data['listing_id']}");
            
            return $review;
        });
    }

    public function updateReview(int $id, array $data)
    {
        $review = MarketplaceReview::findOrFail($id);
        $listingId = $review->listing_id;
        
        $review->update($data);
        
        $this->updateListingStats($listingId);
        Cache::forget("marketplace:reviews:{$listingId}:page:10");
        
        return $review->fresh();
    }

    public function deleteReview(int $id)
    {
        $review = MarketplaceReview::findOrFail($id);
        $listingId = $review->listing_id;
        
        $review->delete();
        
        $this->updateListingStats($listingId);
        Cache::forget("marketplace:reviews:{$listingId}:page:10");
    }

    public function getUserReview(int $listingId, int $userId)
    {
        return MarketplaceReview::where('listing_id', $listingId)
            ->where('user_id', $userId)
            ->first();
    }

    private function updateListingStats(int $listingId): void
    {
        $listing = MarketplaceListing::findOrFail($listingId);
        
        $listing->update([
            'rating' => $listing->reviews()->published()->avg('rating') ?? 0,
            'total_reviews' => $listing->reviews()->published()->count(),
        ]);
    }
}

class MarketplaceAnalyticsRepository implements MarketplaceAnalyticsRepositoryInterface
{
    public function recordClick(int $listingId, array $data)
    {
        MarketplaceClick::create([
            'listing_id' => $listingId,
            'user_id' => $data['user_id'] ?? null,
            'clicked_at' => now(),
            'user_agent' => $data['user_agent'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'referrer' => $data['referrer'] ?? null,
        ]);
        
        // Clear cache
        Cache::forget("marketplace:clicks:{$listingId}");
    }

    public function getClickStats(int $listingId, string $period = '30days')
    {
        return Cache::remember("marketplace:clicks:{$listingId}:{$period}", 3600, function () use ($listingId, $period) {
            $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 30;
            
            return MarketplaceClick::where('listing_id', $listingId)
                ->where('clicked_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(clicked_at) as date, COUNT(*) as clicks')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    public function getPopularListings(int $limit = 10)
    {
        return Cache::remember("marketplace:popular:{$limit}", 1800, function () use ($limit) {
            return MarketplaceListing::query()
                ->withCount(['clicks' => fn($q) => $q->where('clicked_at', '>=', now()->subDays(7))])
                ->orderBy('clicks_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function getConversionRate(int $listingId)
    {
        return Cache::remember("marketplace:conversion:{$listingId}", 3600, function () use ($listingId) {
            $listing = MarketplaceListing::findOrFail($listingId);
            
            $clicks = MarketplaceClick::where('listing_id', $listingId)
                ->where('clicked_at', '>=', now()->subDays(30))
                ->count();
            
            $enrollments = $listing->tenant->students()
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            
            return [
                'clicks' => $clicks,
                'enrollments' => $enrollments,
                'rate' => $clicks > 0 ? round(($enrollments / $clicks) * 100, 2) : 0,
            ];
        });
    }
}


// ============================================================================
// SERVICES (app/Services/Marketplace/)
// ============================================================================

namespace App\Services\Marketplace;

use App\Contracts\Marketplace\{MarketplaceRepositoryInterface, MarketplaceReviewRepositoryInterface, MarketplaceAnalyticsRepositoryInterface};
use App\Events\Marketplace\{ListingCreated, ListingViewed, ReviewSubmitted};
use Illuminate\Support\Facades\{DB, Storage, Cache};
use Illuminate\Http\UploadedFile;

class MarketplaceService
{
    public function __construct(
        private MarketplaceRepositoryInterface $marketplaceRepo,
        private MarketplaceReviewRepositoryInterface $reviewRepo,
        private MarketplaceAnalyticsRepositoryInterface $analyticsRepo
    ) {}

    public function getMarketplaceListings(array $filters = [])
    {
        return $this->marketplaceRepo->getAllListings($filters);
    }

    public function searchSchools(string $query, array $filters = [])
    {
        return $this->marketplaceRepo->searchListings($query, $filters);
    }

    public function getSchoolDetails(int $listingId, ?int $userId = null)
    {
        $cacheKey = "marketplace:details:{$listingId}";
        
        $listing = Cache::remember($cacheKey, 600, function () use ($listingId) {
            return MarketplaceListing::with([
                'tenant',
                'reviews' => fn($q) => $q->published()->latest()->limit(5),
                'reviews.user:id,first_name,last_name,avatar'
            ])->findOrFail($listingId);
        });

        // Record view (async via event)
        event(new ListingViewed($listing, $userId));

        return [
            'listing' => $listing,
            'stats' => $this->getListingStats($listingId),
            'user_review' => $userId ? $this->reviewRepo->getUserReview($listingId, $userId) : null,
        ];
    }

    public function createOrUpdateListing(int $tenantId, array $data)
    {
        $listing = $this->marketplaceRepo->getListingByTenant($tenantId);

        if ($listing) {
            return $this->marketplaceRepo->updateListing($listing->id, $data);
        }

        $listingData = array_merge($data, ['tenant_id' => $tenantId]);
        $listing = $this->marketplaceRepo->createListing($listingData);

        event(new ListingCreated($listing));

        return $listing;
    }

    public function uploadListingImage(int $listingId, UploadedFile $file, string $type = 'logo')
    {
        $listing = MarketplaceListing::findOrFail($listingId);
        
        $path = $file->store("marketplace/{$listing->tenant_id}/{$type}", 'public');
        
        // Delete old image
        if ($type === 'logo' && $listing->logo) {
            Storage::disk('public')->delete($listing->logo);
        } elseif ($type === 'banner' && $listing->banner_image) {
            Storage::disk('public')->delete($listing->banner_image);
        }
        
        $field = $type === 'logo' ? 'logo' : 'banner_image';
        $listing->update([$field => $path]);
        
        Cache::forget("marketplace:listing:{$listingId}");
        Cache::forget("marketplace:listing:tenant:{$listing->tenant_id}");
        
        return $path;
    }

    public function makeFeatured(int $listingId, int $days = 30)
    {
        $listing = MarketplaceListing::findOrFail($listingId);
        $listing->makeFeatured($days);
        
        Cache::tags(['marketplace', 'featured'])->flush();
        
        return $listing;
    }

    public function removeFeatured(int $listingId)
    {
        $listing = MarketplaceListing::findOrFail($listingId);
        $listing->removeFeatured();
        
        Cache::tags(['marketplace', 'featured'])->flush();
        
        return $listing;
    }

    private function getListingStats(int $listingId): array
    {
        return Cache::remember("marketplace:stats:{$listingId}", 3600, function () use ($listingId) {
            $listing = MarketplaceListing::findOrFail($listingId);
            
            return [
                'total_clicks' => $listing->clicks()->count(),
                'clicks_7days' => $listing->clicks()->where('clicked_at', '>=', now()->subDays(7))->count(),
                'conversion' => $this->analyticsRepo->getConversionRate($listingId),
            ];
        });
    }
}

class MarketplaceReviewService
{
    public function __construct(
        private MarketplaceReviewRepositoryInterface $reviewRepo
    ) {}

    public function getReviews(int $listingId, int $page = 1)
    {
        return $this->reviewRepo->getListingReviews($listingId, 10);
    }

    public function submitReview(int $listingId, int $userId, array $data)
    {
        // Check if user already reviewed
        $existing = $this->reviewRepo->getUserReview($listingId, $userId);
        
        if ($existing) {
            throw new \Exception('You have already reviewed this school');
        }

        // Verify user is/was student at this school
        $listing = MarketplaceListing::findOrFail($listingId);
        $isStudent = $listing->tenant->students()
            ->whereHas('user', fn($q) => $q->where('id', $userId))
            ->exists();

        $reviewData = [
            'listing_id' => $listingId,
            'user_id' => $userId,
            'rating' => $data['rating'],
            'review' => $data['review'] ?? null,
            'is_verified' => $isStudent,
            'is_published' => true,
        ];

        $review = $this->reviewRepo->createReview($reviewData);

        event(new ReviewSubmitted($review));

        return $review;
    }

    public function updateReview(int $reviewId, int $userId, array $data)
    {
        $review = MarketplaceReview::findOrFail($reviewId);
        
        if ($review->user_id !== $userId) {
            throw new \Exception('Unauthorized');
        }

        return $this->reviewRepo->updateReview($reviewId, $data);
    }

    public function deleteReview(int $reviewId, int $userId)
    {
        $review = MarketplaceReview::findOrFail($reviewId);
        
        if ($review->user_id !== $userId) {
            throw new \Exception('Unauthorized');
        }

        $this->reviewRepo->deleteReview($reviewId);
    }

    public function markHelpful(int $reviewId)
    {
        $review = MarketplaceReview::findOrFail($reviewId);
        $review->markHelpful();
        
        Cache::forget("marketplace:reviews:{$review->listing_id}:page:10");
    }

    public function reportReview(int $reviewId, int $userId, string $reason)
    {
        $review = MarketplaceReview::findOrFail($reviewId);
        $review->report();
        
        // Log report
        DB::table('review_reports')->insert([
            'review_id' => $reviewId,
            'reported_by' => $userId,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}

class MarketplaceAnalyticsService
{
    public function __construct(
        private MarketplaceAnalyticsRepositoryInterface $analyticsRepo
    ) {}

    public function recordClick(int $listingId, array $metadata = [])
    {
        $this->analyticsRepo->recordClick($listingId, [
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'referrer' => request()->header('referer'),
            ...$metadata
        ]);
    }

    public function getListingAnalytics(int $listingId, string $period = '30days')
    {
        return [
            'clicks' => $this->analyticsRepo->getClickStats($listingId, $period),
            'conversion' => $this->analyticsRepo->getConversionRate($listingId),
            'reviews_stats' => $this->getReviewStats($listingId),
            'traffic_sources' => $this->getTrafficSources($listingId, $period),
        ];
    }

    public function getMarketplaceOverview()
    {
        return Cache::remember('marketplace:overview', 3600, function () {
            return [
                'total_listings' => MarketplaceListing::active()->count(),
                'total_schools' => MarketplaceListing::active()->distinct('tenant_id')->count(),
                'popular_listings' => $this->analyticsRepo->getPopularListings(10),
                'recent_reviews' => MarketplaceReview::published()->latest()->limit(10)->get(),
            ];
        });
    }

    private function getReviewStats(int $listingId): array
    {
        return Cache::remember("marketplace:review_stats:{$listingId}", 3600, function () use ($listingId) {
            $reviews = MarketplaceReview::where('listing_id', $listingId)
                ->published()
                ->get();

            $ratingDistribution = $reviews->groupBy('rating')
                ->map(fn($group) => $group->count())
                ->toArray();

            return [
                'average' => $reviews->avg('rating'),
                'total' => $reviews->count(),
                'distribution' => $ratingDistribution,
            ];
        });
    }

    private function getTrafficSources(int $listingId, string $period): array
    {
        $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 30;
        
        return Cache::remember("marketplace:traffic:{$listingId}:{$period}", 3600, function () use ($listingId, $days) {
            return MarketplaceClick::where('listing_id', $listingId)
                ->where('clicked_at', '>=', now()->subDays($days))
                ->selectRaw("
                    CASE 
                        WHEN referrer IS NULL THEN 'Direct'
                        WHEN referrer LIKE '%google%' THEN 'Google'
                        WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                        WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                        ELSE 'Other'
                    END as source,
                    COUNT(*) as clicks
                ")
                ->groupBy('source')
                ->get();
        });
    }
}


// ============================================================================
// JOBS (app/Jobs/Marketplace/)
// ============================================================================

namespace App\Jobs\Marketplace;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use App\Models\MarketplaceListing;
use Illuminate\Support\Facades\Cache;

class UpdateListingStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $listingId) {}

    public function handle(): void
    {
        $listing = MarketplaceListing::findOrFail($this->listingId);
        
        $listing->updateStats();
        
        Cache::forget("marketplace:listing:{$this->listingId}");
        Cache::forget("marketplace:stats:{$this->listingId}");
    }
}

class ProcessListingAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $listingId, public string $date) {}

    public function handle(): void
    {
        // Aggregate daily analytics
        $clicks = \App\Models\MarketplaceClick::where('listing_id', $this->listingId)
            ->whereDate('clicked_at', $this->date)
            ->count();

        // Store in analytics table
        DB::table('marketplace_daily_analytics')->updateOrInsert(
            ['listing_id' => $this->listingId, 'date' => $this->date],
            ['clicks' => $clicks, 'updated_at' => now()]
        );
    }
}
