<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room_booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_in',
        'check_out',
        'total_price',
        'status',
        'guest_name',
        'room_type_id',
        'room_id',
        'user_id',
        'approved_by'
    ];

    public $timestamps = false;

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType()
    {
        return $this->belongsTo(Room_type::class, 'room_type_id');
    }


}
