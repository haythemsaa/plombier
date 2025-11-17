<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Booking;
use App\Models\Provider;

class ReviewService
{
    /**
     * Create a new review
     */
    public function createReview(array $data)
    {
        $booking = Booking::findOrFail($data['booking_id']);

        // Validate booking is completed
        if ($booking->status !== 'completed') {
            throw new \Exception('Can only review completed bookings');
        }

        // Check if review already exists
        if ($booking->review) {
            throw new \Exception('This booking has already been reviewed');
        }

        // Validate client is the one who made the booking
        if ($booking->client_id !== $data['client_id']) {
            throw new \Exception('Unauthorized to review this booking');
        }

        // Create review
        $review = Review::create([
            'booking_id' => $data['booking_id'],
            'client_id' => $data['client_id'],
            'provider_id' => $booking->provider_id,
            'rating' => $data['rating'],
            'professionalism_rating' => $data['professionalism_rating'] ?? null,
            'quality_rating' => $data['quality_rating'] ?? null,
            'value_rating' => $data['value_rating'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_verified' => true,
        ]);

        // Update provider statistics
        $this->updateProviderRating($booking->provider_id);

        return $review;
    }

    /**
     * Update provider rating statistics
     */
    protected function updateProviderRating($providerId)
    {
        $provider = Provider::where('user_id', $providerId)->firstOrFail();

        // Get all reviews for this provider
        $reviews = Review::where('provider_id', $providerId)
            ->verified()
            ->get();

        $totalReviews = $reviews->count();

        if ($totalReviews === 0) {
            return;
        }

        // Calculate average rating
        $averageRating = $reviews->avg('rating');

        // Update provider
        $provider->update([
            'rating_average' => round($averageRating, 2),
            'rating_count' => $totalReviews,
        ]);
    }

    /**
     * Provider responds to a review
     */
    public function respondToReview($reviewId, $providerId, $response)
    {
        $review = Review::findOrFail($reviewId);

        // Validate provider owns this review
        if ($review->provider_id !== $providerId) {
            throw new \Exception('Unauthorized to respond to this review');
        }

        // Check if already responded
        if ($review->hasResponse()) {
            throw new \Exception('Review already has a response');
        }

        $review->update([
            'response' => $response,
            'responded_at' => now(),
        ]);

        return $review;
    }

    /**
     * Get provider reviews with statistics
     */
    public function getProviderReviews($providerId, $perPage = 20)
    {
        $reviews = Review::where('provider_id', $providerId)
            ->verified()
            ->with(['client', 'booking.service'])
            ->recent()
            ->paginate($perPage);

        $stats = $this->getProviderReviewStats($providerId);

        return [
            'reviews' => $reviews,
            'stats' => $stats,
        ];
    }

    /**
     * Get provider review statistics
     */
    public function getProviderReviewStats($providerId)
    {
        $reviews = Review::where('provider_id', $providerId)
            ->verified()
            ->get();

        $totalReviews = $reviews->count();

        if ($totalReviews === 0) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    5 => 0,
                    4 => 0,
                    3 => 0,
                    2 => 0,
                    1 => 0,
                ],
                'average_professionalism' => 0,
                'average_quality' => 0,
                'average_value' => 0,
            ];
        }

        // Rating distribution
        $distribution = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($reviews->avg('rating'), 2),
            'rating_distribution' => $distribution,
            'average_professionalism' => round($reviews->avg('professionalism_rating'), 2),
            'average_quality' => round($reviews->avg('quality_rating'), 2),
            'average_value' => round($reviews->avg('value_rating'), 2),
        ];
    }

    /**
     * Mark review as featured
     */
    public function markAsFeatured($reviewId)
    {
        $review = Review::findOrFail($reviewId);

        $review->update([
            'is_featured' => true,
        ]);

        return $review;
    }

    /**
     * Get featured reviews
     */
    public function getFeaturedReviews($limit = 10)
    {
        return Review::featured()
            ->verified()
            ->with(['client', 'provider.provider', 'booking.service'])
            ->recent()
            ->limit($limit)
            ->get();
    }
}
