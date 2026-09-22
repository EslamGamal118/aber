<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds a complete, re-runnable test dataset in dependency order.
 *
 *   php artisan migrate:fresh --seed        (fresh database)
 *   php artisan db:seed                     (re-run: upserts, replaces #SEED orders)
 *
 * Test credentials (password for every account: "password"):
 *   Admin     admin@admin.com
 *   Clients   client1@aber.test .. client5@aber.test   (phones 0551000001-0551000005)
 *   Providers burger@aber.test (active), coffee@aber.test (active),
 *             shawarma@aber.test (pending), pizza@aber.test (blocked)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Auth & reference data (no dependencies)
            AdminSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            ClientSeeder::class,
            ProviderSeeder::class,

            // 2. Catalog (providers -> menus -> partitions -> products/options)
            VendorMenuSeeder::class,
            PartitionSeeder::class,
            ProductSeeder::class,
            RatingSeeder::class,        // clients + products
            TimetableSeeder::class,     // providers
            OfferSeeder::class,         // admin + providers
            FavoriteSeeder::class,      // clients + providers

            // 3. Orders & Al Rajhi payment states (clients + providers + products/options)
            OrderSeeder::class,
        ]);
    }
}
