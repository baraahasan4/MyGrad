<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Room_booking;
use Carbon\Carbon;

class ConflictHandlerService
{
    public function cancelPendingBookings(Room_booking $confirmedBooking)
{
    $roomTypeId = $confirmedBooking->room_type_id;
    $checkIn = Carbon::parse($confirmedBooking->check_in);
    $checkOut = Carbon::parse($confirmedBooking->check_out);

    $roomCount = Room::where('room_type_id', $roomTypeId)->count();

    // جيب كل الحجوزات المعلقة يلي ممكن تتقاطع مع الحجز المؤكد
    $pendingBookings = Room_booking::where('room_type_id', $roomTypeId)
        ->where('status', 'pending')
        ->where('id', '!=', $confirmedBooking->id)
        ->where(function ($query) use ($checkIn, $checkOut) {
            $query->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
        })
        ->get();

    foreach ($pendingBookings as $pending) {
        $pendingCheckIn = Carbon::parse($pending->check_in);
        $pendingCheckOut = Carbon::parse($pending->check_out);

        // من أول يوم تعارض نلغي الحجز مباشرة
        while ($pendingCheckIn < $pendingCheckOut) {
            $day = $pendingCheckIn->toDateString();

            $confirmedCount = Room_booking::where('room_type_id', $roomTypeId)
                ->where('status', 'confirmed')
                ->whereDate('check_in', '<=', $day)
                ->whereDate('check_out', '>', $day)
                ->count();

            if ($confirmedCount >= $roomCount) {
                // تعارض: نلغي الحجز وننتقل للي بعده
                $pending->update(['status' => 'cancelled']);
                break;
            }

            $pendingCheckIn->addDay();
        }
    }
}

}
