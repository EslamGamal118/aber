<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuPartition;
use Illuminate\Database\Seeder;

/**
 * Partitions (sections) inside each seeded menu.
 * Both menu_id (used by the app) and the legacy menuId FK are filled.
 */
class PartitionSeeder extends Seeder
{
    /**
     * Partitions keyed by menu name.
     */
    public const PARTITIONS = [
        'القائمة الرئيسية' => [
            ['name' => 'برجر لحم', 'description' => 'برجر لحم بقري واغيو'],
            ['name' => 'برجر دجاج', 'description' => 'برجر دجاج مقرمش ومشوي'],
            ['name' => 'جانبيات', 'description' => 'بطاطس وحلقات بصل'],
            ['name' => 'مشروبات', 'description' => 'مشروبات غازية وعصائر'],
        ],
        'قائمة الإفطار' => [
            ['name' => 'ساندويتشات', 'description' => 'ساندويتشات بيض وجبن'],
            ['name' => 'مشروبات ساخنة', 'description' => 'شاي وقهوة'],
        ],
        'قائمة المشروبات' => [
            ['name' => 'قهوة ساخنة', 'description' => 'إسبريسو، لاتيه، كابتشينو'],
            ['name' => 'قهوة باردة', 'description' => 'آيس لاتيه وكولد برو'],
            ['name' => 'حلويات', 'description' => 'كوكيز وكيك'],
            ['name' => 'موسمي', 'description' => 'مشروبات موسمية', 'status' => 'inactive'],
        ],
    ];

    public function run(): void
    {
        foreach (Menu::whereNotNull('provider_id')->get() as $menu) {
            foreach (self::PARTITIONS[$menu->name] ?? [] as $partition) {
                $model = MenuPartition::firstOrNew(['menu_id' => $menu->id, 'name' => $partition['name']]);
                $model->forceFill($partition + [
                    'menu_id' => $menu->id,
                    'menuId' => $menu->id,
                    'status' => $partition['status'] ?? 'active',
                ])->save();
            }
        }
    }
}
