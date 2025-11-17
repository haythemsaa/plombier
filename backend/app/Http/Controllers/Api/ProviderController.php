<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Search for providers
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'location' => 'required|array',
            'location.governorate' => 'required|string',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceId = $request->service_id;
        $location = $request->location;
        $filters = $request->filters ?? [];

        $query = Provider::query()
            ->where('status', 'active')
            ->whereHas('services', function($q) use ($serviceId) {
                $q->where('service_id', $serviceId)
                  ->where('is_available', true);
            });

        // Filter by zone
        if (isset($location['governorate'])) {
            $query->whereHas('zones', function($q) use ($location) {
                $q->where('governorate', $location['governorate']);
            });
        }

        // Filter by minimum rating
        if (isset($filters['min_rating'])) {
            $query->where('rating_average', '>=', $filters['min_rating']);
        }

        // Verified only
        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('verified_at');
        }

        $providers = $query->with(['user', 'services', 'zones'])->get();

        // Calculate relevance score for each provider
        $scoredProviders = $providers->map(function($provider) use ($location, $serviceId) {
            $score = $this->matchingService->calculateRelevanceScore(
                $provider,
                $location,
                $serviceId
            );

            $provider->relevance_score = $score;
            $provider->distance = $this->matchingService->calculateDistance(
                $provider->zones->first(),
                $location
            );

            return $provider;
        });

        // Sort by relevance score
        $sortedProviders = $scoredProviders->sortByDesc('relevance_score')->values();

        return response()->json([
            'providers' => $sortedProviders
        ]);
    }

    /**
     * Get provider details
     */
    public function show($id)
    {
        $provider = Provider::with([
            'user',
            'services',
            'zones',
            'reviews.client'
        ])->findOrFail($id);

        return response()->json([
            'provider' => $provider
        ]);
    }

    /**
     * Get provider dashboard stats
     */
    public function dashboard(Request $request)
    {
        $provider = $request->user()->provider;

        $stats = [
            'total_bookings' => $provider->bookings()->count(),
            'completed_bookings' => $provider->bookings()->completed()->count(),
            'pending_bookings' => $provider->bookings()->pending()->count(),
            'total_earnings' => $provider->total_earnings,
            'rating_average' => $provider->rating_average,
            'rating_count' => $provider->rating_count,
            'completion_rate' => $provider->completion_rate,
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Get provider earnings
     */
    public function earnings(Request $request)
    {
        $provider = $request->user()->provider;

        $bookings = $provider->bookings()
            ->where('status', 'completed')
            ->with('service')
            ->get();

        $totalEarnings = $bookings->sum(function($booking) {
            return $booking->total - $booking->commission;
        });

        $monthlyEarnings = $bookings->where('completed_at', '>=', now()->startOfMonth())
            ->sum(function($booking) {
                return $booking->total - $booking->commission;
            });

        return response()->json([
            'total_earnings' => $totalEarnings,
            'monthly_earnings' => $monthlyEarnings,
            'bookings' => $bookings
        ]);
    }
}
