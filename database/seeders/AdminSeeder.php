<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Default admin panel accounts (login at /admin/login).
 *
 *  admin@admin.com        / password   (Super Admin)
 *  ops@aber.test          / password   (second active admin)
 *  disabled@aber.test     / password   (inactive - to test blocked admin login)
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['name' => 'Super Admin', 'email' => 'admin@admin.com', 'phone' => '0500000000', 'status' => 'active'],
            ['name' => 'Operations Admin', 'email' => 'ops@aber.test', 'phone' => '0500000001', 'status' => 'active'],
            ['name' => 'Disabled Admin', 'email' => 'disabled@aber.test', 'phone' => '0500000002', 'status' => 'inactive'],
        ];

        foreach ($admins as $admin) {
            Admin::updateOrCreate(
                ['email' => $admin['email']],
                $admin + ['password' => Hash::make('password')]
            );
        }
    }
}
