<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospitality extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_name',
        'en_name',
        'ar_description',
        'en_description',
        'image',
        'price',
        'type',
        'occasion_type_id'

    ];

    public $timestamps = false;

    public function hallBookings()
    {
        return $this->hasMany(HallBooking::class);
    }
    // public function occasionTypes()
    // {
    //     return $this->belongsToMany(OccasionType::class, 'hospitality_occasion');
    // }
    public function occasionType()
    {
        return $this->belongsTo(OccasionType::class);
    }

}
