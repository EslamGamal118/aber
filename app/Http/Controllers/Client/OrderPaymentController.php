<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Web checkout: sends an authenticated client straight to the Al Rajhi Bank
 * hosted payment page for one of their unpaid orders.
 */
class OrderPaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected PaymentService $paymentService,
    ) {
    }

    public function pay(Order $order): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        abort_unless($client && (int) $order->userId === (int) $client->id, 403);

        if ($order->isPaid()) {
            return redirect()->to(URL::signedRoute('payment.result', ['order' => $order->id, 'status' => 'success']));
        }

        if (!$order->isPayable()) {
            return redirect()->route('client.dashboard')->with('error', 'لا يمكن دفع هذا الطلب.');
        }

        $payment = $this->paymentGateway->sendPayment($order);

        if (!$payment['success']) {
            return redirect()->route('client.dashboard')
                ->with('error', 'تعذر بدء عملية الدفع عبر مصرف الراجحي، يرجى المحاولة مرة أخرى.');
        }

        $this->paymentService->recordPaymentInitiation($order, $payment);

        // Straight to the Al Rajhi Hosted Payment Page
        return redirect()->away($payment['url']);
    }
}
