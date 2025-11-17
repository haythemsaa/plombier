<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="Audit log endpoints (admin only)"
 * )
 */
class AuditController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/audit-logs",
     *     tags={"Audit"},
     *     summary="Get audit logs with filters",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Filter by action type",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="severity",
     *         in="query",
     *         description="Filter by severity",
     *         @OA\Schema(type="string", enum={"info", "warning", "critical"})
     *     ),
     *     @OA\Response(response=200, description="List of audit logs"),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Only admins can access audit logs
        if (!$request->user() || $request->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        AuditService::logAdminAccess('/audit-logs', $request->user(), $request);

        $query = AuditLog::with('user');

        // Apply filters
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Pagination
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/audit-logs/user/{id}",
     *     tags={"Audit"},
     *     summary="Get audit logs for a specific user",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="User's audit logs")
     * )
     */
    public function userLogs(Request $request, string $id): JsonResponse
    {
        if (!$request->user() || $request->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $logs = AuditService::getUserLogs(
            \App\Models\User::findOrFail($id),
            $request->get('limit', 50)
        );

        return response()->json($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/audit-logs/entity/{type}/{id}",
     *     tags={"Audit"},
     *     summary="Get audit logs for a specific entity",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Entity's audit logs")
     * )
     */
    public function entityLogs(Request $request, string $type, string $id): JsonResponse
    {
        if (!$request->user() || $request->user()->type !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $logs = AuditService::getEntityLogs(
            $type,
            $id,
            $request->get('limit', 50)
        );

        return response()->json($logs);
    }
}
