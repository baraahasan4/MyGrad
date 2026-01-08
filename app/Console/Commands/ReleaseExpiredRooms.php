<?php

namespace App\Console\Commands;

use App\Models\Room_booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReleaseExpiredRooms extends Command
{
    protected $signature = 'rooms:release-expired';
    protected $description = 'تحديث حالة الغرف المنتهية الحجوزات إلى متاحة';

    public function handle()
    {
        $now = Carbon::now();

        $expiredBookings = Room_booking::where('status', 'confirmed')
            ->where('check_out', '<=', $now)
            ->get();

        foreach ($expiredBookings as $booking) {
            $room = $booking->room;
            if ($room->status !== 'available') {
                $room->status = 'available';
                $room->save();
                $this->info("تم تحرير الغرفة رقم {$room->number}.");
            }
        }

        $this->info('انتهى تحديث حالة الغرف.');
    }
}
