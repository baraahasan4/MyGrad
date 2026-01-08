<?php

namespace App\Services;

use App\Models\{Decoration, HallBooking, Hospitality, OccasionType, Promotion, Service_pricing ,Invoice};
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BookingHallService
{
    public function bookHall(Request $request, InvoiceService $invoiceService)
    {
        DB::beginTransaction();

        try {
            $guests = $request->guests_count;
            $occasionType = $request->occasion_type;

            if ($occasionType === 'Wedding' && ($guests < 100 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور في الزفاف يجب أن يكون بين 100 و200 شخص'], 422);
            }

            if ($occasionType !== 'Wedding' && ($guests < 50 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور يجب أن يكون بين 50 و200 شخص'], 422);
            }

            $startTime = new \DateTime($request->start_time);
            $endTime = new \DateTime($request->end_time);

            $interval = $startTime->diff($endTime);
            $duration = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            if ($duration > 4) {
                return response()->json(['message' => 'لا يمكن حجز الصالة لأكثر من 4 ساعات'], 422);
            }

            $conflict = HallBooking::where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })->whereNotIn('status', ['pending', 'cancelled'])->exists();

            if ($conflict) {
                return response()->json(['message' => 'الصالة محجوزة في هذا الوقت'], 409);
            }

            $occasion = OccasionType::where('en_name', $occasionType)->first();
            $hospitality = Hospitality::findOrFail($request->hospitality_id);
            $decoration = Decoration::findOrFail($request->decoration_id);

            if (!$occasion || $decoration->occasion_type_id !== $occasion->id || $hospitality->occasion_type_id !== $occasion->id) {
                return response()->json(['message' => 'الديكور أو الضيافة غير متاحين لهذه المناسبة'], 422);
            }

            $hourlyHallPrice = Service_pricing::where('service_type', 'hall_booking')
                ->where('active', true)
                ->orderByDesc('date')
                ->value('price');

            if ($hourlyHallPrice === null) {
                return response()->json(['message' => 'لم يتم تحديد سعر الصالة بعد'], 500);
            }

            $hallCost = $hourlyHallPrice * $duration;
            $hospitalityCost = $hospitality->price * $guests;
            $decorationCost = $decoration->price;

            $originalTotalPrice = $hallCost + $hospitalityCost + $decorationCost;
            $finalPrice = $originalTotalPrice;

            $promotion = Promotion::where('promotion_type', 'hall_bookings')
                ->where('active', true)
                ->where('start_date', '<=', $startTime)
                ->where('end_date', '>=', $endTime)
                ->first();

            $discountAmount = 0;
            if ($promotion) {
                $discountAmount = $promotion->discount_type === 'percentage'
                    ? ($promotion->discount_value / 100) * $originalTotalPrice
                    : $promotion->discount_value;

                $finalPrice = max(0, $originalTotalPrice - $discountAmount);
            }

            $user = Auth::user();

            $booking = HallBooking::create([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'guests_count' => $guests,
                'booked_duration' => $duration,
                'status' => 'pending',
                'occasion_type' => $occasionType,
                'decoration_id' => $decoration->id,
                'hospitality_id' => $hospitality->id,
                'user_id' => $user->id,
                'guestName' => $user->name,
                'price' => $finalPrice,
            ]);

            $booking->start_time = $booking->start_time->format('Y-m-d H:i:s');
            $booking->end_time = $booking->end_time->format('Y-m-d H:i:s');

            $invoice = $invoiceService->createInvoice(
                'hall_bookings',
                $booking->id,
                $finalPrice,
                $user->id,
                $promotion
                    ? "Hall booked invoice {$occasionType} (عرض مفعّل: {$promotion->title})"
                    : "Hall booked invoice {$occasionType}"
            );

            DB::commit();

            return response()->json([
                'message' => 'تم الحجز بنجاح',
                'booking' => $booking,
                'invoice' => $invoice,
                'promotion_applied' => $promotion ? [
                    'title' => $promotion->title,
                    'discount_type' => $promotion->discount_type,
                    'discount_value' => $promotion->discount_value,
                    'discount_amount' => $discountAmount,
                    'original_price' => $originalTotalPrice,
                    'final_price' => $finalPrice
                ] : null
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء الحجز',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateBooking(Request $request, $id, InvoiceService $invoiceService)
    {
        DB::beginTransaction();

        try {
            $booking = HallBooking::with('invoice')->findOrFail($id);

            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'لا يمكن تعديل الحجز بعد قبوله أو رفضه'], 403);
            }

            if ($booking->user_id !== auth()->id()) {
                return response()->json(['message' => 'غير مصرح لك بتعديل هذا الحجز'], 403);
            }

            $startTime = new \DateTime($request->start_time);
            $endTime = new \DateTime($request->end_time);

            $duration = ($startTime->diff($endTime)->h) + ($startTime->diff($endTime)->i / 60);
            if ($duration > 4) {
                return response()->json(['message' => 'لا يمكن حجز الصالة لأكثر من 4 ساعات'], 422);
            }

            $guests = $request->guests_count;
            $occasionType = $booking->occasion_type;

            if ($occasionType === 'Wedding' && ($guests < 100 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور في الزفاف يجب أن يكون بين 100 و200 شخص'], 422);
            }

            if ($occasionType !== 'Wedding' && ($guests < 50 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور يجب أن يكون بين 50 و200 شخص'], 422);
            }

            $conflict = HallBooking::where('id', '!=', $id)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })->where('status', '!=', 'cancelled')->exists();

            if ($conflict) {
                return response()->json(['message' => 'الصالة محجوزة في هذا الوقت'], 409);
            }

            $hospitality = Hospitality::findOrFail($request->hospitality_id);
            $decoration = Decoration::findOrFail($request->decoration_id);
            $occasion = OccasionType::where('en_name', $occasionType)->first();


            if ($hospitality->occasion_type_id !== $occasion->id) {
                return response()->json(['message' => 'الضيافة غير متاحة لهذه المناسبة'], 422);
            }
            if ($decoration->occasion_type_id !== $occasion->id ) {
                return response()->json(['message' => 'الديكور غير متاح لهذه المناسبة'], 422);
            }

            $hourlyHallPrice = Service_pricing::where('service_type', 'hall_booking')
                ->where('active', true)
                ->orderByDesc('date')
                ->value('price');

            if (!$hourlyHallPrice) {
                return response()->json(['message' => 'لم يتم تحديد سعر الصالة بعد'], 500);
            }

            $hallCost = $hourlyHallPrice * $duration;
            $hospitalityCost = $hospitality->price * $guests;
            $decorationCost = $decoration->price;

            $originalTotalPrice = $hallCost + $hospitalityCost + $decorationCost;
            $finalPrice = $originalTotalPrice;

            $promotion = Promotion::where('promotion_type', 'hall_bookings')
                ->where('active', true)
                ->where('start_date', '<=', $startTime)
                ->where('end_date', '>=', $endTime)
                ->first();

            $discountAmount = 0;
            if ($promotion) {
                $discountAmount = $promotion->discount_type === 'percentage'
                    ? ($promotion->discount_value / 100) * $originalTotalPrice
                    : $promotion->discount_value;

                $finalPrice = max(0, $originalTotalPrice - $discountAmount);
            }

            $booking->update([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'guests_count' => $guests,
                'booked_duration' => $duration,
                'decoration_id' => $decoration->id,
                'hospitality_id' => $hospitality->id,
                'price' => $finalPrice,
            ]);

            $invoiceService->updateInvoice(
                'hall_bookings',
                $booking->id,
                $finalPrice,
                $promotion
                    ? "فاتورة محدثة لحجز صالة لمناسبة {$occasionType} (عرض مفعّل: {$promotion->title})"
                    : "فاتورة محدثة لحجز صالة لمناسبة {$occasionType}"
            );

            DB::commit();

            $booking->start_time = $booking->start_time->format('Y-m-d H:i:s');
            $booking->end_time = $booking->end_time->format('Y-m-d H:i:s');

            return response()->json([
                'message' => 'تم تعديل الحجز بنجاح',
                'booking' => $booking->load('invoice'),
                'promotion_applied' => $promotion ? [
                    'title' => $promotion->title,
                    'discount_type' => $promotion->discount_type,
                    'discount_value' => $promotion->discount_value,
                    'discount_amount' => $discountAmount,
                    'original_price' => $originalTotalPrice,
                    'final_price' => $finalPrice
                ] : null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء تعديل الحجز',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function acceptByReceptionist(int $id)
     {
            $booking = HallBooking::find($id);

            if (!$booking) {
                return response()->json(['message' => 'الحجز غير موجود'], 404);
            }

            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'لا يمكن تعديل حالة هذا الحجز.'], 400);
            }

            // التحقق من وجود تعارض مع حجز مؤكد
            $conflict = HallBooking::where('id', '!=', $booking->id)
                ->where('status', 'confirmed')
                ->where(function ($query) use ($booking) {
                    $query->where('start_time', '<', $booking->end_time)
                        ->where('end_time', '>', $booking->start_time);
                })->first();

            if ($conflict) {
                $booking->status = 'cancelled';
                $booking->approved_or_rejected_by = Auth::id();
                $booking->save();

                $invoice = Invoice::where('item_type', 'hall_bookings')
                    ->where('item_id', $booking->id)
                    ->first();

                if ($invoice) {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }

                return response()->json([
                    'message' => 'تم رفض الحجز تلقائيًا بسبب وجود حجز آخر مؤكد في نفس الوقت.',
                    'booking' => $booking
                ], 409);
            }

            $booking->status = 'confirmed';
            $booking->approved_or_rejected_by = Auth::id();
            $booking->save();

            //  رفض الحجوزات المتداخلة 
            $overlapping = HallBooking::where('id', '!=', $booking->id)
                ->where('status', 'pending')
                ->where(function ($query) use ($booking) {
                    $query->where('start_time', '<', $booking->end_time)
                        ->where('end_time', '>', $booking->start_time);
                })->get();

            foreach ($overlapping as $pending) {
                $pending->status = 'cancelled';
                $pending->approved_or_rejected_by = Auth::id();
                $pending->save();

                $invoice = Invoice::where('item_type', 'hall_bookings')
                ->where('item_id', $pending->id)
                ->first();

                if ($invoice) {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }
            }

            return response()->json([
                'message' => 'تم قبول الحجز بنجاح، وتم رفض الحجوزات الأخرى المتداخلة مع إلغاء فواتيرها.',
                'booking' => $booking,
                'rejected_bookings_count' => $overlapping->count()
            ]);
     }


    public function rejectByReceptionist(int $id)
    {
        $booking = HallBooking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن تعديل حالة هذا الحجز.'], 400);
        }

        $booking->status = 'cancelled';
        $booking->approved_or_rejected_by = Auth::id();
        $booking->save();

        // إلغاء الفاتورة المرتبطة بالحجز إذا وُجدت
        $invoice = Invoice::where('item_type', 'hall_bookings')
            ->where('item_id', $booking->id)
            ->first();

        if ($invoice) {
            $invoice->status = 'cancelled';
            $invoice->save();
        }

        return response()->json([
            'message' => 'تم رفض الحجز بنجاح وتم إلغاء الفاتورة المرتبطة.',
        ]);
    }

    public function bookByReceptionistForUser(Request $request, InvoiceService $invoiceService)
    {
        return DB::transaction(function () use ($request, $invoiceService) {
            $guests = $request->guests_count;
            $occasionType = $request->occasion_type;

            if ($occasionType === 'Wedding' && ($guests < 100 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور في الزفاف يجب أن يكون بين 100 و200 شخص'], 422);
            }

            if ($occasionType !== 'Wedding' && ($guests < 50 || $guests > 200)) {
                return response()->json(['message' => 'عدد الحضور يجب أن يكون بين 50 و200 شخص'], 422);
            }

            $user = auth()->user();
            $userId = $user?->id;
            $guestName = $request->guestName;

            $startTime = new \DateTime($request->start_time);
            $endTime = new \DateTime($request->end_time);
            $duration = ($startTime->diff($endTime)->h) + ($startTime->diff($endTime)->i / 60);

            if ($startTime < now()) {
                return response()->json(['message' => 'لا يمكن حجز الصالة بتاريخ ووقت في الماضي'], 422);
            }

            if ($duration > 4) {
                return response()->json(['message' => 'لا يمكن حجز الصالة لأكثر من 4 ساعات'], 422);
            }

            $conflict = HallBooking::where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })->whereNotIn('status', ['pending', 'cancelled'])->exists();

            if ($conflict) {
                return response()->json(['message' => 'الصالة محجوزة في هذا الوقت'], 409);
            }

            $occasion = OccasionType::where('en_name', $occasionType)->first();
            if (!$occasion) {
                return response()->json(['message' => 'نوع المناسبة غير موجود'], 404);
            }

            $hospitality = Hospitality::findOrFail($request->hospitality_id);
            $decoration = Decoration::findOrFail($request->decoration_id);

            if ($decoration->occasion_type_id !== $occasion->id) {
                return response()->json(['message' => 'الديكور غير متاح لهذه المناسبة'], 422);
            }

            if ($hospitality->occasion_type_id !== $occasion->id) {
                return response()->json(['message' => 'الضيافة غير متاحة لهذه المناسبة'], 422);
            }

            $hourlyHallPrice = Service_pricing::where('service_type', 'hall_booking')
                ->where('active', true)
                ->orderByDesc('date')
                ->value('price');

            if ($hourlyHallPrice === null) {
                return response()->json(['message' => 'لم يتم تحديد سعر الصالة بعد'], 500);
            }

            $hallCost = $hourlyHallPrice * $duration;
            $hospitalityCost = $hospitality->price * $guests;
            $decorationCost = $decoration->price;
            $originalTotalPrice = $hallCost + $hospitalityCost + $decorationCost;
            $finalPrice = $originalTotalPrice;

            $promotion = Promotion::where('promotion_type', 'hall_bookings')
                ->where('active', true)
                ->where('start_date', '<=', $startTime)
                ->where('end_date', '>=', $endTime)
                ->first();

            $discountAmount = 0;
            if ($promotion) {
                $discountAmount = $promotion->discount_type === 'percentage'
                    ? ($promotion->discount_value / 100) * $originalTotalPrice
                    : $promotion->discount_value;
                $finalPrice = max(0, $originalTotalPrice - $discountAmount);
            }

            $booking = HallBooking::create([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'guests_count' => $guests,
                'booked_duration' => $duration,
                'status' => 'confirmed',
                'occasion_type' => $occasionType,
                'decoration_id' => $decoration->id,
                'hospitality_id' => $hospitality->id,
                'user_id' => $userId,
                'guestName' => $guestName,
                'price' => $finalPrice,
                'approved_or_rejected_by' => $user?->id,
            ]);

            $invoice = null;
            if ($userId) {
                $invoice = $invoiceService->createInvoice(
                    itemType: 'hall_bookings',
                    itemId: $booking->id,
                    amount: $finalPrice,
                    userId: $userId,
                    description: $promotion
                        ? "فاتورة حجز صالة لمناسبة {$occasionType} (عرض مفعّل: {$promotion->title})"
                        : "فاتورة حجز صالة لمناسبة {$occasionType}"
                );
                $invoice->status = 'paid';
                $invoice->save();
            }

            $booking->start_time = $booking->start_time->format('Y-m-d H:i:s');
            $booking->end_time = $booking->end_time->format('Y-m-d H:i:s');

            return response()->json([
                'message' => 'تم تنفيذ الحجز بنجاح' . ($userId ? ' وإنشاء الفاتورة' : ' للضيف بدون حساب'),
                'booking' => $booking,
                'invoice' => $invoice,
                'promotion_applied' => $promotion ? [
                    'title' => $promotion->title,
                    'discount_type' => $promotion->discount_type,
                    'discount_value' => $promotion->discount_value,
                    'discount_amount' => $discountAmount,
                    'original_price' => $originalTotalPrice,
                    'final_price' => $finalPrice
                ] : null
            ], 201);
        });
    }
    
    public function previewHallBookingForReceptionist(Request $request)
    {
        $guests = $request->guests_count;
        $occasionType = $request->occasion_type;
    
        if ($occasionType === 'Wedding' && ($guests < 100 || $guests > 200)) {
            return response()->json(['message' => 'عدد الحضور في الزفاف يجب أن يكون بين 100 و200 شخص'], 422);
        }
    
        if ($occasionType !== 'Wedding' && ($guests < 50 || $guests > 200)) {
            return response()->json(['message' => 'عدد الحضور يجب أن يكون بين 50 و200 شخص'], 422);
        }
    
        $startTime = new \DateTime($request->start_time);
        $endTime = new \DateTime($request->end_time);
    
        if ($startTime < now()) {
            return response()->json(['message' => 'لا يمكن اختيار تاريخ سابق للحجز'], 422);
        }
    
        $duration = ($startTime->diff($endTime)->h) + ($startTime->diff($endTime)->i / 60);
    
        if ($duration > 4) {
            return response()->json(['message' => 'لا يمكن حجز الصالة لأكثر من 4 ساعات'], 422);
        }
    
        $conflict = HallBooking::where(function ($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
        })->whereNotIn('status', ['pending', 'cancelled'])->exists();
    
        if ($conflict) {
            return response()->json(['message' => 'الصالة محجوزة في هذا الوقت، لا يمكن تنفيذ الحجز'], 409);
        }
    
        $occasion = OccasionType::where('en_name', $occasionType)->first();
        if (!$occasion) {
            return response()->json(['message' => 'نوع المناسبة غير موجود'], 404);
        }
    
        $hospitality = Hospitality::findOrFail($request->hospitality_id);
        $decoration = Decoration::findOrFail($request->decoration_id);
    
        if ($decoration->occasion_type_id !== $occasion->id) {
            return response()->json(['message' => 'الديكور غير متاح لهذه المناسبة'], 422);
        }
    
        if ($hospitality->occasion_type_id !== $occasion->id) {
            return response()->json(['message' => 'الضيافة غير متاحة لهذه المناسبة'], 422);
        }
    
        $hourlyHallPrice = Service_pricing::where('service_type', 'hall_booking')
            ->where('active', true)
            ->orderByDesc('date')
            ->value('price');
    
        if ($hourlyHallPrice === null) {
            return response()->json(['message' => 'لم يتم تحديد سعر الصالة بعد'], 500);
        }
    
        $hallCost = $hourlyHallPrice * $duration;
        $hospitalityCost = $hospitality->price * $guests;
        $decorationCost = $decoration->price;
        $originalTotalPrice = $hallCost + $hospitalityCost + $decorationCost;
        $finalPrice = $originalTotalPrice;
    
        $promotion = Promotion::where('promotion_type', 'hall_bookings')
            ->where('active', true)
            ->where('start_date', '<=', $startTime)
            ->where('end_date', '>=', $endTime)
            ->first();
    
        $discountAmount = 0;
        if ($promotion) {
            $discountAmount = $promotion->discount_type === 'percentage'
                ? ($promotion->discount_value / 100) * $originalTotalPrice
                : $promotion->discount_value;
            $finalPrice = max(0, $originalTotalPrice - $discountAmount);
        }
    
        return response()->json([
            'message' => 'تم احتساب التكلفة بنجاح. يمكنك تنفيذ الحجز إن لم يظهر تعارض.',
            'duration_hours' => $duration,
            'hall_cost' => $hallCost,
            'hospitality_cost' => $hospitalityCost,
            'decoration_cost' => $decorationCost,
            'total_before_discount' => $originalTotalPrice,
            'discount' => $discountAmount,
            'total_after_discount' => $finalPrice,
            'promotion_applied' => $promotion ? [
                'title' => $promotion->title,
                'discount_type' => $promotion->discount_type,
                'discount_value' => $promotion->discount_value,
            ] : null,
            'can_proceed' => !$conflict && $startTime >= now()
        ]);
    }

}
