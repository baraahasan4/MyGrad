<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Pool_reservation;
use App\Models\Promotion;
use App\Models\Service_pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\InvoiceService;

class PoolController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function requestPoolReservation(Request $request)
    {
        $request->validate([
            'number_of_people' => 'required|integer|min:1',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|in:morning,evening',
        ]);

        DB::beginTransaction();

        try {
            $pricing = Service_pricing::where('service_type', 'pool')
            ->where('active', true)
            ->orderByDesc('date')
            ->first();

            if (!$pricing) {
                return response()->json(['error' => 'لا يوجد سعر فعّال حالياً.'], 400);
            }

            $numPeople = $request->number_of_people;
            $reservedPeople = Pool_reservation::where('date', $request->date)
            ->where('time', $request->time)
            ->where('status','confirmed')
            ->sum('number_of_people');

            if (($reservedPeople + $numPeople) > 40) {
                return response()->json([
                    'error' => 'لا يمكن تقديم الحجز. تم حجز الحد الأقصى للأشخاص في هذه الفترة (40 شخص).'
                ], 400);
            }

            $pricePerPerson = $pricing->price;
            $totalPrice = $pricePerPerson * $numPeople;

            // تطبيق الخصم إن وجد
            $promotion = Promotion::where('promotion_type', 'pool')
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('start_date')
            ->first();

            if ($promotion) {
                if ($promotion->discount_type === 'percentage') {
                    $discountAmount = $totalPrice * ($promotion->discount_value / 100);
                } else {
                    $discountAmount = $promotion->discount_value;
                }

                $totalPrice = max(0, $totalPrice - $discountAmount); // تجنب السالب
            }

            $reservation = Pool_reservation::create([
                'price_for_person' => $pricePerPerson,
                'number_of_people' => $numPeople,
                'total_price' => $totalPrice,
                'date' => $request->date,
                'time' => $request->time,
                'status' => 'pending',
                'user_id' => Auth::id(),
            ]);


            DB::commit();

            return response()->json([
                'message' => 'تم إرسال طلب الحجز ويحتاج إلى موافقة.',
                'reservation' => $reservation
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'حدث خطأ أثناء طلب الحجز.'], 500);
        }
    }

    public function approvePoolReservation($id)
    {
        DB::beginTransaction();

        try {
            $reservation = Pool_reservation::findOrFail($id);

            if ($reservation->status !== 'pending') {
                return response()->json(['error' => 'لا يمكن الموافقة على هذا الحجز.'], 400);
            }

            $confirmedPeople = Pool_reservation::where('date', $reservation->date)
            ->where('time', $reservation->time)
            ->where('status', 'confirmed')
            ->sum('number_of_people');

            if (($confirmedPeople + $reservation->number_of_people) > 40) {
                return response()->json([
                    'error' => 'لا يمكن تأكيد الحجز. العدد سيتجاوز 40 شخص في هذه الفترة.'
                ], 400);
            }

            $reservation->update([
                'status' => 'confirmed',
                'approved_by' => Auth::id(),
            ]);

            $this->invoiceService->createInvoice(
                'pool',
                $reservation->id,
                $reservation->total_price,
                $reservation->user_id,
                'فاتورة حجز مسبح لعدد ' . $reservation->number_of_people .
                ' شخص بتاريخ ' . $reservation->date .
                ' (' . ($reservation->time === 'morning' ? 'صباحية' : 'مسائية') . ')'
            );


            DB::commit();

            return response()->json(['message' => 'تمت الموافقة على الحجز بنجاح.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'فشل في تأكيد الحجز.'], 500);
        }
    }

    public function cancelPoolReservation($id)
    {
        DB::beginTransaction();

        try {
            $reservation = Pool_reservation::findOrFail($id);
            $user = Auth::user();

            if ($reservation->status === 'cancelled') {
                return response()->json(['error' => 'تم إلغاء هذا الحجز مسبقاً .'], 400);
            }

            if ($user->role === 'Guest' && $reservation->user_id !== $user->id) {
                return response()->json(['error' => 'غير مسموح لك بإلغاء هذا الحجز.'], 403);
            }

            $invoice = $this->invoiceService->getInvoiceByItem('pool', $reservation->id);

            if ($user->role === 'Receptionist') {
                if ($invoice && $invoice->status !== 'unpaid') {
                    return response()->json(['error' => 'لا يمكن إلغاء الحجز. الفاتورة مدفوعة.'], 400);
                }
            }

            $reservation->update(['status' => 'cancelled']);

            if ($invoice) {
                $invoice->update(['status' => 'cancelled']);
            }

            DB::commit();

            return response()->json(['message' => 'تم إلغاء الحجز بنجاح.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'حدث خطأ أثناء إلغاء الحجز.'], 500);
        }
    }

    public function reservePoolByReception(Request $request)
    {
        $request->validate([
            'number_of_people' => 'required|integer|min:1',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|in:morning,evening',
            'guest_name' => 'string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $pricing = Service_pricing::where('service_type', 'pool')
            ->where('active', true)
            ->orderByDesc('date')
            ->first();

            if (!$pricing) {
                return response()->json(['error' => 'لا يوجد سعر فعّال حالياً.'], 400);
            }

            $numPeople = $request->number_of_people;

            $confirmedPeople = Pool_reservation::where('date', $request->date)
            ->where('time', $request->time)
            ->where('status','confirmed')
            ->sum('number_of_people');

            if (($confirmedPeople + $numPeople) > 40) {
                return response()->json([
                    'error' => 'لا يمكن تأكيد الحجز. العدد سيتجاوز الحد الأقصى (40 شخص).'
                ], 400);
            }

            $pricePerPerson = $pricing->price;
            $totalPrice = $pricePerPerson * $numPeople;

            $promotion = Promotion::where('promotion_type', 'pool')
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('start_date')
            ->first();

            if ($promotion) {
                if ($promotion->discount_type === 'percentage') {
                    $discountAmount = $totalPrice * ($promotion->discount_value / 100);
                } else {
                    $discountAmount = $promotion->discount_value;
                }

                $totalPrice = max(0, $totalPrice - $discountAmount);
            }

            $reservation = Pool_reservation::create([
                'price_for_person' => $pricePerPerson,
                'number_of_people' => $numPeople,
                'total_price' => $totalPrice,
                'date' => $request->date,
                'time' => $request->time,
                'status' => 'confirmed',
                'guest_name' => $request->guest_name,
                'user_id' => Auth::id(),
                'approved_by' => Auth::id(),
            ]);

            $invoice = $this->invoiceService->createInvoice(
                itemType: 'pool',
                itemId: $reservation->id,
                amount: $totalPrice,
                userId: Auth::id(),
                description: 'فاتورة حجز مسبح لعدد ' . $reservation->number_of_people .
                ' شخص بتاريخ ' . $reservation->date .
                ' (' . ($reservation->time === 'morning' ? 'صباحية' : 'مسائية') . ')'
            );

            $invoice->update(['status' => 'paid']);

            DB::commit();

            return response()->json([
                'message' => 'تم حجز المسبح وتأكيده بنجاح.',
                'reservation' => $reservation,
                'invoice' => $invoice
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'فشل في إنشاء الحجز.'], 500);
        }
    }

    public function getPoolReservationsByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        try {
            $reservations = Pool_reservation::with('user:id,name,email')
            ->where('status', $request->status)
            ->orderByDesc('date')
            ->get();

            return response()->json([
                'status' => $request->status,
                'count' => $reservations->count(),
                'reservations' => $reservations
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب الحجوزات.' , $e], 500);
        }
    }

    public function getMyPoolReservationsByStatus(Request $request)
{
    $request->validate([
        'status' => 'required|in:pending,confirmed,cancelled',
    ]);

    try {
        $user = auth()->user();

        $reservations = Pool_reservation::where('user_id', $user->id)
            ->where('status', $request->status)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'status' => $request->status,
            'count' => $reservations->count(),
            'reservations' => $reservations
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'حدث خطأ أثناء جلب الحجوزات.'], 500);
    }
}


    public function checkPoolAvailability(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|in:morning,evening',
        ]);

        try {
            $reservedCount = Pool_reservation::where('date', $request->date)
            ->where('time', $request->time)
            ->whereIn('status', ['confirmed'])
            ->sum('number_of_people');

            $maxCapacity = 40;
            $availableSlots = max(0, $maxCapacity - $reservedCount);

            return response()->json([
                'date' => $request->date,
                'time' => $request->time === 'morning' ? 'صباحية' : 'مسائية',
                'available_slots' => $availableSlots,
                'is_available' => $availableSlots > 0
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء التحقق من التوافر.'], 500);
        }
    }

}
