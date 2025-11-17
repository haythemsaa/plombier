<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 1800;    // 30 minutes
    const TTL_LONG = 3600;      // 1 hour
    const TTL_VERY_LONG = 86400; // 24 hours

    /**
     * Cache key prefixes
     */
    const PREFIX_SERVICES = 'services:';
    const PREFIX_PROVIDERS = 'providers:';
    const PREFIX_BOOKINGS = 'bookings:';
    const PREFIX_REVIEWS = 'reviews:';
    const PREFIX_CATEGORIES = 'categories:';
    const PREFIX_STATS = 'stats:';

    /**
     * Get or set cache with callback.
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback: execute callback without caching
            return $callback();
        }
    }

    /**
     * Get cached services list.
     */
    public function getServices(array $filters = [])
    {
        $key = self::PREFIX_SERVICES . 'list:' . md5(serialize($filters));

        return $this->remember($key, self::TTL_MEDIUM, function () use ($filters) {
            return \App\Models\Service::query()
                ->when(isset($filters['category']), fn($q) => $q->where('category', $filters['category']))
                ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
                ->with('category')
                ->get();
        });
    }

    /**
     * Get cached service by ID.
     */
    public function getService(int $id)
    {
        $key = self::PREFIX_SERVICES . $id;

        return $this->remember($key, self::TTL_LONG, function () use ($id) {
            return \App\Models\Service::with(['category', 'providerServices.provider'])
                ->findOrFail($id);
        });
    }

    /**
     * Get cached provider by ID.
     */
    public function getProvider(int $id)
    {
        $key = self::PREFIX_PROVIDERS . $id;

        return $this->remember($key, self::TTL_MEDIUM, function () use ($id) {
            return \App\Models\User::where('type', 'provider')
                ->with(['providerServices.service', 'reviews'])
                ->findOrFail($id);
        });
    }

    /**
     * Get cached provider statistics.
     */
    public function getProviderStats(int $providerId)
    {
        $key = self::PREFIX_STATS . "provider:$providerId";

        return $this->remember($key, self::TTL_SHORT, function () use ($providerId) {
            $provider = \App\Models\User::findOrFail($providerId);

            return [
                'total_bookings' => $provider->providerBookings()->count(),
                'completed_bookings' => $provider->providerBookings()->where('status', 'completed')->count(),
                'total_revenue' => $provider->providerBookings()
                    ->where('status', 'completed')
                    ->sum('total_price'),
                'average_rating' => $provider->reviews()->avg('overall_rating'),
                'total_reviews' => $provider->reviews()->count(),
            ];
        });
    }

    /**
     * Get cached service categories.
     */
    public function getCategories()
    {
        $key = self::PREFIX_CATEGORIES . 'all';

        return $this->remember($key, self::TTL_VERY_LONG, function () {
            return \App\Models\ServiceCategory::with('services')->get();
        });
    }

    /**
     * Get cached featured reviews.
     */
    public function getFeaturedReviews(int $limit = 10)
    {
        $key = self::PREFIX_REVIEWS . "featured:$limit";

        return $this->remember($key, self::TTL_LONG, function () use ($limit) {
            return \App\Models\Review::with(['booking.client', 'booking.providerService.provider'])
                ->where('overall_rating', '>=', 4)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Invalidate service cache.
     */
    public function invalidateService(int $id): void
    {
        Cache::forget(self::PREFIX_SERVICES . $id);
        $this->invalidateServicesList();
    }

    /**
     * Invalidate services list cache.
     */
    public function invalidateServicesList(): void
    {
        Cache::tags([self::PREFIX_SERVICES . 'list'])->flush();
    }

    /**
     * Invalidate provider cache.
     */
    public function invalidateProvider(int $id): void
    {
        Cache::forget(self::PREFIX_PROVIDERS . $id);
        Cache::forget(self::PREFIX_STATS . "provider:$id");
    }

    /**
     * Invalidate categories cache.
     */
    public function invalidateCategories(): void
    {
        Cache::forget(self::PREFIX_CATEGORIES . 'all');
    }

    /**
     * Invalidate reviews cache.
     */
    public function invalidateReviews(): void
    {
        Cache::tags([self::PREFIX_REVIEWS])->flush();
    }

    /**
     * Invalidate all cache.
     */
    public function invalidateAll(): void
    {
        Cache::flush();
        Log::info('All cache invalidated');
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();

            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
                'total_keys' => $redis->dbSize(),
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate cache hit rate.
     */
    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
