<?php

namespace App\Http\Controllers;

use App\Models\Massage_request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\MassageService;



class MassageController extends Controller
{
    public function __construct(
        protected MassageService $massageService
        ) {}

    public function RequestMassage(Request $request)
    {
        $validated = $request->validate([
            'preferred_time' => 'required|date|after:now',
            'gender' => 'required|in:male,female',
        ]);

        return $this->massageService->requestMassage($validated);
    }

    public function approveMassageRequest($id)
    {
        return $this->massageService->approveMassageRequest($id);
    }

    public function cancelMassageRequestByReception($id)
    {
        return $this->massageService->cancelByReception($id);
    }

    public function cancelMassageRequestByUser($id)
    {
        return $this->massageService->cancelByUser($id);
    }

    public function bookMassageByReception(Request $request)
    {
        $validated = $request->validate([
            'preferred_time' => 'required|date|after:now',
            'gender' => 'required|in:male,female',
            'guest_name' => 'string|max:255',
        ]);

        return $this->massageService->bookMassageByReception($validated);
    }

    public function getavailableEmployees(Request $request)
    {
        $request->validate([
            'preferred_time' => 'required|date_format:Y-m-d H:i:s',
            'gender' => 'required|in:male,female'
        ]);

        $startTime = Carbon::parse($request->preferred_time);
        $endTime = $startTime->copy()->addMinutes(60);

        $employees = DB::table('employees')
        ->where('gender', $request->gender)
        ->pluck('id');

        $busyEmployeeIds = Massage_request::whereIn('employee_id', $employees)
        ->where('status', 'confirmed')
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where('preferred_time', '<', $endTime)
            ->where(DB::raw('DATE_ADD(preferred_time, INTERVAL 60 MINUTE)'), '>', $startTime);
        })
        ->pluck('employee_id');

        $availableEmployees = DB::table('employees')
        ->whereIn('id', $employees)
        ->whereNotIn('id', $busyEmployeeIds)
        ->get();

        if($availableEmployees){
            return response()->json([
                'يوجد موظفون متاحون في هذا الوقت'
            ]);
        }
    }

    public function getUserMassageRequestsByStatus(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,confirmed,cancelled']);

        $data = $this->massageService->getMassageRequests(
            status: $request->status,
            userId: Auth::id()
        );

        return response()->json(['message' => 'تم جلب الطلبات بنجاح.', 'data' => $data]);
    }

    public function getMassageRequestsByStatus(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,confirmed,cancelled']);

        $data = $this->massageService->getMassageRequests(
            status: $request->status,
            withRelations: true
        );

        return response()->json(['message' => 'تم جلب طلبات المساج بنجاح.', 'data' => $data]);
    }
}
