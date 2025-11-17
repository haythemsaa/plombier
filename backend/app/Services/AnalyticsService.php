<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Service;
use App\Models\Review;
use App\Models\PackageSubscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get admin dashboard metrics.
     */
    public static function getAdminDashboard(string $period = 'month'): array
    {
        $startDate = self::getStartDate($period);
        $previousStartDate = self::getPreviousStartDate($period);

        // Current period metrics
        $currentMetrics = self::getPeriodMetrics($startDate, now());
        $previousMetrics = self::getPeriodMetrics($previousStartDate, $startDate);

        return [
            'overview' => [
                'total_revenue' => $currentMetrics['revenue'],
                'total_bookings' => $currentMetrics['bookings'],
                'active_clients' => $currentMetrics['active_clients'],
                'active_providers' => $currentMetrics['active_providers'],
                'average_booking_value' => $currentMetrics['avg_booking_value'],
            ],
            'growth' => [
                'revenue_growth' => self::calculateGrowth(
                    $currentMetrics['revenue'],
                    $previousMetrics['revenue']
                ),
                'bookings_growth' => self::calculateGrowth(
                    $currentMetrics['bookings'],
                    $previousMetrics['bookings']
                ),
                'clients_growth' => self::calculateGrowth(
                    $currentMetrics['active_clients'],
                    $previousMetrics['active_clients']
                ),
            ],
            'conversion' => self::getConversionFunnel($startDate),
            'top_services' => self::getTopServices($startDate, 10),
            'geographic_distribution' => self::getGeographicDistribution($startDate),
            'revenue_by_source' => self::getRevenueBySource($startDate),
            'period' => $period,
        ];
    }

    /**
     * Get provider analytics.
     */
    public static function getProviderAnalytics(string $providerId, string $period = 'month'): array
    {
        $startDate = self::getStartDate($period);

        $bookings = Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $completedBookings = $bookings->where('status', Booking::STATUS_COMPLETED);

        // Calculate earnings
        $totalEarnings = $completedBookings->sum('provider_earnings');
        $platformFees = $completedBookings->sum('platform_fee');

        // Rating statistics
        $reviews = Review::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'earnings' => [
                'total' => $totalEarnings,
                'platform_fees' => $platformFees,
                'gross_revenue' => $totalEarnings + $platformFees,
                'average_per_booking' => $completedBookings->count() > 0
                    ? $totalEarnings / $completedBookings->count()
                    : 0,
                'by_week' => self::getEarningsByWeek($providerId, $startDate),
            ],
            'bookings' => [
                'total' => $bookings->count(),
                'completed' => $completedBookings->count(),
                'cancelled' => $bookings->where('status', Booking::STATUS_CANCELLED)->count(),
                'completion_rate' => $bookings->count() > 0
                    ? ($completedBookings->count() / $bookings->count()) * 100
                    : 0,
                'by_status' => self::getBookingsByStatus($providerId, $startDate),
            ],
            'performance' => [
                'average_rating' => $reviews->avg('rating') ?? 0,
                'total_reviews' => $reviews->count(),
                'rating_trend' => self::getRatingTrend($providerId, $startDate),
                'response_time_avg' => self::getAverageResponseTime($providerId, $startDate),
            ],
            'peak_hours' => self::getPeakHours($providerId, $startDate),
            'top_services' => self::getProviderTopServices($providerId, $startDate),
            'client_retention' => self::getClientRetentionRate($providerId, $startDate),
            'benchmark' => self::getProviderBenchmark($providerId, $startDate),
            'period' => $period,
        ];
    }

    /**
     * Get client analytics.
     */
    public static function getClientAnalytics(string $clientId, string $period = 'month'): array
    {
        $startDate = self::getStartDate($period);

        $bookings = Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $completedBookings = $bookings->where('status', Booking::STATUS_COMPLETED);

        // Get client details for loyalty info
        $client = User::find($clientId);

        // Calculate savings
        $packageSavings = self::calculatePackageSavings($clientId, $startDate);
        $loyaltySavings = self::calculateLoyaltySavings($clientId, $startDate);

        return [
            'spending' => [
                'total' => $completedBookings->sum('total_price'),
                'average_per_booking' => $completedBookings->count() > 0
                    ? $completedBookings->sum('total_price') / $completedBookings->count()
                    : 0,
                'by_month' => self::getSpendingByMonth($clientId, $startDate),
                'by_service_category' => self::getSpendingByCategory($clientId, $startDate),
            ],
            'savings' => [
                'from_packages' => $packageSavings,
                'from_loyalty' => $loyaltySavings,
                'total_saved' => $packageSavings + $loyaltySavings,
            ],
            'bookings' => [
                'total' => $bookings->count(),
                'completed' => $completedBookings->count(),
                'cancelled' => $bookings->where('status', Booking::STATUS_CANCELLED)->count(),
                'recurring_active' => $client->recurringBookings()->where('status', 'active')->count(),
            ],
            'loyalty' => [
                'current_tier' => $client->loyaltyTier?->name ?? 'Bronze',
                'total_points' => $client->loyalty_points,
                'points_to_next_tier' => self::getPointsToNextTier($client),
                'tier_benefits' => $client->loyaltyTier?->benefits ?? [],
            ],
            'favorites' => [
                'services' => self::getFavoriteServices($clientId, $startDate),
                'providers' => self::getFavoriteProviders($clientId, $startDate),
                'time_slots' => self::getFavoriteTimeSlots($clientId, $startDate),
            ],
            'patterns' => [
                'booking_frequency' => self::getBookingFrequency($clientId, $startDate),
                'preferred_days' => self::getPreferredDays($clientId, $startDate),
                'spending_trend' => self::getSpendingTrend($clientId, $startDate),
            ],
            'period' => $period,
        ];
    }

    /**
     * Get cohort analysis.
     */
    public static function getCohortAnalysis(int $months = 6): array
    {
        $cohorts = [];
        $startDate = Carbon::now()->subMonths($months);

        for ($i = 0; $i < $months; $i++) {
            $cohortStart = $startDate->copy()->addMonths($i)->startOfMonth();
            $cohortEnd = $cohortStart->copy()->endOfMonth();

            $users = User::where('user_type', 'client')
                ->whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->get();

            $cohortData = [
                'month' => $cohortStart->format('Y-m'),
                'new_users' => $users->count(),
                'retention' => [],
            ];

            // Calculate retention for each subsequent month
            for ($j = 0; $j <= $months - $i - 1; $j++) {
                $retentionMonth = $cohortStart->copy()->addMonths($j);
                $retentionStart = $retentionMonth->startOfMonth();
                $retentionEnd = $retentionMonth->endOfMonth();

                $activeUsers = Booking::whereIn('client_id', $users->pluck('id'))
                    ->whereBetween('created_at', [$retentionStart, $retentionEnd])
                    ->distinct('client_id')
                    ->count();

                $cohortData['retention'][$j] = [
                    'month' => $j,
                    'active_users' => $activeUsers,
                    'retention_rate' => $users->count() > 0
                        ? round(($activeUsers / $users->count()) * 100, 2)
                        : 0,
                ];
            }

            $cohorts[] = $cohortData;
        }

        return $cohorts;
    }

    /**
     * Get conversion funnel metrics.
     */
    protected static function getConversionFunnel(Carbon $startDate): array
    {
        $registrations = User::where('user_type', 'client')
            ->where('created_at', '>=', $startDate)
            ->count();

        $firstBookings = User::where('user_type', 'client')
            ->where('created_at', '>=', $startDate)
            ->whereHas('bookings')
            ->count();

        $completedBookings = User::where('user_type', 'client')
            ->where('created_at', '>=', $startDate)
            ->whereHas('bookings', function ($query) {
                $query->where('status', Booking::STATUS_COMPLETED);
            })
            ->count();

        $repeatCustomers = User::where('user_type', 'client')
            ->where('created_at', '>=', $startDate)
            ->has('bookings', '>=', 2)
            ->count();

        return [
            'registrations' => $registrations,
            'first_booking' => $firstBookings,
            'completed_booking' => $completedBookings,
            'repeat_customer' => $repeatCustomers,
            'conversion_rates' => [
                'registration_to_booking' => $registrations > 0
                    ? round(($firstBookings / $registrations) * 100, 2)
                    : 0,
                'booking_to_completion' => $firstBookings > 0
                    ? round(($completedBookings / $firstBookings) * 100, 2)
                    : 0,
                'completion_to_repeat' => $completedBookings > 0
                    ? round(($repeatCustomers / $completedBookings) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Get period metrics.
     */
    protected static function getPeriodMetrics(Carbon $start, Carbon $end): array
    {
        $bookings = Booking::whereBetween('created_at', [$start, $end])
            ->where('status', Booking::STATUS_COMPLETED)
            ->get();

        return [
            'revenue' => $bookings->sum('total_price'),
            'bookings' => $bookings->count(),
            'avg_booking_value' => $bookings->count() > 0
                ? $bookings->sum('total_price') / $bookings->count()
                : 0,
            'active_clients' => Booking::whereBetween('created_at', [$start, $end])
                ->distinct('client_id')
                ->count(),
            'active_providers' => Booking::whereBetween('created_at', [$start, $end])
                ->distinct('provider_id')
                ->count(),
        ];
    }

    /**
     * Get top performing services.
     */
    protected static function getTopServices(Carbon $startDate, int $limit = 10): array
    {
        return Booking::where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->select('service_id', DB::raw('COUNT(*) as bookings'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('service_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $service = Service::find($item->service_id);
                return [
                    'service_name' => $service?->name,
                    'bookings' => $item->bookings,
                    'revenue' => $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get geographic distribution.
     */
    protected static function getGeographicDistribution(Carbon $startDate): array
    {
        return Booking::join('addresses', 'bookings.address_id', '=', 'addresses.id')
            ->where('bookings.created_at', '>=', $startDate)
            ->where('bookings.status', Booking::STATUS_COMPLETED)
            ->select('addresses.governorate', DB::raw('COUNT(*) as bookings'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('addresses.governorate')
            ->orderByDesc('revenue')
            ->get()
            ->toArray();
    }

    /**
     * Get revenue by source (bookings vs packages).
     */
    protected static function getRevenueBySource(Carbon $startDate): array
    {
        $bookingRevenue = Booking::where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereNull('package_subscription_id')
            ->sum('total_price');

        $packageRevenue = PackageSubscription::where('created_at', '>=', $startDate)
            ->where('status', PackageSubscription::STATUS_ACTIVE)
            ->sum('paid_amount');

        return [
            'one_time_bookings' => $bookingRevenue,
            'package_subscriptions' => $packageRevenue,
            'total' => $bookingRevenue + $packageRevenue,
        ];
    }

    /**
     * Get earnings by week for a provider.
     */
    protected static function getEarningsByWeek(string $providerId, Carbon $startDate): array
    {
        return Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->select(
                DB::raw('YEARWEEK(created_at) as week'),
                DB::raw('SUM(provider_earnings) as earnings'),
                DB::raw('COUNT(*) as bookings')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->toArray();
    }

    /**
     * Get provider's peak hours.
     */
    protected static function getPeakHours(string $providerId, Carbon $startDate): array
    {
        return Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('HOUR(scheduled_at) as hour'),
                DB::raw('COUNT(*) as bookings')
            )
            ->groupBy('hour')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour . ':00',
                    'bookings' => $item->bookings,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate package savings for a client.
     */
    protected static function calculatePackageSavings(string $clientId, Carbon $startDate): float
    {
        $subscriptions = PackageSubscription::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->with('package')
            ->get();

        $savings = 0;
        foreach ($subscriptions as $subscription) {
            if ($subscription->package) {
                $savings += ($subscription->package->original_price - $subscription->package->price);
            }
        }

        return $savings;
    }

    /**
     * Calculate loyalty discount savings.
     */
    protected static function calculateLoyaltySavings(string $clientId, Carbon $startDate): float
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->sum('loyalty_discount_amount') ?? 0;
    }

    /**
     * Get points needed for next tier.
     */
    protected static function getPointsToNextTier(User $client): ?int
    {
        $currentTier = $client->loyaltyTier;
        if (!$currentTier) {
            return null;
        }

        $nextTier = \App\Models\LoyaltyTier::where('level', '>', $currentTier->level)
            ->orderBy('level')
            ->first();

        if (!$nextTier) {
            return null; // Already at highest tier
        }

        return max(0, $nextTier->min_points_required - $client->loyalty_points);
    }

    /**
     * Get client's favorite services.
     */
    protected static function getFavoriteServices(string $clientId, Carbon $startDate): array
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->select('service_id', DB::raw('COUNT(*) as count'))
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $service = Service::find($item->service_id);
                return [
                    'service_name' => $service?->name,
                    'bookings' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get client's favorite providers.
     */
    protected static function getFavoriteProviders(string $clientId, Carbon $startDate): array
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->select('provider_id', DB::raw('COUNT(*) as count'))
            ->groupBy('provider_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $provider = User::find($item->provider_id);
                return [
                    'provider_name' => $provider?->name,
                    'bookings' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Helper: Get start date based on period.
     */
    protected static function getStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    /**
     * Helper: Get previous period start date.
     */
    protected static function getPreviousStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => Carbon::yesterday(),
            'week' => Carbon::now()->subWeek()->startOfWeek(),
            'month' => Carbon::now()->subMonth()->startOfMonth(),
            'quarter' => Carbon::now()->subQuarter()->startOfQuarter(),
            'year' => Carbon::now()->subYear()->startOfYear(),
            default => Carbon::now()->subMonth()->startOfMonth(),
        };
    }

    /**
     * Helper: Calculate growth percentage.
     */
    protected static function calculateGrowth(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get bookings by status for provider.
     */
    protected static function getBookingsByStatus(string $providerId, Carbon $startDate): array
    {
        return Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get rating trend for provider.
     */
    protected static function getRatingTrend(string $providerId, Carbon $startDate): array
    {
        return Review::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get average response time for provider.
     */
    protected static function getAverageResponseTime(string $providerId, Carbon $startDate): ?int
    {
        $bookings = Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('confirmed_at')
            ->get();

        if ($bookings->isEmpty()) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($bookings as $booking) {
            $totalMinutes += $booking->created_at->diffInMinutes($booking->confirmed_at);
        }

        return round($totalMinutes / $bookings->count());
    }

    /**
     * Get provider's top services.
     */
    protected static function getProviderTopServices(string $providerId, Carbon $startDate): array
    {
        return Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->select('service_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(provider_earnings) as earnings'))
            ->groupBy('service_id')
            ->orderByDesc('earnings')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $service = Service::find($item->service_id);
                return [
                    'service_name' => $service?->name,
                    'bookings' => $item->count,
                    'earnings' => $item->earnings,
                ];
            })
            ->toArray();
    }

    /**
     * Get client retention rate for provider.
     */
    protected static function getClientRetentionRate(string $providerId, Carbon $startDate): float
    {
        $uniqueClients = Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->distinct('client_id')
            ->count();

        $repeatClients = Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->select('client_id', DB::raw('COUNT(*) as count'))
            ->groupBy('client_id')
            ->having('count', '>', 1)
            ->count();

        return $uniqueClients > 0 ? round(($repeatClients / $uniqueClients) * 100, 2) : 0;
    }

    /**
     * Get provider benchmark vs peers.
     */
    protected static function getProviderBenchmark(string $providerId, Carbon $startDate): array
    {
        $provider = User::find($providerId);
        $providerMetrics = self::getProviderAnalytics($providerId, 'month');

        // Get average metrics for all providers
        $allProviders = User::where('user_type', 'provider')
            ->where('verification_status', 'verified')
            ->pluck('id');

        $avgBookings = Booking::whereIn('provider_id', $allProviders)
            ->where('created_at', '>=', $startDate)
            ->count() / max($allProviders->count(), 1);

        $avgRating = Review::whereIn('provider_id', $allProviders)
            ->where('created_at', '>=', $startDate)
            ->avg('rating') ?? 0;

        return [
            'your_bookings' => $providerMetrics['bookings']['total'],
            'average_bookings' => round($avgBookings, 2),
            'your_rating' => $providerMetrics['performance']['average_rating'],
            'average_rating' => round($avgRating, 2),
            'percentile' => self::calculatePercentile($providerId, $allProviders, $startDate),
        ];
    }

    /**
     * Calculate provider's percentile ranking.
     */
    protected static function calculatePercentile(string $providerId, $allProviders, Carbon $startDate): int
    {
        $providerBookings = Booking::where('provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $betterProviders = 0;
        foreach ($allProviders as $otherProviderId) {
            if ($otherProviderId === $providerId) continue;

            $otherBookings = Booking::where('provider_id', $otherProviderId)
                ->where('created_at', '>=', $startDate)
                ->count();

            if ($otherBookings > $providerBookings) {
                $betterProviders++;
            }
        }

        return 100 - round(($betterProviders / max($allProviders->count() - 1, 1)) * 100);
    }

    /**
     * Get spending by month for client.
     */
    protected static function getSpendingByMonth(string $clientId, Carbon $startDate): array
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get spending by service category.
     */
    protected static function getSpendingByCategory(string $clientId, Carbon $startDate): array
    {
        return Booking::join('services', 'bookings.service_id', '=', 'services.id')
            ->where('bookings.client_id', $clientId)
            ->where('bookings.created_at', '>=', $startDate)
            ->where('bookings.status', Booking::STATUS_COMPLETED)
            ->select('services.category', DB::raw('SUM(total_price) as total'))
            ->groupBy('services.category')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Get booking frequency for client.
     */
    protected static function getBookingFrequency(string $clientId, Carbon $startDate): string
    {
        $bookings = Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $days = $startDate->diffInDays(now());
        $perMonth = ($bookings / max($days, 1)) * 30;

        return match(true) {
            $perMonth >= 4 => 'weekly',
            $perMonth >= 2 => 'bi-weekly',
            $perMonth >= 1 => 'monthly',
            default => 'occasional',
        };
    }

    /**
     * Get preferred booking days for client.
     */
    protected static function getPreferredDays(string $clientId, Carbon $startDate): array
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DAYNAME(scheduled_at) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('day')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->pluck('day')
            ->toArray();
    }

    /**
     * Get favorite time slots for client.
     */
    protected static function getFavoriteTimeSlots(string $clientId, Carbon $startDate): array
    {
        return Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('HOUR(scheduled_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return $item->hour . ':00';
            })
            ->toArray();
    }

    /**
     * Get spending trend for client.
     */
    protected static function getSpendingTrend(string $clientId, Carbon $startDate): string
    {
        $months = Booking::where('client_id', $clientId)
            ->where('created_at', '>=', $startDate)
            ->where('status', Booking::STATUS_COMPLETED)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        if ($months->count() < 2) {
            return 'stable';
        }

        $first = $months->first()->total;
        $last = $months->last()->total;

        $change = (($last - $first) / max($first, 1)) * 100;

        return match(true) {
            $change > 20 => 'increasing',
            $change < -20 => 'decreasing',
            default => 'stable',
        };
    }
}
