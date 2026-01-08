<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccasionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_name',
         'en_name'
        ];

    public $timestamps = false;

    public function decorations()
    {
        return $this->hasMany(Decoration::class);
    }

    public function hospitalities()
    {
        return $this->hasMany(Hospitality::class);
    }



    // public function decorations()
    // {
    //     return $this->belongsToMany(Decoration::class, 'decoration_occasions');
    // }

    // public function hospitalities()
    // {
    //     return $this->belongsToMany(Hospitality::class, 'hospitality_occasions');
    // }
}
