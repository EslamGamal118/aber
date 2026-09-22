<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Favorite;
use Illuminate\Database\Seeder;

/**
 * Clients' favourite providers (GET /api/client/favorites).
 */
class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::whereIn('email', array_column(ClientSeeder::CLIENTS, 'email'))->orderBy('id')->get()->values();
        $providers = ProviderSeeder::activeProviders()->values();

        if ($clients->isEmpty() || $providers->isEmpty()) {
            return;
        }

        $pairs = [
            [0, 0], [0, 1], // client 1 likes both active trucks
            [1, 0],
            [2, 1],
            [3, 0],
        ];

        foreach ($pairs as [$clientIndex, $providerIndex]) {
            if (!isset($clients[$clientIndex], $providers[$providerIndex])) {
                continue;
            }

            Favorite::firstOrCreate([
                'client_id' => $clients[$clientIndex]->id,
                'provider_id' => $providers[$providerIndex]->id,
            ]);
        }
    }
}
