<?php

namespace App\Interfaces;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment for the given order.
     *
     * @return array{success: bool, url: string, payment_id?: string|null, message?: string|null}
     */
    public function sendPayment(Order $order): array;

    /**
     * Process the gateway callback and return the affected order (or null when
     * the payload could not be matched to an order / was not a successful capture).
     */
    public function callBack(Request $request): ?Order;
}
