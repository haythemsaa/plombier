<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'governorate',
        'cities',
        'radius_km',
    ];

    protected $casts = [
        'cities' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
