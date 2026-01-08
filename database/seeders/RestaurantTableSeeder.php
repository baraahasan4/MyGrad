<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantTable;

class RestaurantTableSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 20; $i++) {
            RestaurantTable::create([
                'table_number' => $i,
                'status' => 'available',
            ]);
        }
    }
}
