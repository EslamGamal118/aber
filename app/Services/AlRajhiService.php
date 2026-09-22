<?php

namespace App\Services;

use App\Helpers\PaymentLogger;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Throwable;

/**
 * Al Rajhi Bank Hosted Payment Page integration.
 *
 * Flow:
 *  1. sendPayment()  -> POST encrypted "trandata" to /pg/payment/hosted.htm,
 *                       receive "<PaymentID>:<HostedPageURL>" and hand the
 *                       customer the hosted page URL.
 *  2. Customer pays on the bank page.
 *  3. Bank POSTs encrypted "trandata" to route('payment.callback') (or
 *     route('payment.failed')) -> callBack() decrypts it and, on CAPTURED,
 *     completes the order through PaymentService.
 */
class AlRajhiService extends BasePaymentService implements PaymentGatewayInterface
{
    public const RESULT_CAPTURED = 'CAPTURED';

    protected AlRajhiEncryptionService $encryptionService;
    protected PaymentService $paymentService;

    protected string $id;
    protected string $password;
    protected string $currencyCode;

    public function __construct(PaymentService $paymentService, AlRajhiEncryptionService $encryptionService)
    {
        $this->paymentService = $paymentService;
        $this->encryptionService = $encryptionService;

        $this->base_url = rtrim((string) config('services.alrajhi.base_url'), '/');
        $this->id = (string) config('services.alrajhi.transportal_id');
        $this->password = (string) config('services.alrajhi.password');
        $this->currencyCode = (string) config('services.alrajhi.currency_code', '682');

        $this->header = [
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
        ];
    }

    /**
     * Request a hosted payment page URL for the order.
     *
     * @return array{success: bool, url: string, payment_id: string|null, message: string|null}
     */
    public function sendPayment(Order $order): array
    {
        $correlationId = PaymentLogger::correlationId($order->id);
        $trackId = $order->id . '_' . time();

        $plainData = [[
            'id' => $this->id,
            'password' => $this->password,
            'action' => '1', // 1 = Purchase
            'currencyCode' => $this->currencyCode,
            'errorURL' => route('payment.failed'),
            'responseURL' => route('payment.callback'),
            'trackId' => $trackId,
            'amt' => number_format((float) $order->total, 2, '.', ''),
        ] + $this->userDefinedFields([
            // udf1: internal order id (callBack() falls back to it when trackId is missing)
            1 => (string) $order->id,
            // udf2: human readable reference; "#CKS9NO1D" -> "CKS9NO1D"
            2 => (string) $order->reference,
            // udf3: customer id
            3 => (string) $order->userId,
        ])];

        $encryptedRequest = [[
            'id' => $this->id,
            'trandata' => $this->encryptionService->encrypt(json_encode($plainData)),
            'errorURL' => route('payment.failed'),
            'responseURL' => route('payment.callback'),
        ]];

        PaymentLogger::info('Al Rajhi payment initiation request', [
            'correlation_id' => $correlationId,
            'internal_order_id' => $order->id,
            'track_id' => $trackId,
            'amount' => $plainData[0]['amt'],
            'currency' => $this->currencyCode,
        ]);

        try {
            $response = $this->buildRequest('POST', '/pg/payment/hosted.htm', $encryptedRequest);
            $responseData = $response->getData(true);
        } catch (Throwable $e) {
            PaymentLogger::error('Al Rajhi payment initiation exception', [
                'correlation_id' => $correlationId,
                'internal_order_id' => $order->id,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);

            return $this->failedInitiation($e->getMessage());
        }

        $result = $responseData['data'][0]['result'] ?? null;
        $status = $responseData['data'][0]['status'] ?? null;

        // Success payload: "<PaymentID>:<HostedPageURL>"
        if (($responseData['success'] ?? false) && is_string($result) && str_contains($result, ':')) {
            [$paymentId, $url] = explode(':', $result, 2);
            $hostedPageUrl = $url . '?PaymentID=' . $paymentId;

            PaymentLogger::info('Al Rajhi hosted page URL generated', [
                'correlation_id' => $correlationId,
                'internal_order_id' => $order->id,
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => true,
                'url' => $hostedPageUrl,
                'payment_id' => $paymentId,
                'message' => null,
            ];
        }

        PaymentLogger::error('Al Rajhi payment initiation failed', [
            'correlation_id' => $correlationId,
            'internal_order_id' => $order->id,
            'status_code' => $responseData['status'] ?? null,
            'gateway_status' => $status,
            'gateway_result' => is_string($result) ? $result : null,
            'error_text' => $responseData['data'][0]['errorText'] ?? $responseData['message'] ?? null,
        ]);

        return $this->failedInitiation(
            $responseData['data'][0]['errorText'] ?? $responseData['message'] ?? 'Payment gateway unavailable'
        );
    }

    /**
     * Handle the bank's response/error callback.
     *
     * Returns the matched Order (with payment state updated) or null when the
     * payload cannot be decrypted / matched to an order.
     */
    public function callBack(Request $request): ?Order
    {
        $paymentDetails = $this->decodeCallback($request);

        if (empty($paymentDetails)) {
            PaymentLogger::error('Al Rajhi callback payload could not be decoded', [
                'correlation_id' => PaymentLogger::correlationId(),
                'ip' => $request->ip(),
                'has_trandata' => $request->filled('trandata'),
            ]);
            return null;
        }

        $orderId = $this->resolveOrderId($paymentDetails);
        $order = $orderId ? $this->findOrder($orderId) : null;

        if (!$order) {
            PaymentLogger::error('Al Rajhi callback: order not found', [
                'correlation_id' => PaymentLogger::correlationId(),
                'track_id' => $paymentDetails['trackId'] ?? null,
                'result' => $paymentDetails['result'] ?? null,
            ]);
            return null;
        }

        $result = strtoupper((string) ($paymentDetails['result'] ?? ''));

        PaymentLogger::info('Al Rajhi callback received', [
            'correlation_id' => PaymentLogger::correlationId($order->id),
            'internal_order_id' => $order->id,
            'result' => $result,
            'payment_id' => $paymentDetails['paymentId'] ?? null,
            'transaction_id' => $paymentDetails['tranId'] ?? null,
        ]);

        if ($result === self::RESULT_CAPTURED) {
            $this->paymentService->completeOrderPayment($order, $paymentDetails);
        } else {
            $this->paymentService->failOrderPayment($order, $paymentDetails);
        }

        return $order;
    }

    /**
     * Whether the order's payment has been captured.
     */
    public function isPaid(?Order $order): bool
    {
        return $order !== null && $order->payment_status === Order::PAYMENT_STATUS_PAID;
    }

    /**
     * Decrypt and normalise the callback "trandata" payload into a flat array.
     */
    protected function decodeCallback(Request $request): array
    {
        $trandata = $request->input('trandata');

        if (!is_string($trandata) || $trandata === '') {
            return [];
        }

        try {
            $decrypted = $this->encryptionService->decrypt($trandata);
        } catch (Throwable $e) {
            return [];
        }

        if ($decrypted === false || $decrypted === '') {
            return [];
        }

        $data = json_decode(urldecode($decrypted), true);

        if (!is_array($data)) {
            return [];
        }

        // Payload is normally a single-element list: [{ result, trackId, ... }]
        return isset($data[0]) && is_array($data[0]) ? $data[0] : $data;
    }

    /**
     * Build udf1..udf5 for the gateway. IPAY rejects a UDF with
     * "IPAY0100029 - Invalid user defined fieldN" when it contains characters
     * outside [A-Za-z0-9 ._@-] or exceeds 255 chars, so every value is
     * whitelisted and fields that end up empty are omitted entirely.
     *
     * @param  array<int, string|null> $values  index (1-5) => raw value
     * @return array<string, string>            e.g. ['udf1' => '42', 'udf2' => 'CKS9NO1D']
     */
    protected function userDefinedFields(array $values): array
    {
        $fields = [];

        foreach ($values as $index => $value) {
            if ($index < 1 || $index > 5) {
                continue;
            }

            $clean = self::sanitizeUdf($value);

            if ($clean !== '') {
                $fields['udf' . $index] = $clean;
            }
        }

        return $fields;
    }

    /**
     * Strip everything the gateway does not accept in a user defined field.
     */
    public static function sanitizeUdf(?string $value, int $maxLength = 255): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 ._@-]/', '', (string) $value) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        return mb_substr($clean, 0, $maxLength);
    }

    protected function findOrder(int $orderId): ?Order
    {
        return Order::find($orderId);
    }

    /**
     * Track id format is "<orderId>_<timestamp>"; udf1 also carries the order id.
     */
    protected function resolveOrderId(array $paymentDetails): ?int
    {
        $trackId = (string) ($paymentDetails['trackId'] ?? '');
        $fromTrack = $trackId !== '' ? explode('_', $trackId)[0] : null;
        $orderId = $fromTrack ?: ($paymentDetails['udf1'] ?? null);

        return is_numeric($orderId) ? (int) $orderId : null;
    }

    protected function failedInitiation(?string $message): array
    {
        return [
            'success' => false,
            'url' => route('payment.failed'),
            'payment_id' => null,
            'message' => $message,
        ];
    }
}
