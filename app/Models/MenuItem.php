<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_name',
        'en_name',
        'ar_description',
        'en_description',
        'photo',
        'type',
        'price'
    ];

    public $timestamps = false;
}
