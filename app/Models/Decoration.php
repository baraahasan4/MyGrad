<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decoration extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_name',
        'en_name',
        'image',
        'price',
        'occasion_type_id'
    ];

    public $timestamps = false;
    
    public function occasionType() 
    {
        return $this->belongsTo(OccasionType::class);
    }

    // public function occasionTypes()
    // {
    //     return $this->belongsToMany(OccasionType::class, 'decoration_occasion');
    // }
    
    public function hallBookings()
    {
        return $this->hasMany(HallBooking::class);
    }
    
    
}
