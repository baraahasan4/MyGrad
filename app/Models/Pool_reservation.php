<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pool_reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_for_person',
        'number_of_people',
        'total_price',
        'date',
        'time',
        'status',
        'guest_name',
        'user_id',
        'approved_by'
    ];

    public $timestamps = false;

    public function user()
{
    return $this->belongsTo(User::class);
}

}
