<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = ['email','otp','expires_at','attempts','sent_at'];
    protected $casts = ['expires_at' => 'datetime', 'sent_at' => 'datetime'];
}
