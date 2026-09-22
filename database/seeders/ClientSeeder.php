<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Five verified clients. All log in with password "password"
 * (POST /api/client/login with email or phone).
 */
class ClientSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const CLIENTS = [
        ['name' => 'محمد العتيبي', 'email' => 'client1@aber.test', 'phone' => '0551000001', 'city' => 'الرياض', 'address' => 'حي النرجس، شارع الأمير محمد بن سلمان', 'gender' => 'male', 'birthdate' => '1995-03-14'],
        ['name' => 'سارة القحطاني', 'email' => 'client2@aber.test', 'phone' => '0551000002', 'city' => 'الرياض', 'address' => 'حي الياسمين، طريق أنس بن مالك', 'gender' => 'female', 'birthdate' => '1998-07-22'],
        ['name' => 'خالد الشهري', 'email' => 'client3@aber.test', 'phone' => '0551000003', 'city' => 'جدة', 'address' => 'حي الروضة، شارع التحلية', 'gender' => 'male', 'birthdate' => '1990-11-02'],
        ['name' => 'نورة الدوسري', 'email' => 'client4@aber.test', 'phone' => '0551000004', 'city' => 'الدمام', 'address' => 'حي الشاطئ، طريق الملك فهد', 'gender' => 'female', 'birthdate' => '2001-01-30'],
        ['name' => 'فهد المطيري', 'email' => 'client5@aber.test', 'phone' => '0551000005', 'city' => 'الرياض', 'address' => 'حي العليا، شارع العروبة', 'gender' => 'male', 'birthdate' => '1988-05-09', 'is_active' => false],
    ];

    public function run(): void
    {
        foreach (self::CLIENTS as $index => $data) {
            $client = Client::firstOrNew(['email' => $data['email']]);

            // city / address / phone_verified_at are not mass assignable on the model
            $client->forceFill($data + [
                'password' => Hash::make(self::PASSWORD),
                'avatar' => 'https://i.pravatar.cc/150?img=' . ($index + 11),
                'bio' => 'عميل تجريبي #' . ($index + 1),
                'is_active' => $data['is_active'] ?? true,
                'phone_verified_at' => now(),
            ])->save();
        }
    }
}
