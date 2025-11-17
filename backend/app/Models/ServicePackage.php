<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'description',
        'type',
        'sessions_included',
        'price',
        'original_price',
        'validity_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Package types
     */
    const TYPE_ONE_TIME = 'one_time';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_ANNUAL = 'annual';

    /**
     * Get the service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(PackageSubscription::class, 'package_id');
    }

    /**
     * Get active subscriptions.
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Calculate savings percentage.
     */
    public function getSavingsPercentageAttribute(): float
    {
        if ($this->original_price <= 0) {
            return 0;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 1);
    }

    /**
     * Calculate price per session.
     */
    public function getPricePerSessionAttribute(): float
    {
        if ($this->sessions_included <= 0) {
            return 0;
        }

        return round($this->price / $this->sessions_included, 2);
    }

    /**
     * Check if package is subscription-based.
     */
    public function isSubscription(): bool
    {
        return in_array($this->type, [
            self::TYPE_MONTHLY,
            self::TYPE_QUARTERLY,
            self::TYPE_ANNUAL,
        ]);
    }

    /**
     * Get billing cycle in days.
     */
    public function getBillingCycleDays(): int
    {
        return match($this->type) {
            self::TYPE_MONTHLY => 30,
            self::TYPE_QUARTERLY => 90,
            self::TYPE_ANNUAL => 365,
            default => 0,
        };
    }
}
