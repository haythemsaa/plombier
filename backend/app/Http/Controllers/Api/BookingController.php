<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get user's bookings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::query();

        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isProvider()) {
            $query->where('provider_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->with(['service', 'address', 'client', 'provider'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|uuid|exists:users,id',
            'service_id' => 'required|integer|exists:services,id',
            'address_id' => 'required|integer|exists:addresses,id',
            'scheduled_at' => 'required|date|after:now',
            'payment_method' => 'nullable|string',
            'instructions' => 'nullable|string',
            'is_urgent' => 'nullable|boolean',
            'promo_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = $this->bookingService->createBooking([
                'client_id' => $request->user()->id,
                'provider_id' => $request->provider_id,
                'service_id' => $request->service_id,
                'address_id' => $request->address_id,
                'scheduled_at' => $request->scheduled_at,
                'payment_method' => $request->payment_method,
                'instructions' => $request->instructions,
                'is_urgent' => $request->is_urgent ?? false,
                'promo_code' => $request->promo_code,
            ]);

            $booking->load(['service', 'address', 'client', 'provider']);

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get booking details
     */
    public function show($id)
    {
        $booking = Booking::with([
            'service',
            'address',
            'client',
            'provider.provider',
            'photos',
            'review'
        ])->findOrFail($id);

        return response()->json([
            'booking' => $booking
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);
            $user = $request->user();

            // Determine who is cancelling
            $cancelledBy = $user->id === $booking->client_id ? 'client' : 'provider';

            $result = $this->bookingService->cancelBooking(
                $id,
                $cancelledBy,
                $request->reason
            );

            return response()->json([
                'message' => 'Booking cancelled successfully',
                'booking' => $result['booking'],
                'cancellation_fees' => $result['cancellation_fees']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm a booking (provider only)
     */
    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be confirmed'
            ], 422);
        }

        $booking->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'Booking confirmed successfully',
            'booking' => $booking
        ]);
    }

    /**
     * Start a booking (provider only)
     */
    public function start($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Only confirmed bookings can be started'
            ], 422);
        }

        $booking->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);

        return response()->json([
            'message' => 'Booking started successfully',
            'booking' => $booking
        ]);
    }

    /**
     * Complete a booking (provider only)
     */
    public function complete($id)
    {
        try {
            $booking = $this->bookingService->completeBooking($id);

            return response()->json([
                'message' => 'Booking completed successfully',
                'booking' => $booking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get provider's bookings
     */
    public function providerBookings(Request $request)
    {
        $provider = $request->user()->provider;

        $bookings = Booking::where('provider_id', $request->user()->id)
            ->with(['service', 'address', 'client'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return response()->json([
            'bookings' => $bookings
        ]);
    }
}
