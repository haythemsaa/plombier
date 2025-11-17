<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PackageSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'sessions_remaining',
        'sessions_used',
        'start_date',
        'end_date',
        'next_billing_date',
        'price_paid',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    /**
     * Use one session.
     */
    public function useSession(): bool
    {
        if ($this->sessions_remaining <= 0) {
            return false;
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Check if expired
        if ($this->end_date && Carbon::parse($this->end_date)->isPast()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return false;
        }

        $this->decrement('sessions_remaining');
        $this->increment('sessions_used');

        // Auto-expire if no sessions left and not renewable
        if ($this->sessions_remaining === 0 && !$this->package->isSubscription()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }

        return true;
    }

    /**
     * Renew subscription.
     */
    public function renew(): bool
    {
        if (!$this->package->isSubscription()) {
            return false;
        }

        if (!$this->auto_renew) {
            return false;
        }

        // Reset sessions
        $this->update([
            'sessions_remaining' => $this->package->sessions_included,
            'sessions_used' => 0,
            'next_billing_date' => Carbon::parse($this->next_billing_date)
                ->addDays($this->package->getBillingCycleDays()),
            'status' => self::STATUS_ACTIVE,
        ]);

        return true;
    }

    /**
     * Cancel subscription.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    /**
     * Pause subscription.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume subscription.
     */
    public function resume(): void
    {
        if ($this->status === self::STATUS_PAUSED) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription has sessions remaining.
     */
    public function hasSessionsRemaining(): bool
    {
        return $this->sessions_remaining > 0;
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): int
    {
        $total = $this->sessions_used + $this->sessions_remaining;
        if ($total === 0) {
            return 0;
        }

        return round(($this->sessions_used / $total) * 100);
    }
}
