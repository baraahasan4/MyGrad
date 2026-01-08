<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'status',
    ];


    public function orders()
    {
       return $this->hasMany(RestaurantOrder::class, 'table_number', 'table_number');
    }
}
