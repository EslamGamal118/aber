<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * Menus for every ACTIVE provider (GET /api/vendor/menus, /api/client/providers/{id}/menus).
 */
class VendorMenuSeeder extends Seeder
{
    /**
     * Menus keyed by provider email.
     */
    public const MENUS = [
        'burger@aber.test' => [
            ['name' => 'القائمة الرئيسية', 'description' => 'برجر، جانبيات ومشروبات'],
            ['name' => 'قائمة الإفطار', 'description' => 'ساندويتشات الصباح حتى 11 ص'],
        ],
        'coffee@aber.test' => [
            ['name' => 'قائمة المشروبات', 'description' => 'قهوة ساخنة وباردة وحلويات'],
        ],
    ];

    public function run(): void
    {
        foreach (ProviderSeeder::activeProviders() as $provider) {
            foreach (self::MENUS[$provider->email] ?? [] as $menu) {
                Menu::updateOrCreate(
                    ['provider_id' => $provider->id, 'name' => $menu['name']],
                    $menu
                );
            }
        }
    }
}
