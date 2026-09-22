<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Providers (food trucks) in every approval state. Password: "password".
 *
 *  active   burger@aber.test   0552000001  (Riyadh, open)   - full vendor flow
 *  active   coffee@aber.test   0552000002  (Riyadh, closed) - second active vendor
 *  pending  shawarma@aber.test 0552000003  (Jeddah)         - admin approve / reject flow
 *  blocked  pizza@aber.test    0552000004  (Dammam)         - admin unblock flow
 */
class ProviderSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const PROVIDERS = [
        [
            'name' => 'عربة برجر هاوس',
            'car_name' => 'Burger House Truck',
            'email' => 'burger@aber.test',
            'phone' => '0552000001',
            'type' => 'company',
            'commercial_register' => '1010123456',
            'id_card' => '1234567890',
            'status' => 'active',
            'city' => 'الرياض',
            'address' => 'حي النرجس، بجوار حديقة النرجس',
            'latitude' => 24.8306000,
            'longitude' => 46.6480000,
            'bio' => 'أفضل برجر لحم واغيو في الرياض',
            'is_open' => true,
        ],
        [
            'name' => 'عربة قهوة الشروق',
            'car_name' => 'Sunrise Coffee Cart',
            'email' => 'coffee@aber.test',
            'phone' => '0552000002',
            'type' => 'personal',
            'commercial_register' => null,
            'id_card' => '1098765432',
            'status' => 'active',
            'city' => 'الرياض',
            'address' => 'حي الياسمين، طريق أنس بن مالك',
            'latitude' => 24.8215000,
            'longitude' => 46.6250000,
            'bio' => 'قهوة مختصة ومشروبات باردة',
            'is_open' => false,
        ],
        [
            'name' => 'عربة شاورما البلد',
            'car_name' => 'Al Balad Shawarma',
            'email' => 'shawarma@aber.test',
            'phone' => '0552000003',
            'type' => 'company',
            'commercial_register' => '4030987654',
            'id_card' => '1122334455',
            'status' => 'pending',
            'city' => 'جدة',
            'address' => 'حي الروضة، شارع التحلية',
            'latitude' => 21.5433000,
            'longitude' => 39.1728000,
            'bio' => 'شاورما على الطريقة الشامية',
            'is_open' => false,
        ],
        [
            'name' => 'عربة بيتزا الشرقية',
            'car_name' => 'Eastern Pizza Van',
            'email' => 'pizza@aber.test',
            'phone' => '0552000004',
            'type' => 'personal',
            'commercial_register' => null,
            'id_card' => '1555666777',
            'status' => 'blocked',
            'status_message' => 'تم حظر الحساب لمخالفة شروط الاستخدام',
            'city' => 'الدمام',
            'address' => 'حي الشاطئ، كورنيش الدمام',
            'latitude' => 26.4207000,
            'longitude' => 50.0888000,
            'bio' => 'بيتزا إيطالية على الحطب',
            'is_open' => false,
        ],
    ];

    public function run(): void
    {
        foreach (self::PROVIDERS as $index => $data) {
            $provider = Provider::firstOrNew(['email' => $data['email']]);

            $provider->forceFill($data + [
                'password' => Hash::make(self::PASSWORD),
                'avatar' => 'https://i.pravatar.cc/150?img=' . ($index + 31),
                'provider_banner' => 'https://placehold.co/1200x400/png?text=' . rawurlencode($data['car_name']),
                'phone_verified_at' => now(),
                'approved_at' => $data['status'] === 'active' ? now()->subDays(30) : null,
                'status_message' => $data['status_message'] ?? null,
            ])->save();
        }
    }

    /**
     * Providers that may own menus / receive orders.
     */
    public static function activeProviders()
    {
        return Provider::where('status', 'active')
            ->whereIn('email', array_column(self::PROVIDERS, 'email'))
            ->orderBy('id')
            ->get();
    }
}
