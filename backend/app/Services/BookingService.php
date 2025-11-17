<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingService
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data)
    {
        // Generate unique booking number
        $bookingNumber = $this->generateBookingNumber();

        // Calculate pricing
        $pricing = $this->calculatePricing($data);

        // Create the booking
        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'client_id' => $data['client_id'],
            'provider_id' => $data['provider_id'],
            'service_id' => $data['service_id'],
            'address_id' => $data['address_id'],
            'scheduled_at' => $data['scheduled_at'],
            'price' => $pricing['base_price'],
            'discount' => $pricing['discount'],
            'total' => $pricing['total'],
            'commission' => $pricing['commission'],
            'payment_method' => $data['payment_method'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'status' => 'pending'
        ]);

        return $booking;
    }

    /**
     * Calculate booking pricing
     */
    protected function calculatePricing(array $data)
    {
        $providerService = \App\Models\ProviderService::where([
            'provider_id' => $data['provider_id'],
            'service_id' => $data['service_id']
        ])->first();

        if (!$providerService) {
            throw new \Exception('Service not available from this provider');
        }

        $basePrice = $providerService->price;
        $multiplier = 1.0;

        // Apply surcharges
        $scheduledAt = Carbon::parse($data['scheduled_at']);

        // Urgent (within 2 hours)
        if (isset($data['is_urgent']) && $data['is_urgent']) {
            $multiplier += 0.20; // +20%
        }

        // Night time (22h-6h)
        $hour = $scheduledAt->hour;
        if ($hour >= 22 || $hour < 6) {
            $multiplier += 0.30; // +30%
        }

        // Weekend
        if ($scheduledAt->isWeekend()) {
            $multiplier += 0.15; // +15%
        }

        $adjustedPrice = $basePrice * $multiplier;

        // Apply discount if promo code
        $discount = 0;
        if (isset($data['promo_code'])) {
            $discount = $this->applyPromoCode($data['promo_code'], $adjustedPrice);
        }

        $total = $adjustedPrice - $discount;

        // Calculate commission
        $service = Service::find($data['service_id']);
        $commissionRate = $service->commission_rate;

        // Reduce commission for premium providers
        $provider = Provider::find($data['provider_id']);
        if ($provider->subscription_type === 'premium') {
            $commissionRate = 15.00;
        } elseif ($provider->subscription_type === 'pro') {
            $commissionRate = 17.00;
        }

        $commission = $total * ($commissionRate / 100);

        return [
            'base_price' => $basePrice,
            'adjusted_price' => $adjustedPrice,
            'discount' => $discount,
            'total' => $total,
            'commission' => $commission,
            'commission_rate' => $commissionRate
        ];
    }

    /**
     * Apply promo code
     */
    protected function applyPromoCode(string $code, float $price)
    {
        // TODO: Implement promo code logic
        // For now, return 0
        return 0;
    }

    /**
     * Generate unique booking number
     */
    protected function generateBookingNumber()
    {
        do {
            $number = 'BOOK-' . strtoupper(Str::random(5)) . rand(1000, 9999);
        } while (Booking::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Complete a booking
     */
    public function completeBooking($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== 'in_progress') {
            throw new \Exception('Booking must be in progress to complete');
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // Update provider stats
        $this->updateProviderStats($booking->provider_id);

        return $booking;
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking($bookingId, $cancelledBy, $reason)
    {
        $booking = Booking::findOrFail($bookingId);

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            throw new \Exception('This booking cannot be cancelled');
        }

        // Calculate cancellation fees
        $cancellationFees = $this->calculateCancellationFees($booking, $cancelledBy);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy
        ]);

        return [
            'booking' => $booking,
            'cancellation_fees' => $cancellationFees
        ];
    }

    /**
     * Calculate cancellation fees
     */
    protected function calculateCancellationFees($booking, $cancelledBy)
    {
        $hoursUntilBooking = now()->diffInHours($booking->scheduled_at, false);

        $fees = [
            'cancellation_fee' => 0,
            'refund_amount' => $booking->total,
            'penalty' => 0
        ];

        if ($cancelledBy === 'client') {
            if ($hoursUntilBooking < 2) {
                // Less than 2h: 50% fee
                $fees['cancellation_fee'] = $booking->total * 0.50;
                $fees['refund_amount'] = $booking->total * 0.50;
            } elseif ($hoursUntilBooking < 24) {
                // 2-24h: 20% fee
                $fees['cancellation_fee'] = $booking->total * 0.20;
                $fees['refund_amount'] = $booking->total * 0.80;
            }
            // More than 24h: Full refund
        } elseif ($cancelledBy === 'provider') {
            // Penalty for provider
            if ($hoursUntilBooking < 24) {
                $fees['penalty'] = 50; // 50 TND penalty
            }
            $fees['refund_amount'] = $booking->total; // Full refund for client
        }

        return $fees;
    }

    /**
     * Update provider statistics
     */
    protected function updateProviderStats($providerId)
    {
        $provider = Provider::where('user_id', $providerId)->first();

        $completedBookings = Booking::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->count();

        $totalBookings = Booking::where('provider_id', $providerId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->count();

        $completionRate = $totalBookings > 0
            ? ($completedBookings / $totalBookings) * 100
            : 100;

        $provider->update([
            'completion_rate' => $completionRate
        ]);
    }
}
