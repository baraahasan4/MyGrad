<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OccasionType;

class OccasionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['ar_name' => 'عيد ميلاد',     'en_name' => 'Birthday'],
            ['ar_name' => 'زفاف',          'en_name' => 'Wedding'],
            ['ar_name' => 'تخرج',          'en_name' => 'Graduation'],
            ['ar_name' => 'استقبال مولود', 'en_name' => 'Baby_Shower'],
            ['ar_name' => 'رأس السنة',     'en_name' => 'New_Year'],
        ];

        foreach ($types as $type) {
            OccasionType::create($type);
        }
    }
}
