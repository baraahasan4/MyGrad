<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\HallBooking;
use App\Models\Decoration;
use App\Models\Hospitality;
use App\Models\Invoice;
use App\Models\Promotion;
use App\Models\InvoiceItem;
use App\Models\OccasionType;
use App\Services\InvoiceService;
use App\Services\BookingHallService;
use App\Models\Service_pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class BookHallController extends Controller
{

    public function __construct(protected InvoiceService $invoiceService) {}

     public function bookHall(Request $request, InvoiceService $invoiceService, BookingHallService $bookingHallService)
    {
        $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'guests_count' => 'required|integer|min:1',
            'occasion_type' => 'required|in:Birthday,Wedding,Graduation,Baby_Shower,New_Year',
            'decoration_id' => 'required|exists:decorations,id',
            'hospitality_id' => 'required|exists:hospitalities,id',
        ]);

        return $bookingHallService->bookHall($request, $invoiceService);
    }

    public function updateHallBooking(Request $request, $id, BookingHallService $bookingHallService, InvoiceService $invoiceService)
    {
        return $bookingHallService->updateBooking($request, $id, $invoiceService);
    }

    public function getHospitalitiesByOccasion($occasionType)
    {
        $occasion = OccasionType::where('en_name', $occasionType)->first();

        if (!$occasion) {
            return response()->json(['message' => 'نوع المناسبة غير موجود'], 404);
        }

        $hospitalities = Hospitality::where('occasion_type_id', $occasion->id)->get();

        return response()->json([
            'occasion_type' => $occasion->en_name,
            'hospitalities' => $hospitalities,
        ]);
    }

    public function getDecorationsByOccasion($occasionType)
    {
        $occasion = OccasionType::where('en_name', $occasionType)->first();

        if (!$occasion) {
            return response()->json(['message' => 'نوع المناسبة غير موجود'], 404);
        }

        $decorations = Decoration::where('occasion_type_id', $occasion->id)->get();

        return response()->json([
            'occasion_type' => $occasion->en_name,
            'decorations' => $decorations,
        ]);
    }
      public function acceptHallBookingByReceptionist(Request $request, $id, BookingHallService $bookingHallService)
    {
        return $bookingHallService->acceptByReceptionist($id);
    }

    public function rejectHallBookingByReceptionist(Request $request, $id, BookingHallService $bookingHallService)
    {
        return $bookingHallService->rejectByReceptionist($id);
    }

    public function getHallBookingsByStatus($status)
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['message' => 'Invalid status provided.'], 400);
        }

        $booking = HallBooking::where('status', $status)->get();

        return response()->json([
            'success' => true,
            'booking' => $booking
        ]);
    }

    public function cancelHallBookingByUser($id)
    {
        $booking = HallBooking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح لك بإلغاء هذا الحجز.'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز لأنه تمت معالجته بالفعل.'], 400);
        }

        $booking->status = 'cancelled';
        // $booking->approved_or_rejected_by = auth()->id(); // تخزين id المستخدم الذي ألغى
        $booking->save();

        return response()->json([
            'message' => 'تم إلغاء الحجز بنجاح.',
        ]);
    }

    public function BookHallByReceptionistForUser(Request $request, BookingHallService $bookingHallService, InvoiceService $invoiceService)
    {
        return $bookingHallService->bookByReceptionistForUser($request, $invoiceService);
    }
    
    public function previewHallBookingForReceptionist(Request $request, BookingHallService $bookingHallService)
    {
        $request->validate([
            'guestName' => 'sometimes|required_without:user|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'guests_count' => 'required|integer|min:1',
            'occasion_type' => 'required|in:Birthday,Wedding,Graduation,Baby_Shower,New_Year',
            'decoration_id' => 'required|exists:decorations,id',
            'hospitality_id' => 'required|exists:hospitalities,id',
        ]);
    
        return $bookingHallService->previewHallBookingForReceptionist($request);
    }
    
    public function getOccasionTypes()
    {
        $types = OccasionType::all(['id', 'ar_name', 'en_name']);
        
        return response()->json([
            'data' => $types
        ]);
    }

    

}
