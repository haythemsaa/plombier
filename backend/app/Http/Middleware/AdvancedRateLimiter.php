<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdvancedRateLimiter
{
    /**
     * Rate limit configurations per endpoint type
     */
    const LIMITS = [
        'auth' => ['max' => 5, 'decay' => 60], // 5 attempts per minute
        'api' => ['max' => 60, 'decay' => 60], // 60 requests per minute
        'heavy' => ['max' => 10, 'decay' => 60], // 10 requests per minute for heavy operations
        'public' => ['max' => 100, 'decay' => 60], // 100 requests per minute for public endpoints
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType = 'api'): Response
    {
        if (!isset(self::LIMITS[$limitType])) {
            $limitType = 'api';
        }

        $config = self::LIMITS[$limitType];
        $key = $this->resolveRequestSignature($request, $limitType);

        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($key, $config['max'])) {
            return $this->buildRateLimitResponse($key, $config);
        }

        // Increment the counter
        RateLimiter::hit($key, $config['decay']);

        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders(
            $response,
            $config['max'],
            RateLimiter::remaining($key, $config['max']),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $limitType): string
    {
        $user = $request->user();

        // Use user ID if authenticated, otherwise use IP
        $identifier = $user ? "user:{$user->id}" : "ip:{$request->ip()}";

        // Include endpoint path for granular limiting
        $path = $request->path();

        return "rate_limit:{$limitType}:{$identifier}:{$path}";
    }

    /**
     * Build rate limit exceeded response.
     */
    protected function buildRateLimitResponse(string $key, array $config): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'retry_after' => $retryAfter,
            'limit' => $config['max'],
            'window' => $config['decay'],
        ], 429)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', $config['max'])
        ->header('X-RateLimit-Remaining', 0)
        ->header('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts,
        int $retryAfter = null
    ): Response {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));

        if ($retryAfter !== null) {
            $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }

    /**
     * Get current rate limit status for a request.
     */
    public static function getStatus(Request $request, string $limitType = 'api'): array
    {
        $middleware = new self();
        $key = $middleware->resolveRequestSignature($request, $limitType);
        $config = self::LIMITS[$limitType] ?? self::LIMITS['api'];

        return [
            'limit' => $config['max'],
            'remaining' => RateLimiter::remaining($key, $config['max']),
            'retry_after' => RateLimiter::availableIn($key),
            'reset_at' => now()->addSeconds(RateLimiter::availableIn($key))->toIso8601String(),
        ];
    }

    /**
     * Clear rate limit for a specific key (admin function).
     */
    public static function clearLimit(Request $request, string $limitType = 'api'): void
    {
        $middleware = new self();
        $key = $middleware->resolveRequestSignature($request, $limitType);
        RateLimiter::clear($key);
    }
}
