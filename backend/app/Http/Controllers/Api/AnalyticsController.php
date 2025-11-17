<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get admin dashboard analytics.
     *
     * @group Analytics
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        // Only admins can access
        if ($request->user()->user_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->query('period', 'month');

        $analytics = AnalyticsService::getAdminDashboard($period);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get provider analytics dashboard.
     *
     * @group Analytics
     */
    public function providerDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Providers can only see their own analytics
        if ($user->user_type !== 'provider') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->query('period', 'month');

        $analytics = AnalyticsService::getProviderAnalytics($user->id, $period);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get client analytics dashboard.
     *
     * @group Analytics
     */
    public function clientDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Clients can only see their own analytics
        if ($user->user_type !== 'client') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->query('period', 'month');

        $analytics = AnalyticsService::getClientAnalytics($user->id, $period);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get cohort analysis (Admin only).
     *
     * @group Analytics
     */
    public function cohortAnalysis(Request $request): JsonResponse
    {
        // Only admins can access
        if ($request->user()->user_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $months = $request->query('months', 6);

        $cohorts = AnalyticsService::getCohortAnalysis($months);

        return response()->json([
            'success' => true,
            'data' => $cohorts,
        ]);
    }

    /**
     * Get real-time KPIs (Admin only).
     *
     * @group Analytics
     */
    public function realTimeKPIs(Request $request): JsonResponse
    {
        // Only admins can access
        if ($request->user()->user_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $kpis = [
            'today' => AnalyticsService::getAdminDashboard('today'),
            'this_week' => AnalyticsService::getAdminDashboard('week'),
        ];

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Export analytics data as CSV (Admin only).
     *
     * @group Analytics
     */
    public function export(Request $request): JsonResponse
    {
        // Only admins can access
        if ($request->user()->user_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $type = $request->query('type', 'bookings'); // bookings, revenue, users
        $period = $request->query('period', 'month');

        // TODO: Implement CSV export
        // This would generate a CSV file with the requested analytics data

        return response()->json([
            'success' => true,
            'message' => 'Export feature coming soon',
        ]);
    }
}
