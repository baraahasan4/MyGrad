<?php

namespace App\Http\Controllers;

use App\Models\HallBooking;
use App\Models\Invoice;
use App\Models\Massage_request;
use App\Models\Pool_reservation;
use App\Models\RestaurantOrder;
use App\Models\Room_booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\InvoiceService;
use Stripe\Stripe;

class PaymentController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    // تابع موحّد لإنشاء جلسة Stripe
    private function createStripeSession($name, $description, $amount, $successUrl)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $name,
                        'description' => $description,
                    ],
                    'unit_amount' => intval($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
        ]);
    }

    //  تابع موحّد لتأكيد الدفع
    private function markInvoicePaid($itemType, $itemId, $sessionId)
{
    return DB::transaction(function () use ($itemType, $itemId, $sessionId) {
        $invoice = Invoice::where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->where('status', 'unpaid')
            ->lockForUpdate()
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'الفاتورة غير موجودة أو مدفوعة مسبقاً'], 404);
        }

        // 1️⃣ جلب تفاصيل جلسة Stripe
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);

        // 2️⃣ تخزين آخر 4 أرقام ونوع البطاقة
        $invoice->card_last4 = $paymentMethod->card->last4;
        $invoice->card_brand = $paymentMethod->card->brand;
        $invoice->status = 'paid';
        $invoice->save();

        return response()->json(['message' => 'تم الدفع بنجاح.']);
    });
}


    // حجز غرفة
    public function payRoomBooking($bookingId)
    {
        $booking = Room_booking::findOrFail($bookingId);

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'يجب تأكيد الطلب أولاً قبل الدفع'], 400);
        }

        $invoice = $this->invoiceService->getInvoiceByItem('room_booking', $bookingId);

        if (!$invoice || $invoice->status === 'paid') {
            return response()->json(['message' => 'الفاتورة غير موجودة أو مدفوعة'], 400);
        }

        $session = $this->createStripeSession(
            'حجز غرفة',
            'نوع الغرفة : ' . $booking->roomType->type_name_ar,
            $booking->total_price,
            route('room.payment.success', ['bookingId' => $booking->id])
        );

        return response()->json(['url' => $session->url]);
    }

    public function paymentRoomBookingSuccess(Request $request,$bookingId)
    {
        $sessionId = $request->query('session_id');
        return $this->markInvoicePaid('room_booking', $bookingId,$sessionId);
    }

    //  مساج
    public function payMassage($massageRequestId)
    {
        $massage = Massage_request::with('user')->findOrFail($massageRequestId);

        if ($massage->user_id !== Auth::id()) {
            return response()->json(['message' => 'طلب غير مسموح'], 403);
        }

        if ($massage->status !== 'confirmed') {
            return response()->json(['message' => 'يجب تأكيد الطلب أولاً قبل الدفع'], 400);
        }

        $invoice = $this->invoiceService->getInvoiceByItem('massage', $massageRequestId);

        if (!$invoice || $invoice->status === 'paid') {
            return response()->json(['message' => 'الفاتورة غير موجودة أو مدفوعة'], 404);
        }

        $session = $this->createStripeSession(
            'حجز جلسة مساج',
            'جلسة بتاريخ: ' . $massage->preferred_time,
            $massage->price,
            route('massage.payment.success', ['massageRequestId' => $massage->id])
        );

        return response()->json(['url' => $session->url]);
    }

    public function paymentSuccessMassage(Request $request,$massageRequestId)
    {
        $sessionId = $request->query('session_id');
        return $this->markInvoicePaid('massage', $massageRequestId,$sessionId);
    }

    //  مسبح
    public function payPool($reservationId)
    {
        $reservation = Pool_reservation::findOrFail($reservationId);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['message' => 'يجب تأكيد الطلب أولاً قبل الدفع'], 400);
        }

        $invoice = $this->invoiceService->getInvoiceByItem('pool', $reservationId);

        if (!$invoice || $invoice->status === 'paid') {
            return response()->json(['error' => 'الفاتورة غير موجودة أو مدفوعة'], 404);
        }

        $session = $this->createStripeSession(
            'حجز مسبح',
            'عدد الأشخاص: ' . $reservation->number_of_people,
            $reservation->total_price,
            route('pool.payment.success', ['reservationId' => $reservation->id])
        );

        return response()->json(['url' => $session->url]);
    }

    public function paymentSuccessPool(Request $request,$reservationId)
    {
        $sessionId = $request->query('session_id');
        return $this->markInvoicePaid('pool', $reservationId,$sessionId);
    }

    //  صالة
    public function payHallBooking($bookingId)
    {
        $booking = HallBooking::findOrFail($bookingId);

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'يجب تأكيد الطلب أولاً قبل الدفع'], 400);
        }

        $invoice = $this->invoiceService->getInvoiceByItem('hall_bookings', $bookingId);

        if (!$invoice || $invoice->status === 'paid') {
            return response()->json(['error' => 'الفاتورة غير موجودة أو مدفوعة'], 404);
        }

        $session = $this->createStripeSession(
            'حجز صالة',
            'المناسبة: ' . $booking->occasion_type,
            $booking->price,
            route('hall.payment.success', ['bookingId' => $booking->id])
        );

        return response()->json(['url' => $session->url]);
    }

    public function paymentSuccessHall(Request $request,$bookingId)
    {
        $sessionId = $request->query('session_id');
        return $this->markInvoicePaid('hall_bookings', $bookingId,$sessionId);
    }

    //  مطعم
    public function payRestaurantOrder($orderId)
    {
        $order = RestaurantOrder::findOrFail($orderId);

        $invoice = $this->invoiceService->getInvoiceByItem('restaurant', $orderId);

        if (!$invoice || $invoice->status === 'paid') {
            return response()->json(['error' => 'الفاتورة غير موجودة أو مدفوعة'], 404);
        }

        $session = $this->createStripeSession(
            'طلب مطعم #' . $order->id,
            'تفاصيل الطلب',
            $order->total_price,
            route('restaurant.payment.success', ['orderId' => $order->id])
        );

        return response()->json(['url' => $session->url]);
    }

    public function paymentSuccessRestaurant(Request $request,$orderId)
    {
        $sessionId = $request->query('session_id');
        return $this->markInvoicePaid('restaurant', $orderId,$sessionId);
    }
}
