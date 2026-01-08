<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantTable;
use App\Models\Service_pricing;
use App\Models\Promotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class RestaurantOrderService
{
    public function createOrder(array $validated, $invoiceService): array
    {
        DB::beginTransaction();

        try {
            $preferredTime = Carbon::parse($validated['preferred_time']);
            if ($preferredTime->isBefore(now())) {
                throw new \Exception('لا يمكن تحديد وقت حجز في الماضي. يرجى اختيار وقت في المستقبل.');
            }

            $totalMenuPrice = 0;
            foreach ($validated['menu_items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['id']);
                $totalMenuPrice += $menuItem->price * $item['quantity'];
            }

            $totalTablePrice = 0;
            $reservationEndTime = null;

            // حجز الطاولة إن وجدت
            if ($validated['order_type'] === 'table') {

                if (isset($validated['number_of_people']) && $validated['number_of_people'] > 10) {
                    throw new \Exception('عدد الأشخاص لا يمكن أن يتجاوز 10 للحجز على الطاولة.');
                }

                $table = RestaurantTable::where('table_number', $validated['table_or_room_number'])
                    ->where('status', 'available')->first();

                if (!$table) {
                    throw new Exception('الطاولة المحددة غير متاحة.');
                }

                $preferredTime = Carbon::parse($validated['preferred_time']);
                $reservationEndTime = $preferredTime->copy()->addHours($validated['booked_duration']);

                $table->status = 'booked';
                $table->save();

                $pricing = Service_pricing::where('service_type', 'restaurant')
                    ->where('active', true)
                    ->orderBy('date', 'desc')
                    ->first();

                if (!$pricing) {
                    throw new Exception('لم يتم تحديد سعر خدمة المطعم.');
                }

                // $totalTablePrice = $validated['number_of_people'] * $pricing->price;
                $totalTablePrice = $pricing->price;
            }

            $originalTotalPrice = $totalMenuPrice + $totalTablePrice;
            $totalPrice = $originalTotalPrice;
            $discountAmount = 0;

            // الخصم إن وجد
            $promotion = Promotion::where('promotion_type', 'restaurant')
                ->where('active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if ($promotion) {
                $discountAmount = $promotion->discount_type === 'percentage'
                    ? $totalPrice * ($promotion->discount_value / 100)
                    : $promotion->discount_value;

                $discountAmount = min($discountAmount, $totalPrice);
                $totalPrice -= $discountAmount;
            }

            $order = RestaurantOrder::create([
                'order_type' => $validated['order_type'],
                'preferred_time' => $validated['preferred_time'],
                'user_id' => Auth::id(),
                'status' => 'pending',
                'table_number' => $validated['order_type'] === 'table' ? $validated['table_or_room_number'] : null,
                'room_number' => $validated['order_type'] === 'room' ? $validated['table_or_room_number'] : null,
                'number_of_people' => $validated['order_type'] === 'table' ? $validated['number_of_people'] : null,
                'table_price' => $totalTablePrice,
                'total_price' => $totalPrice,
                'booked_duration' => $validated['booked_duration'],
                'reservation_end_time' => $reservationEndTime,
                'approved_or_rejected_by' => null,
            ]);

            foreach ($validated['menu_items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['id']);
                RestaurantOrderItem::create([
                    'restaurant_order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $item['quantity'],
                    'total_price' => $menuItem->price * $item['quantity'],
                ]);
            }

            $invoiceService->createInvoice(
                'restaurant',
                $order->id,
                $totalPrice,
                Auth::id(),
                'فاتورة طلب مطعم رقم ' . $order->id
            );

            DB::commit();

            return [
                'order' => $order,
                'original_price' => $originalTotalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $totalPrice,
                'discount_applied' => (bool) $promotion,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveOrder(int $orderId, InvoiceService $invoiceService): array
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'Restaurant_Supervisor') {
            throw new \Exception('Unauthorized. You are not allowed to approve orders.', 403);
        }

        $order = RestaurantOrder::findOrFail($orderId);

        if ($order->status !== 'pending') {
            throw new \Exception('This order cannot be approved because it is already ' . $order->status, 400);
        }

        $now = now();
        $promotion = Promotion::where('promotion_type', 'restaurant')
            ->where('active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        $originalPrice = $order->total_price;
        $discountValue = 0;

        if ($promotion) {
            if ($promotion->discount_type === 'percentage') {
                $discountValue = $originalPrice * ($promotion->discount_value / 100);
            } elseif ($promotion->discount_type === 'fixed') {
                $discountValue = $promotion->discount_value;
            }

            $discountValue = min($discountValue, $originalPrice);
        }

        $finalPrice = $originalPrice - $discountValue;

        $order->status = 'preparing';
        $order->approved_or_rejected_by = $user->id;
        $order->save();

        $invoice = $invoiceService->createInvoice(
            'restaurant',
            $order->id,
            $finalPrice,
            $order->user_id,
            'Restaurant Order ' . $order->id
        );

        return [
            'order' => $order,
            'invoice' => $invoice,
            'promotion_applied' => (bool) $promotion,
            'original_price' => $originalPrice,
            'discount_value' => $discountValue,
            'final_price' => $finalPrice,
        ];
    }

    public function rejectOrder(int $orderId): array
    {
        $order = RestaurantOrder::find($orderId);

        if (!$order) {
            throw new \Exception('Order not found.', 404);
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'Restaurant_Supervisor') {
            throw new \Exception('Unauthorized.', 403);
        }

        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be rejected.', 400);
        }

        $order->status = 'cancelled';
        $order->approved_or_rejected_by = $user->id;
        $order->save();

        return [
            'message' => 'Order has been rejected and cancelled.',
            'order' => $order,
        ];
    }

    public function cancelOrderByUser(int $orderId): void
    {
        $user = Auth::user();

        $order = RestaurantOrder::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            throw new \Exception('Order not found or does not belong to the user.', 404);
        }

        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be cancelled.', 400);
        }

        $order->status = 'cancelled';
        $order->approved_or_rejected_by = $user->id;
        $order->save();
    }

    public function addMenuItem(array $validated, $photoFile = null): MenuItem
    {
        if ($photoFile) {
            $imageName = time() . '.' . $photoFile->getClientOriginalExtension();
            $photoFile->move(public_path('images/menu'), $imageName);
            $validated['photo'] = 'images/menu/' . $imageName;
        }

        return MenuItem::create($validated);
    }

    public function ordersByStatus($status)
    {
        if (auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validStatuses = ['pending', 'preparing', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid status provided.'], 400);
        }

        $orders = RestaurantOrder::where('status', $status)->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    public function getOrdersByDateRange($request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $orders = RestaurantOrder::with('user', 'orderItems.menuItem')
            ->whereBetween('preferred_time', [$request->start_date, $request->end_date])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد طلبات ضمن الفترة المحددة.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $orders
        ]);
    }

    public function createOrderBySupervisor(array $validated, InvoiceService $invoiceService): array
{
    DB::beginTransaction();

    try {
        $preferredTime = Carbon::parse($validated['preferred_time']);
        if ($preferredTime->isBefore(now())) {
            throw new \Exception('تاريخ ووقت الطلب يجب أن يكونا في المستقبل.');
        }

        $totalMenuPrice = 0;
        foreach ($validated['menu_items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['id']);
            $totalMenuPrice += $menuItem->price * $item['quantity'];
        }

        $totalTablePrice = 0;
        $reservationEndTime = null;

        if ($validated['order_type'] === 'table') {
            $table = RestaurantTable::where('table_number', $validated['table_or_room_number'])
                ->where('status', 'available')->first();

            if (!$table) {
                throw new Exception('الطاولة المحددة غير متاحة.');
            }

            $reservationEndTime = $preferredTime->copy()->addHours($validated['booked_duration']);

            $table->status = 'booked';
            $table->save();

            $pricing = Service_pricing::where('service_type', 'restaurant')
                ->where('active', true)
                ->orderBy('date', 'desc')
                ->first();

            if (!$pricing) {
                throw new Exception('لم يتم تحديد سعر خدمة المطعم.');
            }

            $totalTablePrice = $validated['number_of_people'] * $pricing->price;
        }

        $originalTotalPrice = $totalMenuPrice + $totalTablePrice;
        $totalPrice = $originalTotalPrice;
        $discountAmount = 0;

        $promotion = Promotion::where('promotion_type', 'restaurant')
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($promotion) {
            $discountAmount = $promotion->discount_type === 'percentage'
                ? $totalPrice * ($promotion->discount_value / 100)
                : $promotion->discount_value;

            $discountAmount = min($discountAmount, $totalPrice);
            $totalPrice -= $discountAmount;
        }

        $order = RestaurantOrder::create([
            'order_type' => $validated['order_type'],
            'preferred_time' => $validated['preferred_time'],
            'user_id' => null,
            'guest_name' => $validated['guest_name'],
            'status' => 'preparing',
            'table_number' => $validated['order_type'] === 'table' ? $validated['table_or_room_number'] : null,
            'room_number' => $validated['order_type'] === 'room' ? $validated['table_or_room_number'] : null,
            'number_of_people' => $validated['order_type'] === 'table' ? $validated['number_of_people'] : null,
            'table_price' => $totalTablePrice,
            'total_price' => $totalPrice,
            'booked_duration' => $validated['booked_duration'],
            'reservation_end_time' => $reservationEndTime,
            'approved_or_rejected_by' => Auth::id(),
        ]); $order->save();

        foreach ($validated['menu_items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['id']);
            RestaurantOrderItem::create([
                'restaurant_order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'total_price' => $menuItem->price * $item['quantity'],
            ]);

        }

            $invoice = $invoiceService->createInvoice(
                itemType: 'restaurant',
                itemId: $order->id,
                amount: $totalPrice,
                userId: Auth::id(),
                description: 'فاتورة طلب مطعم باسم الضيف ' . $validated['guest_name']
            );
            $invoice->status = 'paid';
            $invoice->save();

        DB::commit();

        return [
            'order' => $order->fresh(),
            'original_price' => $originalTotalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $totalPrice,
            'discount_applied' => (bool) $promotion,
            'invoice' => $invoice,

        ];
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
}


}
