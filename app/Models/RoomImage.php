<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'image_path',
        'image_type'
    ];

    public $timestamps = false;

    public function room()
{
    return $this->belongsTo(Room::class);
}

}
