<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// class Restaurant_order extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'table_number',
//         'number_of_people',
//         'preferred_time',
//         'table_price',
//         'order_type',
//         'status',
//         'user_id',
//         'approved_by'
//     ];

//     public $timestamps = false;
// }
class RestaurantOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
        'preferred_time',
        'user_id',
        'status',
        'table_number',
        'room_number',
        'number_of_people',
        'booked_duration',
        'reservation_end_time',
        'table_price',
        'total_price',
        'approved_or_rejected_by',
    ];


    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orderItems()
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }
    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_number', 'table_number');
    }
    public function items()
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }
}
