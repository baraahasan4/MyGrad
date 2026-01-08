<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Promotion;
use App\Models\RestaurantOrder;
use App\Models\Room;
use App\Models\Room_booking;
use App\Models\RoomImage;
use App\Models\Service_pricing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class AdminController extends Controller
{
    public function AddRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|unique:rooms,number',
            'status' => 'required|in:available,booked',
            'room_type_id' => 'required|exists:room_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {

            $room = Room::create([
                'number' => $request->number,
                'status' => $request->status,
                'room_type_id' => $request->room_type_id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Room created successfully!',]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred while creating the room.']);
        }
    }

    public function addRoomImage(Request $request, $room_id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png',
            'image_type' => 'required|in:normal,panorama',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $room = Room::findOrFail($room_id);

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/rooms'), $imageName);
            $imagePath = 'images/rooms/' . $imageName;

            RoomImage::create([
                'room_id' => $room->id,
                'image_path' => $imagePath,
                'image_type' => $request->image_type,
            ]);

            DB::commit();

            return response()->json(['message' => 'Room image uploaded successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($imagePath) && File::exists(public_path($imagePath))) {
                File::delete(public_path($imagePath));
            }

            return response()->json([
                'message' => 'An error occurred while uploading the image.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRoom(Request $request, $id)
    {
        $request->validate([
            'number' => 'required|integer|unique:rooms,number,' . $id,
            'status' => 'required|in:available,booked',
            'room_type_id' => 'required|exists:room_types,id',
        ]);

        $room = Room::find($id);

        if (!$room) {
            return response()->json(['message' => 'الغرفة غير موجودة.'], 404);
        }

        $room->update([
            'number' => $request->number,
            'status' => $request->status,
            'room_type_id' => $request->room_type_id,
        ]);

        return response()->json([
            'message' => 'تم تعديل بيانات الغرفة بنجاح.',
            'room' => $room
        ]);
    }

    public function addEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email|unique:users,email',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'birthday' => 'required|date',
            'role' => 'required|in:Receptionist,Restaurant_Supervisor,General',
            'job_title' => 'required_if:role,General|string|max:255',
            'employment_history' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'housing' => 'required|string|max:255',
            'password' => 'required_unless:role,General|min:6|confirmed',
        ]);

        return DB::transaction(function () use ($request) {
            $adminId = Auth::id();

            if ($request->role !== 'General') {
                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone' => $request->phone,
                    'role' => $request->role,
                ]);
            }

            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birthday' => $request->birthday,
                'role' => $request->role,
                'job_title' => $request->job_title,
                'employment_history' => $request->employment_history,
                'salary' => $request->salary,
                'housing' => $request->housing,
                'user_id' => $adminId,
            ]);

            return response()->json([
                'message' => 'تم إضافة الموظف بنجاح.',
                'employee' => $employee
            ], 201);
        });
    }

    public function updateEmployee(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $id . '|unique:users,email',
            'phone' => 'required|string|max:20|unique:employees,phone,' . $id . '|unique:users,phone',
            'gender' => 'required|in:male,female',
            'birthday' => 'required|date',
            'role' => 'required|in:Receptionist,Restaurant_Supervisor,General',
            'job_title' => 'nullable|string|max:255',
            'employment_history' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'housing' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $employee = Employee::findOrFail($id);

            $employee->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birthday' => $request->birthday,
                'role' => $request->role,
                'job_title' => $request->role === 'General' ? $request->job_title : null,
                'employment_history' => $request->employment_history,
                'salary' => $request->salary,
                'housing' => $request->housing,
            ]);

            $user = User::where('email', $employee->email)->first();
            if ($user) {
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->name = $request->name;
                $user->save();
            }

            return response()->json([
                'message' => 'تم تعديل بيانات الموظف بنجاح.',
                'employee' => $employee
            ]);
        });
    }

    public function deleteEmployee($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'الموظف غير موجود.'], 404);
        }

        $user = User::where('email', $employee->email)->first();

        DB::transaction(function () use ($employee, $user) {
            if ($user) {
                $user->delete();
            }
            $employee->delete();
        });

        return response()->json(['message' => 'تم حذف الموظف بنجاح.']);
    }

    public function getEmployees(Request $request)
    {
        $query = Employee::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $employees = $query->get();

        return response()->json([
            'message' => 'تم جلب الموظفين بنجاح.',
            'data' => $employees
        ]);
    }

    public function getCurrentGuests()
    {
        $now = Carbon::now();

        $currentGuests = Room_booking::with(['user', 'room'])
        ->where('status', 'confirmed')
        ->where('check_in', '<=', $now)
        ->where('check_out', '>', $now)
        ->get();

        $result = $currentGuests->map(function ($booking) {
            $guestName = $booking->guest_name ?? $booking->user->name;

            return [
                'guest_name' => $guestName,
                'guest_email' => $booking->user->email,
                'room_number' => $booking->room?->number,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
            ];
        });

        return response()->json([
            'message' => 'تم جلب النزلاء الحاليين بنجاح.',
            'data' => $result
        ]);
    }

    public function getGuestBookingArchive()
    {
        $confirmedBookings = Room_booking::with([
            'user:id,name,email,phone',
            'room:id,number,room_type_id',
            'room.roomType:id,type_name_ar,type_name_en'
            ])
            ->where('status', 'confirmed')
            ->orderBy('check_in')
            ->get();

            $grouped = $confirmedBookings->groupBy('user.id')->map(function ($bookings) {
                $user = $bookings->first()->user;

                return [
                    'guest' => [
                        'name' => $bookings->first()->guest_name ?? $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'bookings' => $bookings->map(function ($booking) {
                        return [
                            'room_number' => $booking->room?->number,
                            'room_type_ar' => $booking->roomType?->type_name_ar,
                            'room_type_en' => $booking->roomType?->type_name_en,
                            'check_in' => $booking->check_in,
                            'check_out' => $booking->check_out,
                            'total_price' => $booking->total_price,
                        ];
                    })->values()
                ];
            })->values();

            return response()->json($grouped);
    }


    public function getRooms()
    {
        $rooms = Room::select('id', 'number', 'status', 'room_type_id')
        ->with([
            'roomType:id,price,description_en,description_ar',
            'images'
            ])
            ->get()
            ->transform(function ($room) {
                $room->images->transform(fn($image) => $this->formatImage($image));
                return $room;
            });

            return response()->json($rooms);
    }

    private function formatImage($image)
    {
        $image->image_path = url($image->image_path);
        return $image;
    }

    public function getOverviewStats()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todayCheckIn = Room_booking::where('status', 'confirmed')
        ->whereDate('check_in', $today)->count();

        $monthCheckIn = Room_booking::where('status', 'confirmed')
        ->whereBetween('check_in', [$startOfMonth, now()])->count();

        $totalCheckIn = Room_booking::where('status', 'confirmed')->count();

        $availableRooms = Room::where('status', 'available')->count();

        $occupiedRooms = Room::where('status', 'booked')->count();

        return response()->json([
            'today_checkin' => $todayCheckIn,
            'month_checkin' => $monthCheckIn,
            'total_checkin' => $totalCheckIn,
            'available_rooms' => $availableRooms,
            'occupied_rooms' => $occupiedRooms,
        ]);
    }

    public function getRevenueStats()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        $dailyRevenue = Invoice::where('status', 'paid')
        ->whereDate('date', $today)
        ->sum('price');

        $weeklyRevenue = Invoice::where('status', 'paid')
        ->whereBetween('date', [$startOfWeek, now()])
        ->sum('price');

        $monthlyRevenue = Invoice::where('status', 'paid')
        ->whereBetween('date', [$startOfMonth, now()])
        ->sum('price');

        $servicesRevenue = [
            'massage' => Invoice::where('status', 'paid')->where('item_type', 'massage')->sum('price'),
            'pool' => Invoice::where('status', 'paid')->where('item_type', 'pool')->sum('price'),
        ];

        return response()->json([
            'revenue_today' => $dailyRevenue,
            'revenue_week' => $weeklyRevenue,
            'revenue_month' => $monthlyRevenue,
            'services_revenue' => $servicesRevenue
        ]);
    }

    public function getOccupancyStatistics()
    {
        $months = [];
        $totalRooms = Room::count();

        for ($i = 0; $i < 12; $i++) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $daysInMonth = $monthStart->daysInMonth;

            $bookings = Room_booking::where('status', 'confirmed')
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('check_in', [$monthStart, $monthEnd])
                ->orWhereBetween('check_out', [$monthStart, $monthEnd])
                ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('check_in', '<', $monthStart)
                    ->where('check_out', '>', $monthEnd);
                });
            })
            ->get();

            $occupiedDays = 0;

            foreach ($bookings as $booking) {
                $checkIn = Carbon::parse($booking->check_in)->max($monthStart);
                $checkOut = Carbon::parse($booking->check_out)->min($monthEnd);
                $occupiedDays += $checkOut->diffInDays($checkIn) + 1;
            }

            $maxAvailableDays = $totalRooms * $daysInMonth;
            $occupancyRate = $maxAvailableDays > 0
            ? round(($occupiedDays / $maxAvailableDays) * 100, 2)
            : 0;

            $months[] = [
                'month' => $monthStart->format('F Y'),
                'occupancy_rate' => $occupancyRate
            ];
        }

        return response()->json([
            'occupancy_statistics' => $months
        ]);
    }

    public function getRestaurantWeeklyStats()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weeklyRevenue = DB::table('invoices')
        ->where('status', 'paid')
        ->where('item_type', 'restaurant')
        ->whereBetween('date', [$startOfWeek, $endOfWeek])
        ->sum('price');

        $roomOrders = DB::table('restaurant_orders')
        ->where('order_type', 'room')
        ->where('status', 'preparing')
        ->whereBetween('preferred_time', [$startOfWeek, $endOfWeek])
        ->count();

        $tableOrders = DB::table('restaurant_orders')
        ->where('order_type', 'table')
        ->where('status', 'preparing')
        ->whereBetween('preferred_time', [$startOfWeek, $endOfWeek])
        ->count();

        return response()->json([
            'weekly_revenue' => $weeklyRevenue,
            'room_orders' => $roomOrders,
            'table_orders' => $tableOrders,
        ]);
    }

    public function addServicePrice(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:hall_booking,massage,pool,restaurant',
            'price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            Service_pricing::where('service_type', $validated['service_type'])
            ->update(['active' => false]);

            $newPrice = Service_pricing::create([
                'service_type' => $validated['service_type'],
                'price' => $validated['price'],
                'date' => now(),
                'active' => true,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'تم إضافة السعر وتفعيله بنجاح.',
                'data' => $newPrice,
            ]);
        });
    }

    public function activateOldServicePrice(int $PriceId)
    {
        return DB::transaction(function () use ($PriceId) {
            $newPrice = Service_pricing::findOrFail($PriceId);

            Service_pricing::where('service_type', $newPrice->service_type)
            ->where('id', '!=', $PriceId)
            ->update(['active' => false]);

            $newPrice->active = true;
            $newPrice->save();

            return response()->json([
                'message' => 'تم تفعيل السعر الجديد وإلغاء تفعيل الأسعار السابقة.',
                'activated_price' => $newPrice
            ]);
        });
    }

    public function getServicePriceByType($serviceType)
    {
        $validTypes = ['hall_booking', 'massage', 'pool', 'restaurant'];

        if (!in_array($serviceType, $validTypes)) {
            return response()->json(['message' => 'نوع الخدمة غير صالح.'], 400);
        }

        $prices = Service_Pricing::where('service_type', $serviceType)
        ->orderByDesc('date')
        ->get();

        return response()->json([
            'service_type' => $serviceType,
            'prices' => $prices,
        ]);
    }

    public function deleteServicePrice($PriceId)
    {
        $price = Service_pricing::find($PriceId);

        if (!$price) {
            return response()->json(['message' => 'السعر غير موجود.'], 404);
        }

        $price->delete();

        return response()->json(['message' => 'تم حذف السعر بنجاح.']);
    }

    public function addPromotion(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'promotion_type' => 'required|in:BookRoom,massage,pool,restaurant,hall_booking',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        Promotion::where('promotion_type', $validated['promotion_type'])
        ->update(['active' => false]);

        $promotion = Promotion::create([
            ...$validated,
            'active' => true,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'تمت إضافة العرض وتفعيله.',
            'promotion' => $promotion
        ]);
    }

    public function activateOldPromotion($id)
    {
        $promotion = Promotion::findOrFail($id);

        Promotion::where('promotion_type', $promotion->promotion_type)
        ->update(['active' => false]);

        $promotion->active = true;
        $promotion->save();

        return response()->json(['message' => 'تم تفعيل العرض بنجاح.']);
    }

    public function getPromotionsByType($promotion_type)
    {
        $validTypes = ['BookRoom','massage','pool','restaurant','hall_booking'];

        if (!in_array($promotion_type, $validTypes)) {
            return response()->json(['message' => 'نوع الخدمة غير صالح.'], 400);
        }

        $promotions = Promotion::where('promotion_type', $promotion_type)
        ->get();

        return response()->json([
            'promotions' => $promotions,
        ]);
    }

    public function deletePromotion($id)
    {
        $promotion = Promotion::find($id);

        if (!$promotion) {
            return response()->json(['message' => 'العرض غير موجود.'], 404);
        }

        $promotion->delete();

        return response()->json(['message' => 'تم حذف العرض بنجاح.']);
    }

    public function getMonthlyRevenueReport()
{
    $monthlyRevenue = [];

    // احضر آخر 12 شهر
    for ($i = 0; $i < 12; $i++) {
        $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
        $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

        // الفواتير المدفوعة ضمن هذا الشهر
        $invoices = Invoice::where('status', 'paid')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        // احسب الإيرادات حسب نوع العنصر
        $roomRevenue = $invoices->where('item_type', 'room_booking')->sum('price');
        $restaurantRevenue = $invoices->where('item_type', 'restaurant')->sum('price');
        $poolRevenue = $invoices->where('item_type', 'pool')->sum('price');
        $massageRevenue = $invoices->where('item_type', 'massage')->sum('price');
        $hallRevenue = $invoices->where('item_type', 'hall')->sum('price');

        $total = $roomRevenue + $restaurantRevenue + $poolRevenue + $massageRevenue + $hallRevenue;

        $monthlyRevenue[] = [
            'month' => $monthStart->format('F Y'),
            'room_revenue' => $roomRevenue,
            'restaurant_revenue' => $restaurantRevenue,
            'pool_revenue' => $poolRevenue,
            'massage_revenue' => $massageRevenue,
            'hall_revenue' => $hallRevenue,
            'total_revenue' => $total,
        ];
    }

    return response()->json([
        'monthly_revenue_report' => $monthlyRevenue
    ]);
}

public function getInvoiceReport()
{
    $statuses = ['paid', 'unpaid', 'cancelled'];
    $types = ['room_booking', 'restaurant', 'pool', 'massage', 'hall'];

    $report = [];

    foreach ($statuses as $status) {
        $data = ['status' => $status, 'total_amount' => 0, 'count' => 0, 'by_type' => []];

        foreach ($types as $type) {
            $invoices = Invoice::where('status', $status)
                ->where('item_type', $type)
                ->get();

            $typeTotal = $invoices->sum('price');
            $typeCount = $invoices->count();

            $data['by_type'][] = [
                'type' => $type,
                'count' => $typeCount,
                'amount' => $typeTotal,
            ];

            $data['total_amount'] += $typeTotal;
            $data['count'] += $typeCount;
        }

        $report[] = $data;
    }

    return response()->json(['invoice_report' => $report]);
}

public function getRoomOccupancyReport()
{
    $totalRooms = Room::count();
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();
    $daysInMonth = $startOfMonth->daysInMonth;

    // الحجوزات المؤكدة ضمن الشهر الحالي
    $bookings = Room_booking::where('status', 'confirmed')
        ->where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('check_in', [$startOfMonth, $endOfMonth])
              ->orWhereBetween('check_out', [$startOfMonth, $endOfMonth])
              ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                  $query->where('check_in', '<', $startOfMonth)
                        ->where('check_out', '>', $endOfMonth);
              });
        })->get();

    // حساب عدد الأيام المحجوزة (occupied room-nights)
    $occupiedRoomNights = 0;
    $totalStayNights = 0;

    foreach ($bookings as $booking) {
        $checkIn = Carbon::parse($booking->check_in)->max($startOfMonth);
        $checkOut = Carbon::parse($booking->check_out)->min($endOfMonth);
        $days = $checkOut->diffInDays($checkIn) + 1;
        $occupiedRoomNights += $days;
        $totalStayNights += Carbon::parse($booking->check_out)->diffInDays(Carbon::parse($booking->check_in));
    }

    // نسبة الإشغال الشهرية
    $monthlyOccupancyRate = $totalRooms * $daysInMonth > 0
        ? round(($occupiedRoomNights / ($totalRooms * $daysInMonth)) * 100, 2)
        : 0;

    // معدل مدة الإقامة
    $averageLengthOfStay = $bookings->count() > 0
        ? round($totalStayNights / $bookings->count(), 2)
        : 0;

    // الحجوزات حسب نوع الغرفة
    $bookingsByRoomType = Room_booking::select('rooms.room_type_id', 'room_types.type_name_ar', DB::raw('count(*) as total'))
        ->join('rooms', 'room_bookings.room_id', '=', 'rooms.id')
        ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
        ->groupBy('rooms.room_type_id', 'room_types.type_name_ar')
        ->get()
        ->map(function ($item) {
            return [
                'room_type_id' => $item->room_type_id,
                'room_type_name' => $item->type_name_ar,
                'total_bookings' => $item->total,
            ];
        });


    return response()->json([
        'monthly_occupancy_rate' => $monthlyOccupancyRate,
        'average_length_of_stay' => $averageLengthOfStay,
        'bookings_by_room_type' => $bookingsByRoomType,
    ]);
}

public function getActivityReport()
{
    $today = Carbon::today();
    $startOfWeek = Carbon::now()->startOfWeek();
    $endOfWeek = Carbon::now()->endOfWeek();

    // عدد طلبات المطعم لليوم
    $restaurantOrdersToday = RestaurantOrder::whereDate('preferred_time', $today)
        ->where('status', '!=', 'cancelled')
        ->count();

    // عدد طلبات المطعم لهذا الأسبوع
    $restaurantOrdersWeek = RestaurantOrder::whereBetween('preferred_time', [$startOfWeek, $endOfWeek])
        ->where('status', '!=', 'cancelled')
        ->count();

    // عدد خدمات المساج المنجزة
    $completedMassageServices = Invoice::where('item_type', 'massage')
        ->where('status', 'paid')
        ->count();

    // عدد خدمات المسبح المنجزة
    $completedPoolServices = Invoice::where('item_type', 'pool')
        ->where('status', 'paid')
        ->count();

    // عدد حجوزات الصالة المنجزة
    $completedHallBookings = Invoice::where('item_type', 'hall_bookings')
        ->where('status', 'paid')
        ->count();

    // عدد الشكاوى
    $totalComplaints = Complaint::count();

    return response()->json([
        'restaurant_orders_today' => $restaurantOrdersToday,
        'restaurant_orders_week' => $restaurantOrdersWeek,
        'completed_massage_services' => $completedMassageServices,
        'completed_pool_services' => $completedPoolServices,
        'completed_hall_bookings' => $completedHallBookings,
        'complaints_count' => $totalComplaints,
    ]);
}

public function getUserInvoices(Request $request, $userId)
{
    $query = Invoice::where('user_id', $userId);

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    $invoices = $query->orderBy('date', 'desc')->get();

    return response()->json([
        'invoices' => $invoices
    ]);
}

public function getAllComplaints()
{
    try {
        $complaints = Complaint::select('id', 'description', 'department', 'user_id')
            ->with(['user:id,name,email'])
            ->orderByDesc('id')
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'description' => $complaint->description,
                    'department' => $complaint->department,
                    'user_name' => $complaint->user->name ?? 'غير معروف',
                    'user_email' => $complaint->user->email ?? 'غير معروف',
                ];
            });

        return response()->json([
            'count' => $complaints->count(),
            'complaints' => $complaints,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'حدث خطأ أثناء جلب الشكاوى.' , $e], 500);
    }
}

public function getGuestsWithInvoices()
    {
        $guests = \App\Models\User::where('role', 'Guest')
            ->with(['invoices' => function ($query) {
                $query->select('id', 'user_id', 'description', 'date', 'price', 'status', 'item_type', 'item_id');
            }])
            ->select('id', 'name', 'email', 'phone')
            ->get();

        $result = $guests->map(function ($guest) {
            return [
                'guest' => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                ],
                'invoices' => $guest->invoices->map(function ($invoice) {
                    return [
                        'invoice_id' => $invoice->id,
                        'description' => $invoice->description,
                        'item_type' => $invoice->item_type,
                        'item_id' => $invoice->item_id,
                        'date' => $invoice->date,
                        'price' => number_format($invoice->price, 2),
                        'status' => $invoice->status,
                    ];
                })
            ];
        });

        return response()->json($result);
    }




}
