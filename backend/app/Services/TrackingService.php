<?php

namespace App\Services;

use App\Models\ProviderLocation;
use App\Models\Booking;
use App\Events\ProviderLocationUpdated;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TrackingService
{
    /**
     * Update provider location.
     */
    public static function updateLocation(
        string $providerId,
        float $latitude,
        float $longitude,
        ?int $bookingId = null,
        ?float $accuracy = null,
        ?float $speed = null,
        ?float $heading = null
    ): ProviderLocation {
        $location = ProviderLocation::create([
            'provider_id' => $providerId,
            'booking_id' => $bookingId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'heading' => $heading,
            'recorded_at' => now(),
        ]);

        // Cache latest location for quick access
        Cache::put("provider_location:{$providerId}", [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'updated_at' => now()->toIso8601String(),
        ], 600); // 10 minutes

        // If tracking for a booking, update ETA
        if ($bookingId) {
            self::updateBookingETA($bookingId, $location);
        }

        // Broadcast location update
        broadcast(new ProviderLocationUpdated($location))->toOthers();

        return $location;
    }

    /**
     * Get provider's latest location.
     */
    public static function getLatestLocation(string $providerId): ?array
    {
        // Try cache first
        $cached = Cache::get("provider_location:{$providerId}");
        if ($cached) {
            return $cached;
        }

        // Fallback to database
        $location = ProviderLocation::where('provider_id', $providerId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$location) {
            return null;
        }

        return [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'updated_at' => $location->recorded_at->toIso8601String(),
        ];
    }

    /**
     * Get provider's location history for a booking.
     */
    public static function getLocationHistory(int $bookingId, int $minutes = 30): array
    {
        $since = Carbon::now()->subMinutes($minutes);

        return ProviderLocation::where('booking_id', $bookingId)
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->map(function ($location) {
                return [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'speed' => $location->speed,
                    'heading' => $location->heading,
                    'timestamp' => $location->recorded_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Update booking ETA based on current location.
     */
    protected static function updateBookingETA(int $bookingId, ProviderLocation $location): void
    {
        $booking = Booking::find($bookingId);

        if (!$booking || !$booking->address) {
            return;
        }

        $eta = $location->calculateETA(
            $booking->address->latitude,
            $booking->address->longitude
        );

        if ($eta !== null) {
            $booking->update(['estimated_arrival_minutes' => $eta]);
        }
    }

    /**
     * Start tracking for a booking.
     */
    public static function startTracking(int $bookingId): void
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return;
        }

        $booking->update([
            'tracking_enabled' => true,
            'provider_en_route_at' => now(),
        ]);

        // Notify client that provider is on the way
        // TODO: Send push notification
    }

    /**
     * Stop tracking for a booking.
     */
    public static function stopTracking(int $bookingId): void
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return;
        }

        $booking->update(['tracking_enabled' => false]);
    }

    /**
     * Calculate distance between provider and booking address.
     */
    public static function getDistanceToBooking(string $providerId, int $bookingId): ?float
    {
        $location = self::getLatestLocation($providerId);
        if (!$location) {
            return null;
        }

        $booking = Booking::with('address')->find($bookingId);
        if (!$booking || !$booking->address) {
            return null;
        }

        $tempLocation = new ProviderLocation([
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
        ]);

        return $tempLocation->distanceTo(
            $booking->address->latitude,
            $booking->address->longitude
        );
    }

    /**
     * Check if provider is near booking location (within 500m).
     */
    public static function isProviderNearby(string $providerId, int $bookingId, float $radiusKm = 0.5): bool
    {
        $distance = self::getDistanceToBooking($providerId, $bookingId);

        return $distance !== null && $distance <= $radiusKm;
    }

    /**
     * Clean up old location records (keep last 7 days).
     */
    public static function cleanupOldLocations(): int
    {
        $cutoffDate = Carbon::now()->subDays(7);

        return ProviderLocation::where('recorded_at', '<', $cutoffDate)->delete();
    }
}
