<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'level',
        'min_points_required',
        'min_bookings_required',
        'discount_percentage',
        'priority_support',
        'benefits',
        'badge_color',
        'badge_icon',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'benefits' => 'array',
    ];

    /**
     * Tier levels
     */
    const LEVEL_BRONZE = 1;
    const LEVEL_SILVER = 2;
    const LEVEL_GOLD = 3;
    const LEVEL_PLATINUM = 4;

    /**
     * Get all clients in this tier.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'loyalty_tier_id');
    }

    /**
     * Check if user qualifies for this tier.
     */
    public static function calculateTier(int $points, int $bookings): ?LoyaltyTier
    {
        return self::where('min_points_required', '<=', $points)
            ->where('min_bookings_required', '<=', $bookings)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Get tier by level.
     */
    public static function getByLevel(int $level): ?LoyaltyTier
    {
        return self::where('level', $level)->first();
    }
}
