<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Massage_request;
use App\Models\Promotion;
use App\Models\Service_pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\InvoiceService;


class MassageService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected EmployeeAvailabilityService $availabilityService
    ) {}

    public function requestMassage(array $validatedData)
    {
        return DB::transaction(function () use ($validatedData) {
            $userId = Auth::id();
            $preferredTime = Carbon::parse($validatedData['preferred_time']);

            $duplicateUserRequest = Massage_request::where('user_id', $userId)
                ->where('preferred_time', $preferredTime)
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->exists();

            if ($duplicateUserRequest) {
                return response()->json(['message' => 'لقد قمت بالفعل بطلب جلسة مساج في هذا الوقت.'], 400);
            }

            $availableEmployeeId = $this->availabilityService->getAvailableEmployeeId($preferredTime, $validatedData['gender']);

            if (!$availableEmployeeId) {
                return response()->json(['message' => 'لا يوجد موظف متاح في هذا التوقيت.'], 400);
            }

            $pricing = Service_pricing::where('service_type', 'massage')
                ->where('active', true)
                ->where('date', '<=', $preferredTime)
                ->orderByDesc('date')
                ->lockForUpdate()
                ->first();

            if (!$pricing) {
                return response()->json(['message' => 'لا توجد تسعيرة متاحة لجلسة المساج في هذا التوقيت.'], 400);
            }

            $finalPrice = $this->applyPromotion($pricing->price, $preferredTime);

            $massageRequest = Massage_request::create([
                'user_id' => $userId,
                'preferred_time' => $preferredTime,
                'price' => round($finalPrice, 2),
                'gender' => $validatedData['gender'],
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'تم إرسال طلب المساج بنجاح. بانتظار التأكيد.',
                'data' => $massageRequest
            ], 201);
        });
    }

    private function applyPromotion(float $originalPrice, Carbon $preferredTime): float
    {
        $promotion = Promotion::where('promotion_type', 'massage')
            ->where('active', true)
            ->where('start_date', '<=', $preferredTime)
            ->where('end_date', '>=', $preferredTime)
            ->lockForUpdate()
            ->first();

        $finalPrice = $originalPrice;

        if ($promotion) {
            if ($promotion->discount_type === 'percentage') {
                $finalPrice -= ($originalPrice * ($promotion->discount_value / 100));
            } elseif ($promotion->discount_type === 'fixed') {
                $finalPrice -= $promotion->discount_value;
            }
            $finalPrice = max($finalPrice, 0);
        }

        return $finalPrice;
    }

    public function approveMassageRequest(int $id)
    {
        return DB::transaction(function () use ($id) {
            $user = Auth::user();
            $massageRequest = Massage_request::lockForUpdate()->find($id);

            if (!$massageRequest) {
                return response()->json(['message' => 'طلب المساج غير موجود.'], 404);
            }

            if ($massageRequest->status !== 'pending') {
                return response()->json(['message' => 'لا يمكن الموافقة على هذا الطلب لأنه ليس في حالة انتظار.'], 400);
            }

            $preferredTime = Carbon::parse($massageRequest->preferred_time);
            $availableEmployeeId = $this->availabilityService->getAvailableEmployeeId($preferredTime, $massageRequest->gender);

            if (!$availableEmployeeId) {
                return response()->json(['message' => 'لا يوجد موظف متاح في هذا التوقيت.'], 400);
            }

            $massageRequest->status = 'confirmed';
            $massageRequest->approved_by = $user->id;
            $massageRequest->employee_id = $availableEmployeeId;
            $massageRequest->save();

            $this->invoiceService->createInvoice(
                'massage',
                $massageRequest->id,
                $massageRequest->price,
                $massageRequest->user_id,
                'فاتورة حجز مساج بتاريخ #' . $massageRequest->preferred_time
            );

            return response()->json([
                'message' => 'تمت الموافقة وتخصيص الموظف بنجاح.',
                'data' => $massageRequest
            ]);
        });
    }

    public function cancelByReception(int $id)
    {
        return $this->cancelRequest($id, true);
    }

    public function cancelByUser(int $id)
    {
        return $this->cancelRequest($id, false);
    }

    private function cancelRequest(int $id, bool $isReception)
    {
        return DB::transaction(function () use ($id, $isReception) {
            $user = Auth::user();

            $query = Massage_request::where('id', $id);
            if (!$isReception) {
                $query->where('user_id', $user->id);
            }

            $request = $query->lockForUpdate()->first();

            if (!$request) {
                return response()->json(['message' => 'طلب المساج غير موجود' . ($isReception ? '.' : ' أو لا يخصك.')], 404);
            }

            if ($request->status === 'cancelled') {
                return response()->json(['message' => 'هذا الطلب ملغى بالفعل.'], 400);
            }

            if (Carbon::parse($request->preferred_time)->isPast()) {
                return response()->json(['message' => 'لا يمكن إلغاء الطلب بعد مرور وقت الجلسة.'], 400);
            }

            $invoice = Invoice::where('item_type', 'massage')
                ->where('item_id', $request->id)
                ->lockForUpdate()
                ->first();

            if ($isReception && $invoice && $invoice->status === 'paid') {
                return response()->json(['message' => 'لا يمكن إلغاء الطلب لأن الفاتورة مدفوعة.'], 400);
            }

            $request->update([
                'status' => 'cancelled',
                'approved_by' => $isReception ? $user->id : null,
            ]);

            if ($invoice && $invoice->status !== 'cancelled') {
                $invoice->status = 'cancelled';
                $invoice->save();
            }

            return response()->json([
                'message' => $isReception
                    ? 'تم إلغاء طلب المساج بنجاح من قبل الريسبشن.'
                    : 'تم إلغاء طلب المساج بنجاح.'
            ]);
        });
    }

    public function bookMassageByReception(array $data)
    {
        return DB::transaction(function () use ($data) {
            $preferredTime = Carbon::parse($data['preferred_time']);
            $userId = auth()->id();
            $guestName = $data['guest_name'] ?? null;

            $duplicate = Massage_request::where('user_id', $userId)
            ->where('preferred_time', $preferredTime)
            ->whereIn('status', ['pending', 'confirmed'])
            ->lockForUpdate()
            ->exists();

            if ($duplicate) {
                return response()->json(['message' => 'يوجد بالفعل حجز مساج لهذا المستخدم في هذا الوقت.'], 400);
            }

            // التأكد من وجود موظف متاح
            $availableEmployeeId = $this->availabilityService->getAvailableEmployeeId($preferredTime, $data['gender']);
            if (!$availableEmployeeId) {
                return response()->json(['message' => 'لا يوجد موظف متاح في هذا التوقيت.'], 400);
            }

            // جلب السعر
            $pricing = Service_pricing::where('service_type', 'massage')
            ->where('active', true)
            ->where('date', '<=', $preferredTime)
            ->orderByDesc('date')
            ->lockForUpdate()
            ->first();

            if (!$pricing) {
                return response()->json(['message' => 'لا توجد تسعيرة متاحة لجلسة المساج في هذا التوقيت.'], 400);
            }

            $finalPrice = $this->applyPromotion($pricing->price, $preferredTime);

            // إنشاء الحجز مباشرة
            $massageRequest = Massage_request::create([
                'user_id' => $userId,
                'preferred_time' => $preferredTime,
                'price' => round($finalPrice, 2),
                'gender' => $data['gender'],
                'status' => 'confirmed',
                'approved_by' => Auth::id(),
                'guest_name' => $guestName,
                'employee_id' => $availableEmployeeId,
            ]);

            // إنشاء الفاتورة كمدفوعة
            $invoice = $this->invoiceService->createInvoice(
                itemType: 'massage',
                itemId: $massageRequest->id,
                amount: $massageRequest->price,
                userId: $userId,
                description: 'فاتورة جلسة مساج بتاريخ ' . $preferredTime
            );
            $invoice->status = 'paid';
            $invoice->save();

            return response()->json([
                'message' => 'تم حجز جلسة المساج بنجاح وتأكيدها مباشرة.',
                'data' => $massageRequest
            ]);
        });
    }

    public function getMassageRequests(?string $status = null, ?int $userId = null, bool $withRelations = false)
    {
        $query = Massage_request::query();

        if ($withRelations) {
            $query->with(['user:id,name,email,phone', 'employee:id,name,email,phone,gender']);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('preferred_time')->get();
    }
}
