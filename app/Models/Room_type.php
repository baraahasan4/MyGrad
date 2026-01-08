<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room_type extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'type_name_en',
        'type_name_ar',
        'description_en',
        'description_ar'
    ];

    public $timestamps = false;
}
