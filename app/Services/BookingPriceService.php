<?php
namespace App\Services;

use App\Models\Promotion;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Room_type;

class BookingPriceService
{
    public function calculatePrice(Room_type $roomType, Carbon $checkIn, Carbon $checkOut): array
    {
        $nights = $checkIn->diffInDays($checkOut, false);
        if ($nights < 0) {
            throw new \InvalidArgumentException("تاريخ المغادرة يجب أن يكون بعد أو في نفس يوم الوصول.");
        }


        $activePromotions = Promotion::where('promotion_type', 'BookRoom')
            ->where('active', true)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('start_date', '<=', $checkOut)
                ->where('end_date', '>=', $checkIn);
            })
            ->get();

        $pricePerNight = $roomType->price;
        $currentDay = $checkIn->copy();
        $totalPrice = 0;
        $priceDetails = [];

        while ($currentDay < $checkOut) {
            $dayPrice = $pricePerNight;
            $appliedPromotion = null;

            foreach ($activePromotions as $promo) {
                if ($currentDay->between(Carbon::parse($promo->start_date), Carbon::parse($promo->end_date))) {
                    $appliedPromotion = $promo;
                    if ($promo->discount_type === 'percentage') {
                        $dayPrice -= ($dayPrice * ($promo->discount_value / 100));
                    } elseif ($promo->discount_type === 'fixed') {
                        $dayPrice -= $promo->discount_value;
                        $dayPrice = max(0, $dayPrice);
                    }
                    break;
                }
            }

            $priceDetails[] = [
                'date' => $currentDay->toDateString(),
                'original_price' => $pricePerNight,
                'final_price' => round($dayPrice, 2),
                'promotion_applied' => $appliedPromotion ? $appliedPromotion->title : null,
            ];

            $totalPrice += $dayPrice;
            $currentDay->addDay();
        }

        return [
            'total_price' => $totalPrice,
            'price_details' => $priceDetails
        ];
    }
}
