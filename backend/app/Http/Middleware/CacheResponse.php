<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request and cache the response.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 3600): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Don't cache if user is authenticated (personalized content)
        if ($request->user()) {
            return $next($request);
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = $this->getCacheKey($request);

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);

            return response()
                ->json($cachedResponse['data'], $cachedResponse['status'])
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Get fresh response
        $response = $next($request);

        // Only cache successful JSON responses
        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $responseData = [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ];

            Cache::put($cacheKey, $responseData, now()->addSeconds($ttl));

            $response->header('X-Cache', 'MISS');
            $response->header('X-Cache-Key', $cacheKey);
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request.
     */
    protected function getCacheKey(Request $request): string
    {
        $url = $request->fullUrl();
        $query = $request->query();

        ksort($query);

        return 'api_cache:' . md5($url . serialize($query));
    }

    /**
     * Clear cache for a specific pattern.
     */
    public static function clearCache(string $pattern = '*'): void
    {
        $keys = Cache::get('api_cache:' . $pattern);

        if ($keys) {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }
}
