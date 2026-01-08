<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Decoration;


class DecorationSeeder extends Seeder
{
    public function run()
    {
        // Birthday Decorations
        Decoration::create([
            'ar_decor_name' => 'عيد ميلاد',
            'en_decor_name' => 'Birthday',
            'image' => 'Birthday.jpg',
            'price' => 20.00,
            'occasion_type_id' => 1,

        ]);

        Decoration::create([
            'ar_decor_name' => 'عيد ميلاد سعيد',
            'en_decor_name' => 'Happy Birthday',
            'image' => 'Birthday2.jpg',
            'price' => 10.00,
            'occasion_type_id' => 1,

        ]);

        // Wedding Decorations
        Decoration::create([
            'ar_decor_name' => 'حفلة زفاف',
            'en_decor_name' => 'Wedding Party ',
            'image' => 'Wedding_pink.jpg',
            'price' => 30.00,
            'occasion_type_id' => 2,

        ]);

        Decoration::create([
            'ar_decor_name' => ' ديكور زفاف',
            'en_decor_name' => 'Wedding Decor',
            'image' => 'Wedding_wow.jpg',
            'price' => 35.00,
            'occasion_type_id' => 2,

        ]);

        // Graduation Decorations
        Decoration::create([
            'ar_decor_name' => 'حفلة تخرج',
            'en_decor_name' => 'Graduation Party',
            'image' => 'Graduation.jpg',
            'price' => 30.00,
            'occasion_type_id' => 3,

        ]);

        Decoration::create([
            'ar_decor_name' => 'ديكور تخرج',
            'en_decor_name' => 'Graduation Decor',
            'image' => 'Graduation2.jpg',
            'price' => 25.00,
            'occasion_type_id' => 3,

        ]);

        // Baby Shower Decorations
        Decoration::create([
            'ar_decor_name' => 'استقبال مولود ولد',
            'en_decor_name' => ' Boy Baby Shower',
            'image' => 'babyBoy2.jpg',
            'price' => 30.00,
            'occasion_type_id' => 4,

        ]);

        Decoration::create([
            'ar_decor_name' => 'استقبال مولود بنت',
            'en_decor_name' => ' Girl Baby Shower',
            'image' => 'babyGirl2.jpg',
            'price' => 15.00,
            'occasion_type_id' => 4,

        ]);

        // New Year Decorations
        Decoration::create([
            'ar_decor_name' => 'سنة جديدة سعيدة ',
            'en_decor_name' => 'Happy New Year',
            'image' => 'new_year.jpg',
            'price' => 30.00,
            'occasion_type_id' => 5,

        ]);

        Decoration::create([
            'ar_decor_name' => 'ديكور سنة جديدة  ',
            'en_decor_name' => 'New Year Decor',
            'image' => 'new_year2.jpg',
            'price' => 15.00,
            'occasion_type_id' => 5,

        ]);
    }
}

