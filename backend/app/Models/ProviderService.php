<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderService extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'service_id',
        'price',
        'min_price',
        'experience_years',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
