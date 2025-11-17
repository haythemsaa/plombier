<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Monitoring",
 *     description="Performance and system monitoring endpoints"
 * )
 */
class MonitoringController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @OA\Get(
     *     path="/api/monitoring/stats",
     *     tags={"Monitoring"},
     *     summary="Get system statistics",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="System statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="database", type="object"),
     *             @OA\Property(property="cache", type="object"),
     *             @OA\Property(property="application", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function stats(): JsonResponse
    {
        // Only allow admins to access monitoring
        if (!auth()->user() || auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'database' => $this->getDatabaseStats(),
            'cache' => $this->cacheService->getStats(),
            'application' => $this->getApplicationStats(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/monitoring/performance",
     *     tags={"Monitoring"},
     *     summary="Get performance metrics",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics",
     *         @OA\JsonContent(
     *             @OA\Property(property="response_times", type="object"),
     *             @OA\Property(property="throughput", type="object"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function performance(): JsonResponse
    {
        if (!auth()->user() || auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'response_times' => $this->getResponseTimes(),
            'throughput' => $this->getThroughput(),
            'errors' => $this->getErrorStats(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/monitoring/cache/clear",
     *     tags={"Monitoring"},
     *     summary="Clear application cache",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cache cleared successfully"
     *     )
     * )
     */
    public function clearCache(): JsonResponse
    {
        if (!auth()->user() || auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $this->cacheService->invalidateAll();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Get database statistics.
     */
    protected function getDatabaseStats(): array
    {
        try {
            return [
                'total_users' => DB::table('users')->count(),
                'total_providers' => DB::table('users')->where('type', 'provider')->count(),
                'total_clients' => DB::table('users')->where('type', 'client')->count(),
                'total_bookings' => DB::table('bookings')->count(),
                'total_services' => DB::table('services')->count(),
                'total_reviews' => DB::table('reviews')->count(),
                'pending_bookings' => DB::table('bookings')->where('status', 'pending')->count(),
                'completed_bookings' => DB::table('bookings')->where('status', 'completed')->count(),
                'total_revenue' => DB::table('bookings')
                    ->where('status', 'completed')
                    ->sum('total_price'),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to fetch database stats'];
        }
    }

    /**
     * Get application statistics.
     */
    protected function getApplicationStats(): array
    {
        return [
            'version' => config('app.version', '1.3.0'),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'uptime' => $this->getUptime(),
            'memory_usage' => [
                'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ],
        ];
    }

    /**
     * Get response time metrics.
     */
    protected function getResponseTimes(): array
    {
        try {
            // Get average response times from Redis (stored by middleware)
            $avgApiResponse = Cache::get('metrics:avg_response_time', 0);
            $avgDbQuery = Cache::get('metrics:avg_db_query_time', 0);

            return [
                'api_average_ms' => round($avgApiResponse, 2),
                'database_average_ms' => round($avgDbQuery, 2),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get throughput metrics.
     */
    protected function getThroughput(): array
    {
        try {
            return [
                'requests_per_minute' => Cache::get('metrics:requests_per_minute', 0),
                'requests_last_hour' => Cache::get('metrics:requests_last_hour', 0),
                'requests_today' => Cache::get('metrics:requests_today', 0),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get error statistics.
     */
    protected function getErrorStats(): array
    {
        try {
            return [
                'total_errors_today' => Cache::get('metrics:errors_today', 0),
                'error_rate' => Cache::get('metrics:error_rate', 0),
                'last_error' => Cache::get('metrics:last_error', null),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get application uptime.
     */
    protected function getUptime(): string
    {
        try {
            $uptimeFile = storage_path('framework/uptime');

            if (!file_exists($uptimeFile)) {
                file_put_contents($uptimeFile, now()->timestamp);
            }

            $startTime = (int) file_get_contents($uptimeFile);
            $uptime = now()->timestamp - $startTime;

            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);

            return "{$days}d {$hours}h {$minutes}m";
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}
