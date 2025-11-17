<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'url',
        'uploaded_by',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
