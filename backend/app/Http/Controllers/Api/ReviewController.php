<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Create a new review
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'professionalism_rating' => 'nullable|integer|min:1|max:5',
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $review = $this->reviewService->createReview([
                'booking_id' => $request->booking_id,
                'client_id' => $request->user()->id,
                'rating' => $request->rating,
                'professionalism_rating' => $request->professionalism_rating,
                'quality_rating' => $request->quality_rating,
                'value_rating' => $request->value_rating,
                'comment' => $request->comment,
            ]);

            $review->load(['client', 'booking.service']);

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get review details
     */
    public function show($id)
    {
        $review = Review::with([
            'client',
            'provider.provider',
            'booking.service'
        ])->findOrFail($id);

        return response()->json([
            'review' => $review
        ]);
    }

    /**
     * Provider responds to a review
     */
    public function respond(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $review = $this->reviewService->respondToReview(
                $id,
                $request->user()->id,
                $request->response
            );

            return response()->json([
                'message' => 'Response added successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to respond to review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get provider reviews
     */
    public function providerReviews(Request $request, $providerId)
    {
        $perPage = $request->input('per_page', 20);

        $result = $this->reviewService->getProviderReviews($providerId, $perPage);

        return response()->json([
            'reviews' => $result['reviews'],
            'stats' => $result['stats']
        ]);
    }

    /**
     * Get my reviews (as client)
     */
    public function myReviews(Request $request)
    {
        $reviews = Review::where('client_id', $request->user()->id)
            ->with(['booking.service', 'provider.provider'])
            ->recent()
            ->paginate(20);

        return response()->json([
            'reviews' => $reviews
        ]);
    }

    /**
     * Get reviews about me (as provider)
     */
    public function reviewsAboutMe(Request $request)
    {
        $reviews = Review::where('provider_id', $request->user()->id)
            ->with(['client', 'booking.service'])
            ->recent()
            ->paginate(20);

        $stats = $this->reviewService->getProviderReviewStats($request->user()->id);

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats
        ]);
    }

    /**
     * Get featured reviews
     */
    public function featured()
    {
        $reviews = $this->reviewService->getFeaturedReviews(10);

        return response()->json([
            'reviews' => $reviews
        ]);
    }

    /**
     * Check if booking can be reviewed
     */
    public function canReview($bookingId, Request $request)
    {
        $booking = \App\Models\Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Booking not found'
            ], 404);
        }

        // Check if booking is completed
        if ($booking->status !== 'completed') {
            return response()->json([
                'can_review' => false,
                'reason' => 'Booking must be completed'
            ]);
        }

        // Check if user is the client
        if ($booking->client_id !== $request->user()->id) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Unauthorized'
            ]);
        }

        // Check if already reviewed
        if ($booking->review) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Already reviewed'
            ]);
        }

        return response()->json([
            'can_review' => true,
            'booking' => $booking->load(['service', 'provider.provider'])
        ]);
    }
}
