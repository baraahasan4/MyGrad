<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'status',
        'room_type_id'
    ];

    public $timestamps = false;

    public function roomType()
    {
        return $this->belongsTo(Room_type::class);
    }

    public function bookings()
    {
        return $this->hasMany(Room_booking::class);
    }

    public function images()
{
    return $this->hasMany(RoomImage::class);
}



}
