<?php

use App\Models\Provider;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| App\Events\OrderPaid is broadcast on "private-provider.{id}" once an order
| is paid via Al Rajhi. Only the owning provider may subscribe.
*/

Broadcast::channel('provider.{providerId}', function ($user, $providerId) {
    return $user instanceof Provider && (int) $user->id === (int) $providerId;
}, ['guards' => ['sanctum', 'provider']]);
