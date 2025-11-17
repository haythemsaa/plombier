<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_id',
        'rating',
        'professionalism_rating',
        'quality_rating',
        'value_rating',
        'comment',
        'response',
        'responded_at',
        'is_verified',
        'is_featured',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeHighRated($query)
    {
        return $query->where('rating', '>=', 4);
    }

    // Helper methods
    public function getAverageDetailedRating(): float
    {
        $ratings = array_filter([
            $this->professionalism_rating,
            $this->quality_rating,
            $this->value_rating,
        ]);

        if (empty($ratings)) {
            return 0;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    public function hasResponse(): bool
    {
        return !is_null($this->response);
    }
}
