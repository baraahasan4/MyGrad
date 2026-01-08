<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'discount_type',
        'promotion_type',
        'discount_value',
        'start_date',
        'end_date',
        'active',
        'user_id'
    ];

    public $timestamps = false;
}
