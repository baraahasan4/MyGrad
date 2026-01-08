<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;

class UpdateTableStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tables:update-status'; // اسم الأمر الذي سيشغله Laravel

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تحديث حالة الطاولات بعد انتهاء مدة الحجز';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // الحصول على الطاولات المحجوزة التي تجاوزت وقت الحجز
        $orders = RestaurantOrder::where('status', 'pending')
            ->where('reservation_end_time', '<=', now()) // التحقق من الحجز الذي انتهى
            ->get();

        foreach ($orders as $order) {
            // إذا كانت الطاولة موجودة في الطلب
            if ($order->table_number) {
                $table = RestaurantTable::where('table_number', $order->table_number)->first();
                
                if ($table) {
                    // تحديث حالة الطاولة إلى "متاحة"
                    $table->status = 'available';
                    $table->save();

                    // تحديث حالة الطلب إلى "تم"
                    $order->status = 'completed';
                    $order->save();
                    
                    // طباعة رسالة لتأكيد التحديث
                    $this->info("تم تحديث حالة الطاولة رقم {$order->table_number} إلى 'متاحة'");
                }
            }
        }
    }
}
