<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Service / food categories shown in the client app (GET /api/categories).
 */
class CategorySeeder extends Seeder
{
    public const CATEGORIES = [
        ['name' => 'برجر', 'slug' => 'burger', 'icon' => 'fa-hamburger', 'description' => 'برجر لحم ودجاج طازج'],
        ['name' => 'شاورما', 'slug' => 'shawarma', 'icon' => 'fa-drumstick-bite', 'description' => 'شاورما عربي ولفائف'],
        ['name' => 'قهوة ومشروبات', 'slug' => 'coffee-drinks', 'icon' => 'fa-mug-hot', 'description' => 'قهوة مختصة ومشروبات باردة'],
        ['name' => 'حلويات', 'slug' => 'desserts', 'icon' => 'fa-ice-cream', 'description' => 'حلويات وآيس كريم'],
        ['name' => 'بيتزا', 'slug' => 'pizza', 'icon' => 'fa-pizza-slice', 'description' => 'بيتزا إيطالية على الحطب'],
        ['name' => 'مأكولات بحرية', 'slug' => 'seafood', 'icon' => 'fa-fish', 'description' => 'أسماك وروبيان طازج'],
        ['name' => 'صحي', 'slug' => 'healthy', 'icon' => 'fa-leaf', 'description' => 'سلطات ووجبات صحية', 'active' => false],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + [
                    'image' => 'https://placehold.co/400x400/png?text=' . Str::title($category['slug']),
                    'active' => true,
                ]
            );
        }
    }
}
