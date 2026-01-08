<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Room_booking;
use App\Models\Room_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\BookingService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\CheckoutToken;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookRoomController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected InvoiceService $invoiceService,
    ) {}
    public function BookRoom(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => ['required', 'date', 'after_or_equal:today', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ];

        // إذا ما عنده رقم وطني، نطلبه من أول حجز
        if (empty($user->national_id)) {
            $rules['national_id'] = 'required|string|size:11|unique:users,national_id';
        }

        $validated = $request->validate($rules);

        // إذا أرسل رقم وطني، خزنه في جدول المستخدمين
        if (isset($validated['national_id'])) {
            $user->national_id = encrypt($validated['national_id']);
            $user->save();
        }

        return $this->bookingService->createBooking($validated);
    }
    
    public function ApproveBooking($bookingId)
    {
        return $this->bookingService->approveBooking($bookingId);
    }

    public function cancelBooking($bookingId)
    {
        $booking = Room_booking::with('room')->find($bookingId);
        if (!$booking) {
            return response()->json(['message' => 'الحجز غير موجود.'], 404);
        }
        $user = Auth::user();

        return $user->role === 'Receptionist'
        ? $this->bookingService->cancelByReceptionist($booking)
        : $this->bookingService->cancelByUser($booking);
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        return $this->bookingService->checkRoomAvailabilityAndPrice($validated);
    }


    public function bookRoomByReceptionist(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => ['required','date','after_or_equal:today','regex:/^\d{4}-\d{2}-\d{2}$/'],
            'check_out' => ['nullable','date','after:check_in','regex:/^\d{4}-\d{2}-\d{2}$/'],
            'guest_name' => 'required|string|max:255',
        ]);

        return $this->bookingService->createBookingByReceptionist($validated);
    }


    public function getUserBookingsByStatus($status)
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid booking status.'], 400);
        }

        $bookings = $this->bookingService->getBookingsCreatedByUsers($status);

            return response()->json([
                'status' => $status,
                'data' => $bookings,
            ]);
    }

    public function getReceptionBookingsByStatus($status)
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid booking status.'], 400);
        }

        $bookings = $this->bookingService->getBookingsCreatedByReception($status);

        return response()->json([
            'status' => $status,
            'created_by' => 'reception',
            'data' => $bookings,
        ]);
    }

    public function getMyBookingsByStatus($status)
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid booking status.'], 400);
        }

        $user = auth()->user();

        $bookings = $this->bookingService->getMyBookingsByStatus($status,$user->id);

            return response()->json([
                'status' => $status,
                'data' => $bookings,
            ]);
    }

    public function getAvailableRoomsByDate(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $roomTypeId = $request->room_type_id;
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;

        $availableRooms = Room::where('room_type_id', $roomTypeId)
        ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
            $query->where('status', 'confirmed')
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn);
            })
            ->with('images')
            ->get();

            $availableRooms->transform(function ($room) {
                $room->images->transform(function ($image) {
                    $image->image_path = url($image->image_path);
                    return $image;
                });
                return $room;
            });

            return response()->json([
                'available_rooms' => $availableRooms
            ]);
    }

    public function getBookedRoomsByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($request->input('date'))->startOfDay();

        $bookings = Room_booking::with(['room', 'user'])
        ->where('status', 'confirmed')
        ->where('check_in', '<=', $date)
        ->where('check_out', '>=', $date)
        ->get()
        ->map(function ($booking) {
            return [
                'id' => $booking->id,
                'room_id' => $booking->room_id,
                'room_number' => $booking->room->number,
                'user_name' => $booking->user->name,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
            ];
        });

        return response()->json([
            'date' => $date->toDateString(),
            'booked_rooms' => $bookings,
        ]);
    }

    public function getRoomTypes()
    {
        $RoomType = Room_type::get();
        return response()->json([$RoomType]);
    }

    public function getRoomsByType($typeId)
    {
        $rooms = Room::select('id', 'number', 'status', 'room_type_id')
        ->where('room_type_id', $typeId)
        ->with([
            'roomType:id,price,description_en,description_ar',
            'images'
            ])
            ->get()
            ->transform(function ($room) {
                $room->images->transform(fn($image) => $this->formatImage($image));
                return $room;
            });

            if ($rooms->isEmpty()) {
                return response()->json(['message' => 'لا توجد غرف لهذا النوع.'], 404);
            }

            return response()->json(['rooms' => $rooms], 200);
    }

    public function getRandomRooms()
    {
        $rooms = Room::select('id', 'number', 'status', 'room_type_id')
        ->with([
            'roomType:id,price,description_en,description_ar',
            'images'
            ])
            ->inRandomOrder()
            ->get()
            ->transform(function ($room) {
                $room->images->transform(fn($image) => $this->formatImage($image));
                return $room;
            });

            return response()->json($rooms);
    }

    public function getRoomDetails($roomId)
    {
        $room = Room::select('id', 'number', 'status', 'room_type_id')
        ->with([
            'roomType:id,price,description_en,description_ar',
            'images'
            ])
            ->find($roomId);

            if ($room) {
            $room->images->transform(fn($image) => $this->formatImage($image));
        }

            if (!$room) {
                return response()->json(['message' => 'الغرفة غير موجودة.'], 404);
            }

            return response()->json($room);
    }

    private function formatImage($image)
    {
        $image->image_path = url($image->image_path);
        return $image;
    }

    public function showRoomBookingInvoice($bookingId)
    {
        // جيب الفاتورة الخاصة بحجز الغرف لهذا الـ bookingId
        $invoice = $this->invoiceService->getInvoiceByItem('room_booking', $bookingId);

        if (!$invoice) {
            return response()->json(['message' => 'لم يتم العثور على الفاتورة.'], 404);
        }

        // جيب الحجز مع معلومات المستخدم (الرقم الوطني ضمن users)
        $booking = Room_booking::with(['user:id,name,email,national_id'])
        ->find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'الحجز غير موجود.'], 404);
        }

        return response()->json([
            'invoice' => [
                'id'        => $invoice->id,
                'amount'    => $invoice->amount,
                'status'    => $invoice->status,
                'date'      => $invoice->date,
                'card_last4'=> $invoice->card_last4 ?? null,
                'card_brand' => $invoice->card_brand ?? null
            ],
            'user' => [
                'id'           => $booking->user->id,
                'name'         => $booking->user->name,
                'email'        => $booking->user->email,
                'national_id'  => $booking->user->national_id
                               ? decrypt($booking->user->national_id) // فك التشفير
                                    : null,
            ],
        ]);
    }

    public function assignRoomToBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:room_bookings,id',
            'room_number' => 'required|exists:rooms,number',
        ]);

        $room = Room::where('number', $request->room_number)->first();
        $booking = Room_booking::findOrFail($request->booking_id);

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'لا يمكن إسناد غرفة إلا لحجز مؤكد فقط.'], 400);
        }

        $invoice = $this->invoiceService->getInvoiceByItem('room_booking', $booking->id);
        if (!$invoice || $invoice->status !== 'paid') {
            return response()->json(['message' => 'لا يمكن إسناد الغرفة إلا بعد دفع الفاتورة.'], 400);
        }

        if ($room->room_type_id !== $booking->room_type_id) {
            return response()->json(['message' => 'رقم الغرفة لا ينتمي إلى نوع الغرفة الذي اختاره المستخدم.'], 400);
        }

        $hasConflict = Room_booking::where('room_id', $room->id)
        ->where('status', 'confirmed')
        ->where('id', '!=', $booking->id)
        ->where(function ($query) use ($booking) {
            $query->where('check_in', '<', $booking->check_out)
                    ->where('check_out', '>', $booking->check_in);
        })
        ->exists();

        if ($hasConflict) {
            return response()->json(['message' => 'الغرفة محجوزة في تواريخ متداخلة مع هذا الحجز.'], 400);
        }

        $booking->room_id = $room->id;
        $booking->save();

        $room->status = 'booked';
        $room->save();

        return response()->json(['message' => 'تم تعيين الغرفة بنجاح وتحديث حالتها.']);
    }

    public function generateCheckoutQr($bookingId)
    {
        $booking = Room_booking::with('room')->findOrFail($bookingId);

        if ($booking->status !== 'confirmed' || !$booking->room_id) {
            return response()->json(['message' => 'لا يمكن توليد QR لهذا الحجز.'], 400);
        }

        // توكن عشوائي
        $token = Str::random(40);

        // حفظ التوكن
        CheckoutToken::updateOrCreate(
            ['room_booking_id' => $booking->id],
            [
                'token' => $token,
                'expires_at' => now()->addMinutes(30)->timezone('UTC'),
                ]
            );

            $url = route('room.checkout.verify', ['token' => $token]);

            // توليد QR كرابط صورة (base64)
            $qrImage = base64_encode(
                QrCode::format('png')->size(300)->generate($url)
            );

            return response()->json([
                'qr_url' => $url,
                'qr_image_base64' => $qrImage,
            ]);
    }

        public function verifyCheckout($token)
{
    $tokenData = CheckoutToken::where('token', $token)->first();

    if (!$tokenData || now('UTC')->greaterThan($tokenData->expires_at)) {
        return response()->json(['message' => 'الرابط منتهي أو غير صالح.'], 400);
    }

    $booking = Room_booking::with('room')->findOrFail($tokenData->room_booking_id);

    // ✅ تحقق إنو اليوزر الحالي هو صاحب الحجز
    if ($booking->user_id !== auth()->id()) {
        return response()->json(['message' => 'هذا الـ QR لا يخصك.'], 403);
    }

    if (!$booking->room || $booking->status !== 'confirmed') {
        return response()->json(['message' => 'لا يمكن تسجيل الخروج لهذا الحجز.'], 400);
    }

    // تغيير حالة الغرفة
    $booking->room->update(['status' => 'available']);

    // حذف التوكن بعد الاستخدام
    $tokenData->delete();

    return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
}

public function checkoutReceptionBooking($bookingId)
{
    $booking = Room_booking::with('room')->find($bookingId);

    if (!$booking) {
        return response()->json(['message' => 'الحجز غير موجود'], 404);
    }

    if ($booking->guest_name === null) {
        return response()->json(['message' => 'لم يتم إنشاء هذا الحجز بواسطة الريسبشن.'], 403);
    }

    if ($booking->room->status !== 'booked') {
        return response()->json(['message' => 'الغرفة غير محجوزة حاليًا.'], 400);
    }

    $booking->room->status = 'available';
    $booking->room->save();

    return response()->json(['message' => 'تم تسجيل الخروج بنجاح',]);
}

}
