<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Room_booking;
use App\Models\Invoice;
use App\Models\Room_type;
use App\Services\InvoiceService;
use App\Services\ConflictCheckerService;
use App\Services\ConflictHandlerService;
use App\Services\BookingPriceService;

class BookingService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ConflictCheckerService $conflictChecker,
        protected ConflictHandlerService $conflictHandler,
        protected BookingPriceService $bookingPriceService
        ) {}

    public function createBooking(array $data)
    {
        return DB::transaction(function () use ($data) {
            $roomType = Room_type::findOrFail($data['room_type_id']);

            $checkIn = Carbon::parse($data['check_in'])->setTime(12, 0); // 12 ظهر
            $checkOut = isset($data['check_out'])
            ? Carbon::parse($data['check_out'])->setTime(12, 0)
            : $checkIn->copy()->addDay(); // حجز يوم واحد

            $userId = Auth::id();

            // // تحقق من التعارضات
            // $existingBooking = $this->conflictChecker->hasRoomConflict($room->id, $checkIn, $checkOut);
            // if ($existingBooking) {
            //     throw new \Exception('عذراً، الغرفة محجوزة بالفعل خلال الفترة المطلوبة.');
            // }

            if (!$this->conflictChecker->isRoomTypeAvailable($data['room_type_id'], $checkIn, $checkOut)) {
                throw new \Exception('لا يوجد غرف متاحة من هذا النوع خلال الفترة المطلوبة.');
            }

            // تحقق إذا المستخدم حجز نفس الغرفة سابقاً بنفس الفترة
            $duplicateBooking = $this->conflictChecker->hasDuplicateUserBooking($roomType, $checkIn, $checkOut, $userId);
            if ($duplicateBooking) {
                throw new \Exception('لقد قمت بالفعل بطلب حجز من هذا التوع بنفس الفترة.');
            }

            $priceResult = $this->bookingPriceService->calculatePrice($roomType, $checkIn, $checkOut);
            $totalPrice = $priceResult['total_price'];
            $priceDetails = $priceResult['price_details'];

            $roomBooking = Room_booking::create([
                'user_id' => $userId,
                'room_type_id' => $roomType->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'تم إرسال طلب الحجز بنجاح. بانتظار الموافقة.',
                'booking' => $roomBooking,
                'price_breakdown' => $priceDetails,
                'total_price' => round($totalPrice, 2),
            ], 201);
        });
    }

    public function approveBooking($bookingId)
    {
        return DB::transaction(function () use ($bookingId) {

            $booking = Room_booking::find($bookingId);

            if (!$booking) {
                throw new \Exception('الحجز غير موجود.');
            }

            if ($booking->status !== 'pending') {
                throw new \Exception('لا يمكن الموافقة على هذا الحجز. حالته الحالية: ' . $booking->status);
            }

            if (!$this->conflictChecker->isRoomTypeAvailable($booking->room_type_id, $booking->check_in, $booking->check_out)) {
            throw new \Exception('لا يمكن تأكيد الحجز: لا يوجد غرف متاحة من هذا النوع خلال الفترة المطلوبة.');
        }

            $booking->status = 'confirmed';
            $booking->approved_by = Auth::id();
            $booking->save();

            $this->invoiceService->createInvoice(
                itemType: 'room_booking',
                itemId: $booking->id,
                amount: $booking->total_price,
                userId: $booking->user_id,
                description: 'فاتورة حجز غرفة نوع :' . $booking->roomType->type_name_ar,
            );
            $this->conflictHandler->cancelPendingBookings($booking);
            return response()->json(['message' => 'تمت الموافقة على الحجز بنجاح، وتم إنشاء الفاتورة.'], 200);

        });
    }

    public function cancelByUser(Room_booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'هذا الحجز ملغى بالفعل.'], 400);
        }

        $this->cancelBookingAndInvoice($booking);
        return response()->json(['message' => 'تم إلغاء الحجز بنجاح.']);

    }

    public function cancelByReceptionist(Room_booking $booking)
    {
        $invoice = Invoice::where('item_type', 'room_booking')
        ->where('item_id', $booking->id)
        ->where('status', 'unpaid')
        ->first();

        if (!$invoice && $booking->status === 'pending') {
            $booking->approved_by = Auth::id();
            $this->cancelBookingAndInvoice($booking, null);

            return response()->json(['message' => 'تم إلغاء الحجز من قبل موظف الاستقبال.']);
        }

        if (!$invoice) {
            return response()->json(['message' => 'لا يمكن إلغاء الحجز، الفاتورة غير موجودة أو تم دفعها.'], 400);
        }

        if (now()->diffInHours($invoice->date) < 8) {
            return response()->json(['message' => 'لا يمكن إلغاء الحجز قبل مرور 8 ساعات من إنشاء الفاتورة.'], 400);
        }

        $booking->approved_by = Auth::id();
        $this->cancelBookingAndInvoice($booking, $invoice);


        return response()->json(['message' => 'تم إلغاء الحجز من قبل موظف الاستقبال بنجاح.']);

    }

    protected function cancelBookingAndInvoice(Room_booking $booking, Invoice $invoice = null): void
    {
        $booking->status = 'cancelled';
        $booking->save();

        if (!$invoice) {
            $invoice = Invoice::where('item_type', 'room_booking')
                ->where('item_id', $booking->id)
                ->where('status', 'unpaid')
                ->first();
        }

        if ($invoice) {
            $invoice->status = 'cancelled';
            $invoice->save();
        }

        if ($booking->room && $booking->room->status === 'booked') {
            $booking->room->status = 'available';
            $booking->room->save();
        }
    }

    public function checkRoomAvailabilityAndPrice(array $data)
    {
        $room = Room::with('roomType')->findOrFail($data['room_id']);

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->endOfDay();

        $hasConflict = $this->conflictChecker->hasRoomConflict($room->id, $checkIn, $checkOut);

        if ($hasConflict) {
            return response()->json([
                'available' => false,
                'message' => 'الغرفة محجوزة بالفعل خلال الفترة المطلوبة.',
            ]);
        }

        $priceResult = $this->bookingPriceService->calculatePrice($room, $checkIn, $checkOut);

        return response()->json([
            'available' => true,
            'message' => 'الغرفة متاحة.',
            'total_price' => $priceResult['total_price'],
            'price_breakdown' => $priceResult['price_details'],
        ]);
    }


    public function createBookingByReceptionist(array $data)
    {
        return DB::transaction(function () use ($data) {
            $room = Room::with('roomType')->findOrFail($data['room_id']);
            $checkIn = Carbon::parse($data['check_in'])->setTime(12, 0);
            $checkOut = isset($data['check_out'])
            ? Carbon::parse($data['check_out'])->setTime(12, 0)
            : $checkIn->copy()->addDay();

            $userId = auth()->id();
            $guestName = $data['guest_name'];

            $conflict = $this->conflictChecker->hasRoomConflict($room->id, $checkIn, $checkOut);
            if ($conflict) {
                throw new \Exception('الغرفة محجوزة بالفعل في الفترة المحددة.');
            }

            $priceResult = $this->bookingPriceService->calculatePrice($room->roomType, $checkIn, $checkOut);
            $totalPrice = $priceResult['total_price'];

            $booking = Room_booking::create([
                'user_id' => $userId,
                'room_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_price' => $totalPrice,
                'status' => 'confirmed',
                'guest_name' => $guestName,
                'approved_by' => Auth::id(),
            ]);

            $room->status = 'booked';
            $room->save();

            $invoice = $this->invoiceService->createInvoice(
                itemType: 'room_booking',
                itemId: $booking->id,
                amount: $totalPrice,
                userId: $userId,
                description: 'فاتورة حجز غرفة من ' . $checkIn . ' إلى ' . $checkOut
            );

            $invoice->status = 'paid';
            $invoice->save();

            return response()->json([
                'message' => 'تم إنشاء الحجز وتأكيده مباشرة.',
                'booking' => $booking,
                'total_price' => $totalPrice,
            ]);
        });
    }


    public function getMyBookingsByStatus(string $status, $userId)
    {
        return Room_booking::select(
            'id',
            'check_in',
            'check_out',
            'total_price',
            'status',
            'approved_by',
            'room_id',
            'room_type_id',
            'user_id'
        )
        ->where('status', $status)
        ->where('user_id', $userId)
        ->with([
            'roomType:id,type_name_ar,type_name_en',
            'room:id,number,status,room_type_id',
            'room.roomType:id,type_name_ar,type_name_en',
        ])
        ->get();
    }

    public function getBookingsCreatedByUsers(string $status)
{
    return Room_booking::select(
            'id',
            'check_in',
            'check_out',
            'total_price',
            'status',
            'approved_by',
            'room_id',
            'room_type_id',
            'user_id'
        )
        ->where('status', $status)
        ->whereNull('guest_name')   // ✅ مو عن طريق الريسبشن
        ->with([
            'roomType:id,type_name_ar,type_name_en',
            'room:id,number,status,room_type_id',
            'room.roomType:id,type_name_ar,type_name_en',
            'user:id,name,email,phone',
        ])
        ->get();
}

public function getBookingsCreatedByReception(string $status)
{
    return Room_booking::select(
            'id',
            'check_in',
            'check_out',
            'total_price',
            'status',
            'room_id',
            'room_type_id',
            'guest_name'
        )
        ->where('status', $status)
        ->whereNotNull('guest_name')   // ✅ أنشأها الريسبشن
        ->with([
            'roomType:id,type_name_ar,type_name_en',
            'room:id,number,status,room_type_id',
            'room.roomType:id,type_name_ar,type_name_en',
        ])
        ->get();
}

}
