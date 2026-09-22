<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentLogger;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Al Rajhi Bank hosted payment page callbacks.
 *
 *  payment.callback  <- bank "responseURL" (successful / declined transactions)
 *  payment.failed    <- bank "errorURL"    (gateway level errors)
 *  payment.result    -> customer facing result page (web + mobile WebView)
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $gateway,
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * Bank response URL. Processes the encrypted "trandata" and completes the
     * order on CAPTURED via PaymentService::completeOrderPayment().
     */
    public function callback(Request $request): Response
    {
        $order = $this->gateway->callBack($request);

        return $this->respondWithRedirect($request, $this->resultUrl($order));
    }

    /**
     * Bank error URL. The bank may send encrypted "trandata" here as well, or a
     * plain (trackId / ErrorText / paymentId) payload for gateway level errors.
     */
    public function failed(Request $request): Response
    {
        $order = $request->filled('trandata')
            ? $this->gateway->callBack($request)
            : $this->failFromPlainPayload($request);

        return $this->respondWithRedirect($request, $this->resultUrl($order, false));
    }

    /**
     * Customer facing result page. Reads the persisted payment state so the
     * page can never be tricked into showing "paid" for an unpaid order.
     */
    public function result(Request $request, ?Order $order = null)
    {
        $order?->load('items');

        return view('payment.result', [
            'order' => $order,
            'paid' => $order?->isPaid() ?? false,
            'status' => $order?->isPaid() ? 'success' : 'failed',
        ]);
    }

    /**
     * Mark the order failed when the bank's error URL is hit without trandata.
     */
    protected function failFromPlainPayload(Request $request): ?Order
    {
        $trackId = (string) $request->input('trackId', $request->input('trackid', ''));
        $orderId = $trackId !== '' ? explode('_', $trackId)[0] : $request->input('udf1');
        $order = is_numeric($orderId) ? Order::find((int) $orderId) : null;

        PaymentLogger::error('Al Rajhi error URL hit', [
            'correlation_id' => PaymentLogger::correlationId($order?->id),
            'internal_order_id' => $order?->id,
            'track_id' => $trackId ?: null,
            'error_code' => $request->input('Error', $request->input('error')),
            'error_text' => $request->input('ErrorText', $request->input('errorText')),
            'payment_id' => $request->input('paymentId', $request->input('paymentid')),
        ]);

        if ($order) {
            $this->paymentService->failOrderPayment($order, [
                'result' => 'ERROR',
                'error' => $request->input('Error', $request->input('error')),
                'errorText' => $request->input('ErrorText', $request->input('errorText')),
                'paymentId' => $request->input('paymentId', $request->input('paymentid')),
            ]);
        }

        return $order;
    }

    /**
     * Al Rajhi's gateway posts to the response/error URL server-to-server and
     * expects a plain "REDIRECT=<url>" body, which it then uses to redirect the
     * customer's browser. Some merchant profiles are configured for a direct
     * browser POST instead; ALRAJHI_CALLBACK_MODE=http_redirect handles those.
     */
    protected function respondWithRedirect(Request $request, string $url): Response
    {
        if (config('services.alrajhi.callback_mode') === 'http_redirect') {
            return redirect()->to($url);
        }

        return response('REDIRECT=' . $url, 200, ['Content-Type' => 'text/plain']);
    }

    protected function resultUrl(?Order $order, bool $allowSuccess = true): string
    {
        $paid = $allowSuccess && $order?->isPaid();

        return URL::signedRoute('payment.result', [
            'order' => $order?->id,
            'status' => $paid ? 'success' : 'failed',
        ]);
    }
}
