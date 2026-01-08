<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'date',
        'price',
        'status',
        'item_type',
        'item_id',
        'user_id'
    ];

    public $timestamps = false;
}
