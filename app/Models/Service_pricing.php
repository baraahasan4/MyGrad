<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service_pricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'price',
        'date',
        'active',
        'user_id'
    ];

    public $timestamps = false;
}
