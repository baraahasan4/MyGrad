<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
        'guests_count',
        'price',
        'booked_duration',
        'guestName',
        'status',
        // 'hospitality_type',
        'occasion_type',
        'user_id',
        'decoration_id',
        'hospitality_id',
        'approved_or_rejected_by'
    ];

    public $timestamps = false;

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function decoration() {
        return $this->belongsTo(Decoration::class);
    }

    public function hospitality()
    {
        return $this->belongsTo(Hospitality::class);
    }

    public function approvedBy() {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'item_id')->where('item_type', 'hall_bookings');
    }
}
