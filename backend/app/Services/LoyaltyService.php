<?php

namespace App\Services;

use App\Models\Client;
use App\Models\LoyaltyTier;
use App\Models\Booking;

class LoyaltyService
{
    /**
     * Points per TND spent
     */
    const POINTS_PER_TND = 1;

    /**
     * Bonus points for completed booking
     */
    const BOOKING_COMPLETION_BONUS = 10;

    /**
     * Bonus points for leaving review
     */
    const REVIEW_BONUS = 20;

    /**
     * Bonus points for first booking
     */
    const FIRST_BOOKING_BONUS = 50;

    /**
     * Award points for a booking.
     */
    public static function awardPointsForBooking(Booking $booking): int
    {
        $client = $booking->client->client;

        if (!$client) {
            return 0;
        }

        // Calculate points from amount spent
        $amountPoints = floor($booking->total_price * self::POINTS_PER_TND);

        // Completion bonus
        $completionBonus = self::BOOKING_COMPLETION_BONUS;

        // First booking bonus
        $firstBookingBonus = 0;
        if ($client->total_bookings_count === 0) {
            $firstBookingBonus = self::FIRST_BOOKING_BONUS;
        }

        $totalPoints = $amountPoints + $completionBonus + $firstBookingBonus;

        // Add points
        $client->increment('loyalty_points', $totalPoints);
        $client->increment('total_bookings_count');
        $client->increment('total_spent', $booking->total_price);

        // Update tier
        self::updateTier($client);

        return $totalPoints;
    }

    /**
     * Award points for leaving a review.
     */
    public static function awardPointsForReview(string $clientId): int
    {
        $client = Client::where('user_id', $clientId)->first();

        if (!$client) {
            return 0;
        }

        $client->increment('loyalty_points', self::REVIEW_BONUS);

        // Update tier
        self::updateTier($client);

        return self::REVIEW_BONUS;
    }

    /**
     * Update client's loyalty tier based on points and bookings.
     */
    public static function updateTier(Client $client): ?LoyaltyTier
    {
        $newTier = LoyaltyTier::calculateTier(
            $client->loyalty_points,
            $client->total_bookings_count
        );

        if (!$newTier) {
            return null;
        }

        // Check if tier changed
        if ($client->loyalty_tier_id !== $newTier->id) {
            $oldTier = $client->loyaltyTier;

            $client->update(['loyalty_tier_id' => $newTier->id]);

            // Send notification about tier upgrade
            if ($oldTier && $newTier->level > $oldTier->level) {
                // TODO: Send congratulations notification
                \Log::info("Client {$client->user_id} upgraded to {$newTier->name}");
            }

            return $newTier;
        }

        return null;
    }

    /**
     * Calculate discount for client based on tier.
     */
    public static function calculateDiscount(Client $client, float $amount): float
    {
        $tier = $client->loyaltyTier;

        if (!$tier || $tier->discount_percentage <= 0) {
            return 0;
        }

        return round($amount * ($tier->discount_percentage / 100), 2);
    }

    /**
     * Get client tier benefits.
     */
    public static function getTierBenefits(Client $client): array
    {
        $tier = $client->loyaltyTier ?? LoyaltyTier::getByLevel(LoyaltyTier::LEVEL_BRONZE);

        if (!$tier) {
            return [];
        }

        return [
            'tier' => [
                'name' => $tier->name,
                'level' => $tier->level,
                'badge_color' => $tier->badge_color,
                'badge_icon' => $tier->badge_icon,
            ],
            'discount_percentage' => $tier->discount_percentage,
            'priority_support' => $tier->priority_support,
            'benefits' => $tier->benefits ?? [],
            'current_points' => $client->loyalty_points,
            'total_bookings' => $client->total_bookings_count,
            'total_spent' => $client->total_spent,
            'next_tier' => self::getNextTierInfo($client, $tier),
        ];
    }

    /**
     * Get information about next tier and progress.
     */
    protected static function getNextTierInfo(Client $client, LoyaltyTier $currentTier): ?array
    {
        $nextTier = LoyaltyTier::where('level', '>', $currentTier->level)
            ->orderBy('level', 'asc')
            ->first();

        if (!$nextTier) {
            return null; // Already at highest tier
        }

        $pointsNeeded = max(0, $nextTier->min_points_required - $client->loyalty_points);
        $bookingsNeeded = max(0, $nextTier->min_bookings_required - $client->total_bookings_count);

        $pointsProgress = $nextTier->min_points_required > 0
            ? min(100, round(($client->loyalty_points / $nextTier->min_points_required) * 100))
            : 100;

        $bookingsProgress = $nextTier->min_bookings_required > 0
            ? min(100, round(($client->total_bookings_count / $nextTier->min_bookings_required) * 100))
            : 100;

        return [
            'name' => $nextTier->name,
            'level' => $nextTier->level,
            'points_needed' => $pointsNeeded,
            'bookings_needed' => $bookingsNeeded,
            'points_progress' => $pointsProgress,
            'bookings_progress' => $bookingsProgress,
            'overall_progress' => round(($pointsProgress + $bookingsProgress) / 2),
        ];
    }

    /**
     * Get leaderboard.
     */
    public static function getLeaderboard(int $limit = 10): array
    {
        return Client::with(['user', 'loyaltyTier'])
            ->orderBy('loyalty_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($client, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $client->user_id,
                    'name' => $client->user->name,
                    'points' => $client->loyalty_points,
                    'tier' => $client->loyaltyTier?->name ?? 'Bronze',
                    'total_bookings' => $client->total_bookings_count,
                ];
            })
            ->toArray();
    }
}
