<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'birthday',
        'role',
        'job_title',
        'employment_history',
        'salary',
        'housing',
        'user_id'
    ];

    public $timestamps = false;

    public function massageRequests()
{
    return $this->hasMany(Massage_request::class, 'employee_id');
}
}
