<?php

namespace App\Services;

use App\Models\Provider;

class MatchingService
{
    /**
     * Calculate relevance score for a provider
     */
    public function calculateRelevanceScore($provider, $location, $serviceId)
    {
        // Distance score (30%)
        $distanceScore = $this->calculateDistanceScore($provider, $location);

        // Rating score (25%)
        $ratingScore = $this->calculateRatingScore($provider);

        // Price score (20%)
        $priceScore = $this->calculatePriceScore($provider, $serviceId);

        // Availability score (15%)
        $availabilityScore = $this->calculateAvailabilityScore($provider);

        // History score (10%)
        $historyScore = $this->calculateHistoryScore($provider);

        $totalScore = ($distanceScore * 0.3)
                    + ($ratingScore * 0.25)
                    + ($priceScore * 0.20)
                    + ($availabilityScore * 0.15)
                    + ($historyScore * 0.10);

        return round($totalScore, 2);
    }

    /**
     * Calculate distance score
     */
    protected function calculateDistanceScore($provider, $location)
    {
        $distance = $this->calculateDistance($provider->zones->first(), $location);

        if ($distance < 2) return 100;
        if ($distance < 5) return 80;
        if ($distance < 10) return 60;
        if ($distance < 15) return 40;
        return 20;
    }

    /**
     * Calculate distance between two points (Haversine formula)
     */
    public function calculateDistance($providerZone, $clientLocation)
    {
        if (!isset($clientLocation['latitude']) || !isset($clientLocation['longitude'])) {
            return 999; // Very large distance if no coordinates
        }

        // Get governorate center coordinates
        $coords = $this->getGovernorateCoordinates($providerZone->governorate);
        $providerLat = $coords['lat'];
        $providerLng = $coords['lng'];

        $lat1 = deg2rad($providerLat);
        $lon1 = deg2rad($providerLng);
        $lat2 = deg2rad($clientLocation['latitude']);
        $lon2 = deg2rad($clientLocation['longitude']);

        $earthRadius = 6371; // km

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat/2) * sin($dLat/2) +
             cos($lat1) * cos($lat2) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Rating score
     */
    protected function calculateRatingScore($provider)
    {
        if ($provider->rating_count === 0) {
            return 50; // Neutral score for new providers
        }

        $baseScore = ($provider->rating_average / 5) * 100;

        // Bonus for high number of reviews (trust)
        $reviewBonus = min(($provider->rating_count / 100) * 10, 10);

        return min($baseScore + $reviewBonus, 100);
    }

    /**
     * Price competitiveness score
     */
    protected function calculatePriceScore($provider, $serviceId)
    {
        $providerService = $provider->services->firstWhere('id', $serviceId);

        if (!$providerService) {
            return 0;
        }

        // Calculate market average price
        $averagePrice = \App\Models\ProviderService::where('service_id', $serviceId)
            ->avg('price');

        if (!$averagePrice) {
            return 100;
        }

        $priceRatio = $providerService->pivot->price / $averagePrice;

        // Optimal score for prices around market average
        if ($priceRatio >= 0.8 && $priceRatio <= 1.2) {
            return 100;
        } elseif ($priceRatio < 0.8) {
            return 70; // Too cheap = suspicious
        } else {
            return max(100 - (($priceRatio - 1.2) * 100), 0);
        }
    }

    /**
     * Availability score
     */
    protected function calculateAvailabilityScore($provider)
    {
        $score = 70; // Base score

        // Bonus for fast response time
        if ($provider->response_time_avg < 600) { // < 10 min
            $score += 30;
        } elseif ($provider->response_time_avg < 1800) { // < 30 min
            $score += 20;
        } elseif ($provider->response_time_avg < 3600) { // < 1h
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * History score
     */
    protected function calculateHistoryScore($provider)
    {
        return $provider->completion_rate;
    }

    /**
     * Get governorate coordinates
     */
    protected function getGovernorateCoordinates($governorate)
    {
        $coordinates = [
            'Tunis' => ['lat' => 36.8065, 'lng' => 10.1815],
            'Ariana' => ['lat' => 36.8625, 'lng' => 10.1956],
            'Ben Arous' => ['lat' => 36.7469, 'lng' => 10.2306],
            'Manouba' => ['lat' => 36.8080, 'lng' => 10.0965],
            'Sousse' => ['lat' => 35.8256, 'lng' => 10.6346],
            'Sfax' => ['lat' => 34.7406, 'lng' => 10.7603],
            'Nabeul' => ['lat' => 36.4561, 'lng' => 10.7376],
            'Bizerte' => ['lat' => 37.2744, 'lng' => 9.8739],
            'Monastir' => ['lat' => 35.7643, 'lng' => 10.8113],
            'Kairouan' => ['lat' => 35.6781, 'lng' => 10.0963],
            'Gabès' => ['lat' => 33.8815, 'lng' => 10.0982],
            'Médenine' => ['lat' => 33.3549, 'lng' => 10.5052],
            'Gafsa' => ['lat' => 34.4250, 'lng' => 8.7842],
        ];

        return $coordinates[$governorate] ?? ['lat' => 36.8065, 'lng' => 10.1815];
    }
}
