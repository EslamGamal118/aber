<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment-first order workflow
    |--------------------------------------------------------------------------
    |
    | Orders are created as "pending_payment" and are hidden from providers
    | until Al Rajhi confirms the capture. Provider alerts are triggered only
    | from PaymentService::completeOrderPayment() via the OrderPaid event.
    |
    */

    'notify_provider' => [
        // In-app (database) notification is always created.
        // SMS to the provider's phone through SmsService (4jawaly).
        'sms' => (bool) env('ORDER_NOTIFY_PROVIDER_SMS', false),
        // Push hook (FCM not integrated yet - see ProviderNotificationService::sendPush()).
        'push' => (bool) env('ORDER_NOTIFY_PROVIDER_PUSH', false),
    ],

];
