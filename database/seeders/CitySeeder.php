<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public const CITIES = [
        'الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة', 'الدمام',
        'الخبر', 'الظهران', 'الطائف', 'تبوك', 'أبها', 'القصيم', 'حائل',
    ];

    public function run(): void
    {
        foreach (self::CITIES as $name) {
            City::firstOrCreate(['name' => $name]);
        }
    }
}
