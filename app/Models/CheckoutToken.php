<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_booking_id',
        'token',
        'expires_at'
    ];
}
