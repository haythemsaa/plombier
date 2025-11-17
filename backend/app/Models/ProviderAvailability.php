<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProviderAvailability extends Model
{
    protected $table = 'provider_availability';

    protected $fillable = [
        'provider_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Days of week
     */
    const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * Get the provider.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Check if provider is available at a specific time.
     */
    public static function isAvailableAt(string $providerId, Carbon $datetime): bool
    {
        $dayOfWeek = strtolower($datetime->englishDayOfWeek);
        $time = $datetime->format('H:i:s');

        // Check weekly availability
        $availability = self::where('provider_id', $providerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->exists();

        if (!$availability) {
            return false;
        }

        // Check if time slot is blocked
        $isBlocked = ProviderBlockedSlot::where('provider_id', $providerId)
            ->where('start_datetime', '<=', $datetime)
            ->where('end_datetime', '>=', $datetime)
            ->exists();

        return !$isBlocked;
    }

    /**
     * Get available slots for a provider on a specific date.
     */
    public static function getAvailableSlots(string $providerId, Carbon $date, int $durationMinutes = 60): array
    {
        $dayOfWeek = strtolower($date->englishDayOfWeek);

        // Get weekly availability
        $availabilities = self::where('provider_id', $providerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->get();

        if ($availabilities->isEmpty()) {
            return [];
        }

        $slots = [];

        foreach ($availabilities as $availability) {
            $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $availability->start_time);
            $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $availability->end_time);

            $current = $startTime->copy();

            while ($current->lt($endTime)) {
                $slotEnd = $current->copy()->addMinutes($durationMinutes);

                if ($slotEnd->lte($endTime)) {
                    // Check if slot is not blocked
                    if (self::isAvailableAt($providerId, $current)) {
                        // Check if slot is not already booked
                        $isBooked = Booking::where('provider_id', $providerId)
                            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                            ->where(function ($query) use ($current, $slotEnd) {
                                $query->whereBetween('scheduled_at', [$current, $slotEnd])
                                    ->orWhere(function ($q) use ($current, $slotEnd) {
                                        $q->where('scheduled_at', '<=', $current)
                                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL estimated_duration MINUTE) >= ?', [$current]);
                                    });
                            })
                            ->exists();

                        if (!$isBooked) {
                            $slots[] = [
                                'start' => $current->toIso8601String(),
                                'end' => $slotEnd->toIso8601String(),
                                'available' => true,
                            ];
                        }
                    }
                }

                $current->addMinutes(30); // 30-minute intervals
            }
        }

        return $slots;
    }
}
