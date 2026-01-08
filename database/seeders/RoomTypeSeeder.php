<?php

namespace Database\Seeders;

use App\Models\Room_type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roomTypes = [
            [
                'type_name_en' => 'Single',
                'type_name_ar' => 'فردية',
                'description_en' => 'Room for one person, approx. 37-45 sqm.',
                'description_ar' => 'غرفة مخصصة لشخص واحد، تتراوح مساحتها بين 37 و45 متر مربع.',
                'price' => 10.00
            ],
            [
                'type_name_en' => 'Double',
                'type_name_ar' => 'مزدوجة',
                'description_en' => 'Room for two people, around 40-45 sqm.',
                'description_ar' => 'غرفة تتسع لشخصين، بمساحة تتراوح بين 40 و45 متر مربع.',
                'price' => 20.00
            ],
            [
                'type_name_en' => 'Triple',
                'type_name_ar' => 'ثلاثية',
                'description_en' => 'Room for three people with three beds, 45-65 sqm.',
                'description_ar' => 'غرفة بثلاث أسرة تتسع لثلاثة أشخاص، بمساحة تتراوح بين 45 و65 متر مربع.',
                'price' => 30.00
            ],
            [
                'type_name_en' => 'Executive Suite',
                'type_name_ar' => 'جناح تنفيذي',
                'description_en' => 'Suite with a bedroom and a living room, 70-100 sqm.',
                'description_ar' => 'جناح يحتوي على غرفة نوم وغرفة معيشة، بمساحة بين 70 و100 متر مربع.',
                'price' => 40.00
            ],
            [
                'type_name_en' => 'Disabled Room',
                'type_name_ar' => 'غرفة لذوي الاحتياجات الخاصة',
                'description_en' => 'Accessible room designed for people with disabilities, 30-42 sqm.',
                'description_ar' => 'غرفة مجهزة لتلبية احتياجات ذوي الاحتياجات الخاصة، بمساحة تتراوح بين 30 و42 متر مربع.',
                'price' => 25.00
            ],
            [
                'type_name_en' => 'Smoking/Non-Smoking Room',
                'type_name_ar' => 'غرفة تدخين/غير تدخين',
                'description_en' => 'Room for smokers or non-smokers, ranging from 30 to 250 sqm.',
                'description_ar' => 'غرف مخصصة للمدخنين أو لغير المدخنين، بمساحة تتراوح بين 30 و250 متر مربع.',
                'price' => 15.00
            ],
        ];


        foreach ($roomTypes as $roomType) {
            Room_type::create($roomType);
        }
    }
}
