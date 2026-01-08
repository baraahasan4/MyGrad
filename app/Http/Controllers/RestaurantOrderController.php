<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\MenuItem;
use App\Models\Invoice;
use App\Models\Promotion;
use App\Services\InvoiceService;
use App\Services\RestaurantOrderService;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrderItem;
use App\Models\Service_pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    public function RequestOrderByUser(Request $request, InvoiceService $invoiceService, RestaurantOrderService $restaurantOrderService)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:room,table',
            'preferred_time' => 'required|date',
            'menu_items' => 'required|array|min:1',
            'menu_items.*.id' => 'required|exists:menu_items,id',
            'menu_items.*.quantity' => 'required|integer|min:1',
            'table_or_room_number' => 'required|integer',
            'number_of_people' => 'sometimes|required_if:order_type,table|integer|min:1',
            'booked_duration' => 'required|integer|min:1',
        ]);

        try {
            $result = $restaurantOrderService->createOrder($validated, $invoiceService);

            return response()->json([
                'message' => 'تم إنشاء الطلب والفاتورة بنجاح',
                'order' => $result['order'],
                'original_price' => $result['original_price'],
                'discount_amount' => $result['discount_amount'],
                'final_price' => $result['final_price'],
                'discount_applied' => $result['discount_applied'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveOrderBySupervisor($id, RestaurantOrderService $service, InvoiceService $invoiceService)
    {
        try {
            $result = $service->approveOrder($id, $invoiceService);

            return response()->json([
                'success' => true,
                'message' => 'Order approved successfully and invoice created.',
                ...$result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    public function rejectOrderBySupervisor($id, RestaurantOrderService $service)
    {
        try {
            $result = $service->rejectOrder($id);

            return response()->json([
                'success' => true,
                ...$result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    public function cancelByUser($orderId, RestaurantOrderService $service)
    {
        try {
            $service->cancelOrderByUser($orderId);

            return response()->json([
                'message' => 'Your order has been cancelled successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }
    public function addMenuItem(Request $request, RestaurantOrderService $menuService)
    {
        // التحقق من الصلاحية
        if (auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'غير مصرح لك بإضافة الوجبات'], 403);
        }

        // التحقق من صحة البيانات
        $validated = $request->validate([
            'ar_name' => 'required|string|max:255',
            'en_name' => 'required|string|max:255',
            'ar_description' => 'nullable|string|max:1000',
            'en_description' => 'nullable|string|max:1000',
            // 'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|in:Appetizers,Main_Course,Desserts,Drinks',
            'price' => 'required|numeric|min:0',
        ]);

        $menuItem = $menuService->addMenuItem($validated, $request->file('photo'));

        return response()->json([
            'message' => 'تمت إضافة الوجبة إلى القائمة بنجاح',
            'menu_item' => $menuItem,
        ], 201);
    }

    public function ordersByStatus($status, RestaurantOrderService $restaurantOrderService)
    {
        return $restaurantOrderService->ordersByStatus($status);
    }

    public function getOrdersByDateRange(Request $request, RestaurantOrderService $restaurantOrderService)
    {
        return $restaurantOrderService->getOrdersByDateRange($request);
    }


    public function getAllOrders()
    {
        $orders = RestaurantOrder::with('user', 'orderItems.menuItem')->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getAllInvoices()
    {
        if (auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $invoices = Invoice::where('item_type','restaurant')->get();

        return response()->json([
            'success' => true,
            'invoices' => $invoices
        ]);
    }

    public function getUserOrders()
    {
        $userId = Auth::id();

        $orders = RestaurantOrder::with(['items.menuItem'])
            ->where('user_id', $userId)
            ->orderBy('preferred_time', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function invoicesByStatus($status)
    {
        if (auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validStatuses = ['unpaid','paid','cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid status provided.'], 400);
        }

        $invoice = Invoice::where('item_type','restaurant')->where('status', $status)->get();

        return response()->json([
            'success' => true,
            'orders' => $invoice
        ]);
    }

    public function invoicesByDateRange(Request $request)
    {
        if (auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $invoices = Invoice::where('item_type','restaurant')->whereBetween('date', [
            $request->start_date,
            $request->end_date
        ])->get();


        if ($invoices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد فواتير ضمن الفترة المحددة.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invoices' => $invoices
        ]);
    }

    public function getListMenuItems()
    {
        $menuItems = MenuItem::all();
        $MenuItems = $menuItems->map(function ($menuItem) {
            $photo = url($menuItem->photo);
            return [
                'id' => $menuItem->id,
                'ar_name' => $menuItem->ar_name,
                'en_name' => $menuItem->en_name,
                'ar_description' => $menuItem->ar_description,
                'en_description' => $menuItem->en_description,
                'photo' => $photo,
                'type' => $menuItem->type,
                'price' => $menuItem->price,
            ];
        });

        return response()->json($MenuItems);
    }

    public function getMenuItemByType($type)
    {
        $allowedTypes = ['Appetizers', 'Main_Course', 'Desserts', 'Drinks'];

        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'message' => 'Invalid type provided.'
            ], 400);
        }

        $items = MenuItem::where('type', $type)->get();
        $items = $items->map(function ($menuItem) {
            $photo = url($menuItem->photo);
            return [
                'id' => $menuItem->id,
                'ar_name' => $menuItem->ar_name,
                'en_name' => $menuItem->en_name,
                'ar_description' => $menuItem->ar_description,
                'en_description' => $menuItem->en_description,
                'photo' => $photo,
                'type' => $menuItem->type,
                'price' => $menuItem->price,
            ];
        });
        return response()->json($items);
    }


    public function deleteMenuItem($id)
    {
        if (!auth()->check() || auth()->user()->role !== 'Restaurant_Supervisor') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Menu item not found.'], 404);
        }

        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully.'], 200);
    }

    public function RequestOrderBySupervisor(Request $request, RestaurantOrderService $restaurantOrderService, InvoiceService $invoiceService)
    {
        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'order_type' => 'required|in:room,table',
            'preferred_time' => 'required|date',
            'menu_items' => 'required|array|min:1',
            'menu_items.*.id' => 'required|exists:menu_items,id',
            'menu_items.*.quantity' => 'required|integer|min:1',
            'table_or_room_number' => 'required|integer',
            'number_of_people' => 'sometimes|required_if:order_type,table|integer|min:1',
            'booked_duration' => 'required|integer|min:1',
        ]);

        try {
            $result = $restaurantOrderService->createOrderBySupervisor($validated, $invoiceService);

            return response()->json([
                'message' => 'تم إنشاء الطلب باسم الضيف بنجاح.',
                'order' => $result['order'],
                'original_price' => $result['original_price'],
                'discount_amount' => $result['discount_amount'],
                'final_price' => $result['final_price'],
                'discount_applied' => $result['discount_applied'],
                'invoice' => $result['invoice'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الطلب.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }






}
