<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RecurringBookingSchedule extends Model
{
    protected $fillable = [
        'client_id',
        'provider_service_id',
        'preferred_provider_id',
        'frequency',
        'days_of_week',
        'day_of_month',
        'preferred_time',
        'duration_minutes',
        'status',
        'start_date',
        'end_date',
        'next_booking_date',
        'bookings_created',
        'max_bookings',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_booking_date' => 'date',
    ];

    /**
     * Frequencies
     */
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BIWEEKLY = 'biweekly';
    const FREQUENCY_MONTHLY = 'monthly';

    /**
     * Statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the provider service.
     */
    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class);
    }

    /**
     * Get preferred provider.
     */
    public function preferredProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preferred_provider_id');
    }

    /**
     * Calculate next booking date.
     */
    public function calculateNextBookingDate(): ?Carbon
    {
        $current = $this->next_booking_date
            ? Carbon::parse($this->next_booking_date)
            : Carbon::parse($this->start_date);

        switch ($this->frequency) {
            case self::FREQUENCY_WEEKLY:
                return $current->addWeek();

            case self::FREQUENCY_BIWEEKLY:
                return $current->addWeeks(2);

            case self::FREQUENCY_MONTHLY:
                return $current->addMonth();

            default:
                return null;
        }
    }

    /**
     * Create next booking.
     */
    public function createNextBooking(): ?Booking
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return null;
        }

        // Check if max bookings reached
        if ($this->max_bookings && $this->bookings_created >= $this->max_bookings) {
            $this->update(['status' => self::STATUS_CANCELLED]);
            return null;
        }

        // Check if end date passed
        if ($this->end_date && Carbon::parse($this->end_date)->isPast()) {
            $this->update(['status' => self::STATUS_CANCELLED]);
            return null;
        }

        $scheduledAt = Carbon::parse($this->next_booking_date)
            ->setTimeFromTimeString($this->preferred_time);

        // Create booking
        $booking = Booking::create([
            'client_id' => $this->client_id,
            'provider_service_id' => $this->provider_service_id,
            'provider_id' => $this->preferred_provider_id,
            'scheduled_at' => $scheduledAt,
            'is_recurring' => true,
            'recurring_schedule_id' => $this->id,
        ]);

        // Update schedule
        $nextDate = $this->calculateNextBookingDate();
        $this->update([
            'next_booking_date' => $nextDate,
            'bookings_created' => $this->bookings_created + 1,
        ]);

        return $booking;
    }

    /**
     * Pause schedule.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume schedule.
     */
    public function resume(): void
    {
        if ($this->status === self::STATUS_PAUSED) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Cancel schedule.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
