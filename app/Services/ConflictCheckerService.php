<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Room_booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ConflictCheckerService
{
    public function hasRoomConflict($roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        return Room_booking::where('room_id', $roomId)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in', '<', $checkOut)
                        ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }

    public function hasDuplicateUserBooking($roomTypeId, Carbon $checkIn, Carbon $checkOut, $userId): bool
    {
        return Room_booking::where('room_type_id', $roomTypeId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in', '<', $checkOut)
                        ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }

    public function isRoomTypeAvailable(int $roomTypeId, Carbon $checkIn, Carbon $checkOut): bool
    {
        $totalRooms = Room::where('room_type_id', $roomTypeId)->count();
        $currentDay = $checkIn->copy();

        while ($currentDay < $checkOut) {
            $day = $currentDay->toDateString();

            $confirmedCount = Room_booking::where('room_type_id', $roomTypeId)
            ->where('status', 'confirmed')
            ->whereDate('check_in', '<=', $day)
            ->whereDate('check_out', '>', $day)
            ->count();

            if ($confirmedCount >= $totalRooms) {
                return false; // ما في غرف متاحة بهذا اليوم
            }

            $currentDay->addDay();
        }

        return true; // كل الأيام عندها متسع
    }

}
