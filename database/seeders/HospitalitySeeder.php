<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hospitality;

class HospitalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

     public function run()
     {
         // 🎂 Birthday
         Hospitality::create([
             'ar_name' => 'ضيافة ميلاد بسيطة',
             'en_name' => 'Simple Birthday Hospitality',
             'ar_description' => 'ضيافة تشمل انواع بسيطة من الحلويات.',
             'en_description' => 'Simple hospitality including basic types of sweets.',
             'image' => 'Birthday_simple1.png',
             'price' => 5.00,
             'occasion_type_id' => 1,
             'type' => 'Simple ',
            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة ميلاد فاخرة',
             'en_name' => 'Luxurious Birthday Hospitality',
             'ar_description' => 'ضيافة تشمل علب باصناف فاخرة من الحلويات.',
             'en_description' => 'Luxurious hospitality offering premium dessert boxes.',
             'image' => 'Birthday_lux.png',
             'price' => 15.00,
             'occasion_type_id' => 1,
             'type' => 'Luxurious',

            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة ميلاد ملكية',
             'en_name' => 'Royal Birthday Hospitality',
             'ar_description' => 'ضيافة ملكية بأصناف ممتازة بعلب ملكية.',
             'en_description' => 'Royal hospitality with exquisite items in elegant royal boxes.',
             'image' => 'Birthday_royal.png',
             'price' => 30.00,
             'occasion_type_id' => 1,
             'type' => 'Royal',

            ]);

         // 💍 Wedding
         Hospitality::create([
             'ar_name' => 'ضيافة زفاف بسيطة',
             'en_name' => 'Simple Wedding Hospitality',
             'ar_description' => 'ضيافة خفيفة تناسب حفلات الزفاف الصغيرة.',
             'en_description' => 'Light hospitality suitable for small wedding parties.',
             'image' => 'Wedding_simple.png',
             'price' => 8.00,
             'occasion_type_id' => 2,
             'type' => 'Simple ',
            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة زفاف فاخرة',
             'en_name' => 'Luxurious Wedding Hospitality',
             'ar_description' => 'ضيافة فاخرة تحتوي على مجموعة مختارة من الحلويات الراقية.',
             'en_description' => 'Luxurious hospitality with a selection of premium sweets.',
             'image' => 'Wedding_lux.png',
             'price' => 20.00,
             'occasion_type_id' => 2,
             'type' => 'Luxurious',

            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة زفاف ملكية',
             'en_name' => 'Royal Wedding Hospitality',
             'ar_description' => 'ضيافة ملكية لحفلات الزفاف الفخمة مع خدمة ممتازة.',
             'en_description' => 'Royal hospitality for luxurious weddings with top-tier service.',
             'image' => 'Wedding_roy1.png',
             'price' => 40.00,
             'occasion_type_id' => 2,
             'type' => 'Royal',

            ]);

         // 🎓 Graduation
         Hospitality::create([
             'ar_name' => 'ضيافة تخرج بسيطة',
             'en_name' => 'Simple Graduation Hospitality',
             'ar_description' => 'ضيافة بسيطة لحفلات التخرج العائلية.',
             'en_description' => 'Simple hospitality for small family graduation celebrations.',
             'image' => 'Graduation_simple.png',
             'price' => 6.00,
             'occasion_type_id' => 3,
             'type' => 'Simple ',
            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة تخرج فاخرة',
             'en_name' => 'Luxurious Graduation Hospitality',
             'ar_description' => 'ضيافة فاخرة لحفلات التخرج الكبيرة.',
             'en_description' => 'Luxurious hospitality for grand graduation parties.',
             'image' => 'Graduation_lux.png',
             'price' => 18.00,
             'occasion_type_id' => 3,
             'type' => 'Luxurious',

            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة تخرج ملكية',
             'en_name' => 'Royal Graduation Hospitality',
             'ar_description' => 'ضيافة ملكية مميزة لأهم حفلات التخرج.',
             'en_description' => 'Exclusive royal hospitality for distinguished graduation events.',
             'image' => 'Graduation_royal.png',
             'price' => 35.00,
             'occasion_type_id' => 3,
             'type' => 'Royal',

            ]);

         // 👶 Baby Shower
         Hospitality::create([
             'ar_name' => 'ضيافة استقبال مولود بسيطة',
             'en_name' => 'Simple Baby Shower Hospitality',
             'ar_description' => 'ضيافة لطيفة لحفلات استقبال المواليد.',
             'en_description' => 'Gentle hospitality for cozy baby shower events.',
             'image' => 'boy_baby2.png',
             'price' => 7.00,
             'occasion_type_id' => 4,
             'type' => 'Simple ',
            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة استقبال مولود فاخرة',
             'en_name' => 'Luxurious Baby Shower Hospitality',
             'ar_description' => 'ضيافة راقية تشمل ضيافة بألوان مخصصة للمولود.',
             'en_description' => 'Luxurious hospitality with themed treats for the newborn.',
             'image' => 'girl_baby.png',
             'price' => 16.00,
             'occasion_type_id' => 4,
             'type' => 'Luxurious',

            ]);

         Hospitality::create([
             'ar_name' => 'ضيافة استقبال مولود ملكية',
             'en_name' => 'Royal Baby Shower Hospitality',
             'ar_description' => 'ضيافة ملكية لحفل استقبال المولود بمستوى راقٍ جداً.',
             'en_description' => 'Royal baby shower hospitality with elite-level offerings.',
             'image' => 'boy_baby3.png',
             'price' => 32.00,
             'occasion_type_id' => 4,
             'type' => 'Royal',

            ]);

         // 🎉 New Year
         Hospitality::create([
             'ar_name' => 'ضيافة رأس السنة بسيطة',
             'en_name' => 'Simple New Year Hospitality',
             'ar_description' => 'ضيافة خفيفة للترحيب بالعام الجديد.',
             'en_description' => 'Light hospitality to welcome the new year.',
             'image' => 'simple_new_year.png',
             'price' => 6.00,
             'occasion_type_id' => 5,
             'type' => 'Simple ',
         ]);

         Hospitality::create([
             'ar_name' => 'ضيافة رأس السنة فاخرة',
             'en_name' => 'Luxurious New Year Hospitality',
             'ar_description' => 'ضيافة فاخرة لأمسيات رأس السنة الخاصة.',
             'en_description' => 'Luxurious hospitality for elegant New Year’s Eve celebrations.',
             'image' => 'lux_new_year.png',
             'price' => 19.00,
             'occasion_type_id' => 5,
              'type' => 'Luxurious',
         ]);

         Hospitality::create([
             'ar_name' => 'ضيافة رأس السنة ملكية',
             'en_name' => 'Royal New Year Hospitality',
             'ar_description' => 'ضيافة ملكية بأعلى مستوى استقبال للسنة الجديدة.',
             'en_description' => 'Royal-grade hospitality to celebrate the new year in grandeur.',
             'image' => 'royal_new_year.png',
             'price' => 38.00,
             'occasion_type_id' => 5,
              'type' => 'Royal',
         ]);
     }
}
