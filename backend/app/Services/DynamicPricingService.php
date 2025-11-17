<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DynamicPricingService
{
    /**
     * Pricing multipliers
     */
    const SURGE_PRICING_PEAK_HOURS = 1.3; // +30% during peak hours
    const SURGE_PRICING_WEEKEND = 1.15; // +15% on weekends
    const SURGE_PRICING_URGENT = 1.25; // +25% for urgent bookings
    const SURGE_PRICING_HIGH_DEMAND = 1.4; // +40% when very high demand
    const DISCOUNT_OFF_PEAK = 0.9; // -10% during off-peak hours

    /**
     * Peak hours definition
     */
    const PEAK_HOURS_START = 17; // 5 PM
    const PEAK_HOURS_END = 21; // 9 PM

    /**
     * Off-peak hours
     */
    const OFF_PEAK_HOURS = [10, 11, 14, 15]; // 10-11 AM, 2-3 PM

    /**
     * Calculate dynamic price for a booking.
     */
    public static function calculatePrice(
        float $basePrice,
        Carbon $scheduledAt,
        string $serviceCategory,
        string $governorate,
        bool $isUrgent = false
    ): array {
        $multipliers = [];
        $totalMultiplier = 1.0;

        // Time-based surge pricing
        $hour = $scheduledAt->hour;

        // Peak hours (evening)
        if ($hour >= self::PEAK_HOURS_START && $hour < self::PEAK_HOURS_END) {
            $multipliers['peak_hours'] = self::SURGE_PRICING_PEAK_HOURS;
            $totalMultiplier *= self::SURGE_PRICING_PEAK_HOURS;
        }

        // Off-peak discount
        if (in_array($hour, self::OFF_PEAK_HOURS) && !$isUrgent) {
            $multipliers['off_peak_discount'] = self::DISCOUNT_OFF_PEAK;
            $totalMultiplier *= self::DISCOUNT_OFF_PEAK;
        }

        // Weekend surge
        if ($scheduledAt->isWeekend()) {
            $multipliers['weekend_surge'] = self::SURGE_PRICING_WEEKEND;
            $totalMultiplier *= self::SURGE_PRICING_WEEKEND;
        }

        // Urgent booking
        if ($isUrgent) {
            $multipliers['urgent_surge'] = self::SURGE_PRICING_URGENT;
            $totalMultiplier *= self::SURGE_PRICING_URGENT;
        }

        // Demand-based pricing
        $demandLevel = self::getDemandLevel($scheduledAt, $serviceCategory, $governorate);
        if ($demandLevel === 'high') {
            $multipliers['high_demand'] = 1.2; // +20%
            $totalMultiplier *= 1.2;
        } elseif ($demandLevel === 'very_high') {
            $multipliers['very_high_demand'] = self::SURGE_PRICING_HIGH_DEMAND;
            $totalMultiplier *= self::SURGE_PRICING_HIGH_DEMAND;
        }

        // Calculate final price
        $finalPrice = $basePrice * $totalMultiplier;

        // Round to nearest 0.5
        $finalPrice = round($finalPrice * 2) / 2;

        return [
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'total_multiplier' => round($totalMultiplier, 2),
            'surge_percentage' => round(($totalMultiplier - 1) * 100, 1),
            'discount_percentage' => $totalMultiplier < 1 ? round((1 - $totalMultiplier) * 100, 1) : 0,
            'multipliers' => $multipliers,
            'demand_level' => $demandLevel,
        ];
    }

    /**
     * Get demand level for a specific time slot.
     */
    protected static function getDemandLevel(
        Carbon $scheduledAt,
        string $serviceCategory,
        string $governorate
    ): string {
        $cacheKey = "demand_level:{$serviceCategory}:{$governorate}:" . $scheduledAt->format('Y-m-d:H');

        return Cache::remember($cacheKey, 300, function () use ($scheduledAt, $serviceCategory, $governorate) {
            // Count bookings in the same time slot (±2 hours)
            $startTime = $scheduledAt->copy()->subHours(2);
            $endTime = $scheduledAt->copy()->addHours(2);

            $bookingsCount = \App\Models\Booking::whereHas('providerService.service', function ($query) use ($serviceCategory) {
                $query->whereHas('category', function ($q) use ($serviceCategory) {
                    $q->where('name', $serviceCategory);
                });
            })
            ->whereHas('address', function ($query) use ($governorate) {
                $query->where('governorate', $governorate);
            })
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->count();

            // Determine demand level
            if ($bookingsCount >= 20) {
                return 'very_high';
            } elseif ($bookingsCount >= 10) {
                return 'high';
            } elseif ($bookingsCount >= 5) {
                return 'medium';
            }

            return 'low';
        });
    }

    /**
     * Get surge pricing estimate for a date range.
     */
    public static function getSurgeForecast(
        Carbon $startDate,
        Carbon $endDate,
        string $serviceCategory,
        string $governorate
    ): array {
        $forecast = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayForecast = [
                'date' => $currentDate->toDateString(),
                'is_weekend' => $currentDate->isWeekend(),
                'hours' => [],
            ];

            // Check each hour of the day
            for ($hour = 8; $hour <= 20; $hour++) {
                $datetime = $currentDate->copy()->setHour($hour);
                $pricing = self::calculatePrice(100, $datetime, $serviceCategory, $governorate);

                $dayForecast['hours'][] = [
                    'hour' => $hour,
                    'surge_percentage' => $pricing['surge_percentage'],
                    'multiplier' => $pricing['total_multiplier'],
                    'demand_level' => $pricing['demand_level'],
                ];
            }

            $forecast[] = $dayForecast;
            $currentDate->addDay();
        }

        return $forecast;
    }

    /**
     * Get best time to book (lowest price).
     */
    public static function getBestTimeToBook(
        Carbon $date,
        string $serviceCategory,
        string $governorate,
        int $durationHours = 8
    ): array {
        $recommendations = [];

        for ($hour = 8; $hour <= 20 - $durationHours; $hour++) {
            $datetime = $date->copy()->setHour($hour);
            $pricing = self::calculatePrice(100, $datetime, $serviceCategory, $governorate);

            $recommendations[] = [
                'start_time' => $datetime->format('H:00'),
                'end_time' => $datetime->copy()->addHours($durationHours)->format('H:00'),
                'multiplier' => $pricing['total_multiplier'],
                'surge_percentage' => $pricing['surge_percentage'],
                'discount_percentage' => $pricing['discount_percentage'],
                'is_recommended' => $pricing['total_multiplier'] < 1.1,
            ];
        }

        // Sort by multiplier (lowest first)
        usort($recommendations, fn($a, $b) => $a['multiplier'] <=> $b['multiplier']);

        return [
            'date' => $date->toDateString(),
            'best_time' => $recommendations[0],
            'all_times' => array_slice($recommendations, 0, 5), // Top 5
        ];
    }

    /**
     * Apply provider-specific multiplier.
     */
    public static function applyProviderMultiplier(float $price, array $providerStats): float
    {
        $multiplier = 1.0;

        // High-rated providers can charge more
        if ($providerStats['average_rating'] >= 4.8) {
            $multiplier *= 1.15; // +15% for top-rated
        } elseif ($providerStats['average_rating'] >= 4.5) {
            $multiplier *= 1.1; // +10% for highly rated
        }

        // Experienced providers
        if ($providerStats['total_bookings'] >= 100) {
            $multiplier *= 1.1; // +10% for experienced
        }

        // Super providers (badges, verification, etc.)
        if ($providerStats['is_super_provider'] ?? false) {
            $multiplier *= 1.2; // +20% for super providers
        }

        return round($price * $multiplier, 2);
    }

    /**
     * Calculate zone-based pricing.
     */
    public static function getZonePricing(string $governorate): float
    {
        // Premium zones (higher prices due to higher cost of living/demand)
        $premiumZones = ['Tunis', 'Ariana', 'Ben Arous', 'La Marsa'];
        $highDemandZones = ['Sousse', 'Sfax', 'Monastir'];

        if (in_array($governorate, $premiumZones)) {
            return 1.2; // +20%
        }

        if (in_array($governorate, $highDemandZones)) {
            return 1.1; // +10%
        }

        return 1.0; // Base price
    }
}
