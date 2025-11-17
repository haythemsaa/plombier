<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'cin',
        'business_license',
        'subscription_type',
        'subscription_expires_at',
        'rating_average',
        'rating_count',
        'completion_rate',
        'response_time_avg',
        'total_earnings',
        'status',
        'verified_at',
        'bio',
        'years_experience',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'rating_average' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'total_earnings' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(ProviderDocument::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'provider_services')
            ->withPivot('price', 'min_price', 'experience_years', 'is_available')
            ->withTimestamps();
    }

    public function zones()
    {
        return $this->hasMany(ProviderZone::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'provider_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'provider_id', 'user_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeTopRated($query)
    {
        return $query->where('rating_average', '>=', 4.5)
            ->where('rating_count', '>=', 10);
    }

    // Helper methods
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function isPremium(): bool
    {
        return $this->subscription_type === 'premium';
    }
}
