<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RestaurantManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Restaurant Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Restaurant_Supervisor',
            'phone' => '1234567890',
        ]);

        User::create([
            'name' => 'Reception Manager',
            'email' => 'reception2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Receptionist',
            'phone' => '1237267890',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'baraadlihasan@gmail.com',
            'password' => Hash::make('reader55555'),
            'role' => 'admin',
            'phone' => '1237267890',
        ]);
    }
}
