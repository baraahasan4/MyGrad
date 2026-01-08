<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Employee::create([
            'name' => 'rema',
            'email' => 'rema@gmail.com',
            'phone' => '123456789',
            'gender' => 'female',
            'birthday' => '2000-05-01',
            'role' => 'General',
            'job_title' => 'massage',
            'employment_history' => '2023-05-01',
            'salary' => '2000000.00',
            'housing' => 'Damascus',
            'user_id' => '2'
        ]);

        Employee::create([
            'name' => 'leen',
            'email' => 'leen@gmail.com',
            'phone' => '1234587654',
            'gender' => 'female',
            'birthday' => '2000-05-01',
            'role' => 'General',
            'job_title' => 'massage',
            'employment_history' => '2023-05-01',
            'salary' => '1200000.00',
            'housing' => 'Damascus',
            'user_id' => '2'
        ]);

        Employee::create([
            'name' => 'ahmad',
            'email' => 'ahmad@gmail.com',
            'phone' => '1234587654',
            'gender' => 'male',
            'birthday' => '2000-05-01',
            'role' => 'General',
            'job_title' => 'massage',
            'employment_history' => '2023-05-01',
            'salary' => '1200000.00',
            'housing' => 'Damascus',
            'user_id' => '2'
        ]);
    }
}
