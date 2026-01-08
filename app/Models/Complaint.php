<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'user_id',
    ];

    public $timestamps = false;

    public function user()
{
    return $this->belongsTo(User::class);
}

}
