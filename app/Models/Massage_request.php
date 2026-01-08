<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Massage_request extends Model
{
    use HasFactory;

    protected $fillable = [
        'preferred_time',
        'price',
        'gender',
        'status',
        'guest_name',
        'user_id',
        'approved_by',
        'employee_id'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
