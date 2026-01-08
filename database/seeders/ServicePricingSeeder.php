<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service_pricing;
use Carbon\Carbon;

class ServicePricingSeeder extends Seeder
{
    public function run()
    {
        Service_pricing::create([
            'service_type' => 'restaurant',
            'price' => 15.00,
            'date' => Carbon::now(),
            'active' => true,
            'user_id' => 1, // عدله حسب ID المستخدم الذي يضيف السعر
        ]);

        Service_pricing::create([
            'service_type' => 'pool',
            'price' => 5.00,
            'date' => Carbon::now(),
            'active' => true,
            'user_id' => 1,
        ]);

        Service_pricing::create([
            'service_type' => 'massage',
            'price' => 10.00,
            'date' => Carbon::now(),
            'active' => true,
            'user_id' => 1,
        ]);

        Service_pricing::create([
            'service_type' => 'hall_booking',
            'price' => 100.00,
            'date' => Carbon::now(),
            'active' => true,
            'user_id' => 1,
        ]);
    }
}
