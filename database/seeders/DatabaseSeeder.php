<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RestaurantManagerSeeder::class,
            RestaurantTableSeeder::class,
            EmployeeSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            ServicePricingSeeder::class,
            RestaurantTableSeeder::class,
            MenuItemSeeder::class,
            OccasionTypeSeeder::class,
            DecorationSeeder::class,
            HospitalitySeeder::class,
        ]);
    }
}
