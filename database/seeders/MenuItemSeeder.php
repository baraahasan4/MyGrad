<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $menuItems = [
            [
                'ar_name' => 'بيتزا مارجريتا',
                'en_name' => 'Margherita Pizza',
                'ar_description' => 'بيتزا بالجبنة والطماطم الطازجة',
                'en_description' => 'Cheese and fresh tomato pizza',
                'photo' => 'images/menu/1746993609345.png',
                'type' => 'Main_Course',
                'price' => 30.00
            ],
            [
                'ar_name' => 'برجر لحم',
                'en_name' => 'Beef Burger',
                'ar_description' => 'برجر لحم مشوي مع الخضار',
                'en_description' => 'Grilled beef burger with vegetables',
                'photo' => 'images/menu/1746993609323.png',
                'type' => 'Main_Course',
                'price' => 25.00
            ],
            [
                'ar_name' => 'سلطة سيزر',
                'en_name' => 'Caesar Salad',
                'ar_description' => 'سلطة دجاج مع صلصة السيزر',
                'en_description' => 'Chicken salad with Caesar dressing',
                'photo' => 'images/menu/1746993609288.png',
                'type' => 'Appetizers',
                'price' => 20.00
            ],
            [
                'ar_name' => 'شوربة عدس',
                'en_name' => 'Lentil Soup',
                'ar_description' => 'شوربة عدس دافئة بالتوابل',
                'en_description' => 'Warm spiced lentil soup',
                'photo' => 'images/menu/1746993609252.png',
                'type' => 'Appetizers',
                'price' => 15.00
            ],
            [
                'ar_name' => 'مكرونة ألفريدو',
                'en_name' => 'Alfredo Pasta',
                'ar_description' => 'مكرونة مع صلصة ألفريدو بالكريمة والدجاج',
                'en_description' => 'Pasta with Alfredo sauce, cream, and chicken',
                'photo' => 'images/menu/1746993609235.png',
                'type' => 'Main_Course',
                'price' => 35.00
            ],
            [
                'ar_name' => 'تشيز كيك',
                'en_name' => 'Cheesecake',
                'ar_description' => 'تشيز كيك لذيذ مع صوص الفراولة',
                'en_description' => 'Delicious cheesecake with strawberry sauce',
                'photo' => 'images/menu/1746993609203.png',
                'type' => 'Desserts',
                'price' => 18.00
            ],
            [
                'ar_name' => 'عصير برتقال',
                'en_name' => 'Orange Juice',
                'ar_description' => 'عصير برتقال طبيعي وطازج',
                'en_description' => 'Fresh and natural orange juice',
                'photo' => 'images/menu/1746993609184.png',
                'type' => 'Drinks',
                'price' => 10.00
            ],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }
    }
}
