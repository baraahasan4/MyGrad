<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'total_price',
        'restaurant_order_id',
        'menu_item_id',
    ];

    public $timestamps = false;

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
