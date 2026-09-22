<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Offer;
use Illuminate\Database\Seeder;

/**
 * Global (admin) and provider specific offers / promo codes.
 * GET /api/client/offers, /api/client/offers/{provider_id}, POST /api/client/promocode
 */
class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::where('email', 'admin@admin.com')->first();
        $providers = ProviderSeeder::activeProviders()->keyBy('email');

        $offers = [
            // Global admin offers
            ['title' => 'خصم الترحيب 15%', 'code' => 'WELCOME15', 'discount_type' => 'percentage', 'discount_value' => 15,
                'provider_id' => null, 'created_by_id' => $admin?->id, 'created_by_type' => 'admin',
                'max_uses' => 500, 'start_date' => now()->subDays(7), 'end_date' => now()->addMonths(3), 'status' => 'active'],
            ['title' => 'خصم 10 ريال على أول طلب', 'code' => 'FIRST10', 'discount_type' => 'fixed', 'discount_value' => 10,
                'provider_id' => null, 'created_by_id' => $admin?->id, 'created_by_type' => 'admin',
                'max_uses' => 1000, 'start_date' => now()->subDays(30), 'end_date' => now()->addMonth(), 'status' => 'active'],
            ['title' => 'عرض رمضان (منتهي)', 'code' => 'RAMADAN25', 'discount_type' => 'percentage', 'discount_value' => 25,
                'provider_id' => null, 'created_by_id' => $admin?->id, 'created_by_type' => 'admin',
                'max_uses' => 100, 'uses' => 100, 'start_date' => now()->subMonths(6), 'end_date' => now()->subMonths(5), 'status' => 'inactive'],
            // Provider offers
            ['title' => 'برجر هاوس - خصم 20%', 'code' => 'BURGER20', 'discount_type' => 'percentage', 'discount_value' => 20,
                'provider_id' => $providers['burger@aber.test']->id ?? null, 'created_by_id' => $providers['burger@aber.test']->id ?? 0, 'created_by_type' => 'provider',
                'max_uses' => 200, 'start_date' => now()->subDays(3), 'end_date' => now()->addDays(27), 'status' => 'active'],
            ['title' => 'قهوة الشروق - 5 ريال خصم', 'code' => 'COFFEE5', 'discount_type' => 'fixed', 'discount_value' => 5,
                'provider_id' => $providers['coffee@aber.test']->id ?? null, 'created_by_id' => $providers['coffee@aber.test']->id ?? 0, 'created_by_type' => 'provider',
                'max_uses' => 50, 'start_date' => now()->subDay(), 'end_date' => now()->addWeeks(2), 'status' => 'active'],
        ];

        foreach ($offers as $offer) {
            if ($offer['created_by_type'] === 'provider' && !$offer['provider_id']) {
                continue;
            }

            Offer::updateOrCreate(
                ['code' => $offer['code']],
                $offer + ['image' => 'https://placehold.co/800x400/png?text=' . rawurlencode($offer['code'])]
            );
        }
    }
}
