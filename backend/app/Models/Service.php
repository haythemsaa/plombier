<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name_ar',
        'name_fr',
        'description_ar',
        'description_fr',
        'icon',
        'unit',
        'base_price',
        'commission_rate',
        'is_active',
        'order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'provider_services')
            ->withPivot('price', 'min_price', 'experience_years', 'is_available')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getName(string $locale = 'fr'): string
    {
        return $locale === 'ar' ? $this->name_ar : $this->name_fr;
    }

    public function getDescription(string $locale = 'fr'): ?string
    {
        return $locale === 'ar' ? $this->description_ar : $this->description_fr;
    }
}
