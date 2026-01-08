<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeAvailabilityService
{
    /**
     * إرجاع أول موظف متاح حسب الوقت والجنس
     */
    public function getAvailableEmployeeId(Carbon $preferredTime, string $gender): ?int
    {
        $endTime = $preferredTime->copy()->addMinutes(60);

        // جلب جميع الموظفين من نفس الجنس
        $employees = DB::table('employees')
            ->where('gender', $gender)
            ->pluck('id');

        if ($employees->isEmpty()) {
            return null;
        }

        // الموظفون المشغولون خلال هذه الفترة
        $busyEmployeeIds = DB::table('massage_requests')
            ->whereIn('employee_id', $employees)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($preferredTime, $endTime) {
                $query->where(function ($q) use ($preferredTime, $endTime) {
                    $q->where('preferred_time', '<', $endTime)
                      ->where(DB::raw('DATE_ADD(preferred_time, INTERVAL 60 MINUTE)'), '>', $preferredTime);
                });
            })
            ->pluck('employee_id');

        // إرجاع أول موظف غير مشغول
        return $employees->diff($busyEmployeeIds)->first();
    }
}
