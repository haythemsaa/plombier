<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Health",
 *     description="Health check and system status endpoints"
 * )
 */
class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     tags={"Health"},
     *     summary="Basic health check",
     *     description="Returns a simple OK response if the application is running",
     *     @OA\Response(
     *         response=200,
     *         description="Application is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/health/detailed",
     *     tags={"Health"},
     *     summary="Detailed health check",
     *     description="Returns detailed status of all system components",
     *     @OA\Response(
     *         response=200,
     *         description="Detailed health status",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="version", type="string", example="1.3.0"),
     *             @OA\Property(property="environment", type="string", example="production"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(
     *                     property="database",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="response_time_ms", type="number")
     *                 ),
     *                 @OA\Property(
     *                     property="cache",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="response_time_ms", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Service unavailable - one or more checks failed"
     *     )
     * )
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        $response = [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.3.0'),
            'environment' => app()->environment(),
            'checks' => $checks,
        ];

        return response()->json($response, $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity and performance.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
                'connection' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache (Redis) connectivity and performance.
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                throw new \Exception('Cache read/write verification failed');
            }

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache connection failed',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('logs');
            $isWritable = is_writable($path);

            return [
                'status' => $isWritable ? 'ok' : 'error',
                'writable' => $isWritable,
                'path' => $path,
                'free_space_gb' => round(disk_free_space($path) / 1024 / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ];
        }
    }

    /**
     * @OA\Get(
     *     path="/api/ping",
     *     tags={"Health"},
     *     summary="Simple ping endpoint",
     *     description="Returns pong - useful for uptime monitoring",
     *     @OA\Response(
     *         response=200,
     *         description="Pong response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="pong")
     *         )
     *     )
     * )
     */
    public function ping(): JsonResponse
    {
        return response()->json(['message' => 'pong']);
    }
}
