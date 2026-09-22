<?php

namespace Tests\Feature;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\AlRajhiEncryptionService;
use App\Services\AlRajhiService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AlRajhiPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.alrajhi', [
            'base_url' => 'https://securepayments.alrajhibank.com.sa',
            'transportal_id' => 'TEST_ID',
            'password' => 'TEST_PASSWORD',
            'encryption_key' => str_repeat('k', 32),
            'iv' => 'PGKEYENCDECIVSPC',
            'currency_code' => '682',
            'callback_mode' => 'redirect_text',
        ]);
    }

    public function test_al_rajhi_is_bound_as_the_only_payment_gateway(): void
    {
        $this->assertInstanceOf(AlRajhiService::class, app(PaymentGatewayInterface::class));
        $this->assertFalse(class_exists(\App\Services\PaymobService::class));
        $this->assertFalse(class_exists(\App\Http\Controllers\Api\PaymobCallbackController::class));
        $this->assertNull(config('services.paymob'));
    }

    public function test_send_payment_returns_hosted_payment_page_url(): void
    {
        Http::fake([
            '*/pg/payment/hosted.htm' => Http::response([[
                'status' => '1',
                'result' => '100200300:https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm',
            ]], 200),
        ]);

        $order = $this->makeOrder(42, 150.5);

        $result = app(PaymentGatewayInterface::class)->sendPayment($order);

        $this->assertTrue($result['success']);
        $this->assertSame('100200300', $result['payment_id']);
        $this->assertSame(
            'https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm?PaymentID=100200300',
            $result['url']
        );

        // The request carries the encrypted trandata + our named callback routes
        Http::assertSent(function ($request) use ($order) {
            $body = $request->data()[0] ?? [];
            $decrypted = json_decode(app(AlRajhiEncryptionService::class)->decrypt($body['trandata']), true)[0];

            return $body['id'] === 'TEST_ID'
                && $body['responseURL'] === route('payment.callback')
                && $body['errorURL'] === route('payment.failed')
                && $decrypted['amt'] === '150.50'
                && $decrypted['currencyCode'] === '682'
                && $decrypted['action'] === '1'
                && str_starts_with($decrypted['trackId'], $order->id . '_')
                && $decrypted['udf1'] === (string) $order->id;
        });
    }

    /**
     * Regression: udf2 carried the raw "#CKS9NO1D" reference and the gateway
     * answered "!ERROR!-IPAY0100029-Invalid user defined field2."
     */
    public function test_user_defined_fields_are_sanitized_for_the_gateway(): void
    {
        Http::fake([
            '*/pg/payment/hosted.htm' => Http::response([[
                'status' => '1',
                'result' => '1:https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm',
            ]], 200),
        ]);

        $order = $this->makeOrder(42, 10, ['reference' => '#CKS9NO1D', 'userId' => 7]);

        app(PaymentGatewayInterface::class)->sendPayment($order);

        Http::assertSent(function ($request) {
            $body = $request->data()[0] ?? [];
            $plain = json_decode(app(AlRajhiEncryptionService::class)->decrypt($body['trandata']), true)[0];

            return $plain['udf1'] === '42'
                && $plain['udf2'] === 'CKS9NO1D'   // "#" stripped
                && $plain['udf3'] === '7'
                && !array_key_exists('udf4', $plain)
                && !array_key_exists('udf5', $plain);
        });
    }

    public function test_udf_sanitizer_whitelists_characters_and_caps_length(): void
    {
        $this->assertSame('CKS9NO1D', AlRajhiService::sanitizeUdf('#CKS9NO1D'));
        $this->assertSame('ORD-12_a.b@c', AlRajhiService::sanitizeUdf('ORD-12_a.b@c'));
        $this->assertSame('a b', AlRajhiService::sanitizeUdf("  a <&>%'\"  b  "));
        $this->assertSame('', AlRajhiService::sanitizeUdf(null));
        $this->assertSame('', AlRajhiService::sanitizeUdf('###'));
        $this->assertSame(255, strlen(AlRajhiService::sanitizeUdf(str_repeat('x', 300))));
    }

    public function test_send_payment_failure_is_reported_without_throwing(): void
    {
        Http::fake([
            '*/pg/payment/hosted.htm' => Http::response([[
                'status' => '0',
                'errorText' => 'Invalid Transportal ID',
            ]], 200),
        ]);

        $result = app(PaymentGatewayInterface::class)->sendPayment($this->makeOrder(7, 10));

        $this->assertFalse($result['success']);
        $this->assertSame(route('payment.failed'), $result['url']);
        $this->assertSame('Invalid Transportal ID', $result['message']);
    }

    public function test_captured_callback_completes_order_through_payment_service(): void
    {
        $order = $this->makeOrder(55, 99);

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('completeOrderPayment')
            ->once()
            ->withArgs(fn (Order $o, array $details) => $o->id === 55
                && $details['result'] === 'CAPTURED'
                && $details['tranId'] === 'TRX-1'
                && $details['paymentId'] === 'PAY-1')
            ->andReturn(true);
        $paymentService->shouldNotReceive('failOrderPayment');

        $gateway = $this->gatewayWithStubbedOrder($paymentService, $order);

        $result = $gateway->callBack($this->bankRequest([
            'result' => 'CAPTURED',
            'trackId' => '55_1700000000',
            'paymentId' => 'PAY-1',
            'tranId' => 'TRX-1',
            'udf1' => '55',
        ]));

        $this->assertSame($order, $result);
    }

    public function test_non_captured_callback_marks_order_failed(): void
    {
        $order = $this->makeOrder(56, 10);

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldNotReceive('completeOrderPayment');
        $paymentService->shouldReceive('failOrderPayment')
            ->once()
            ->withArgs(fn (Order $o, array $details) => $o->id === 56 && $details['result'] === 'NOT CAPTURED')
            ->andReturn(true);

        $gateway = $this->gatewayWithStubbedOrder($paymentService, $order);

        $this->assertSame($order, $gateway->callBack($this->bankRequest([
            'result' => 'NOT CAPTURED',
            'trackId' => '56_1700000000',
        ])));
    }

    public function test_undecodable_or_unmatched_callback_returns_null(): void
    {
        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldNotReceive('completeOrderPayment');
        $paymentService->shouldNotReceive('failOrderPayment');

        $gateway = $this->gatewayWithStubbedOrder($paymentService, $this->makeOrder(1, 1));

        // Garbage trandata
        $this->assertNull($gateway->callBack(Request::create('/payment/callback', 'POST', ['trandata' => 'zz'])));
        // No trandata at all
        $this->assertNull($gateway->callBack(Request::create('/payment/callback', 'POST')));
        // Valid payload for an order that does not exist
        $this->assertNull($gateway->callBack($this->bankRequest(['result' => 'CAPTURED', 'trackId' => '999_1'])));
    }

    public function test_callback_route_replies_with_bank_redirect_instruction(): void
    {
        $paid = $this->makeOrder(9, 20, ['payment_status' => Order::PAYMENT_STATUS_PAID, 'is_paid' => true]);

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('callBack')->once()->andReturn($paid);
        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $response = $this->post('/payment/callback', ['trandata' => 'deadbeef']);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringStartsWith('REDIRECT=' . url('/payment/result/9'), $response->getContent());
        $this->assertStringContainsString('status=success', $response->getContent());
        $this->assertStringContainsString('signature=', $response->getContent());
    }

    public function test_failed_route_never_reports_success(): void
    {
        $paid = $this->makeOrder(9, 20, ['payment_status' => Order::PAYMENT_STATUS_PAID, 'is_paid' => true]);

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('callBack')->once()->andReturn($paid);
        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $response = $this->post('/payment/failed', ['trandata' => 'deadbeef']);

        $response->assertOk();
        $this->assertStringContainsString('status=failed', $response->getContent());
    }

    public function test_http_redirect_callback_mode_issues_a_302(): void
    {
        config()->set('services.alrajhi.callback_mode', 'http_redirect');

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('callBack')->once()->andReturn(null);
        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $response = $this->post('/payment/callback', ['trandata' => 'deadbeef']);

        $response->assertRedirect();
        $this->assertStringContainsString('/payment/result', $response->headers->get('Location'));
        $this->assertStringContainsString('status=failed', $response->headers->get('Location'));
    }

    public function test_new_orders_default_to_al_rajhi(): void
    {
        $order = new Order();

        $this->assertSame('alrajhi', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertFalse($order->is_paid);
        $this->assertNotContains('paymob_order_id', $order->getFillable());
    }

    /**
     * Real AlRajhiService with only the DB lookup replaced by an in-memory order.
     */
    private function gatewayWithStubbedOrder(PaymentService $paymentService, Order $stub): AlRajhiService
    {
        return new class($paymentService, app(AlRajhiEncryptionService::class), $stub) extends AlRajhiService {
            public function __construct(PaymentService $ps, AlRajhiEncryptionService $enc, private Order $stub)
            {
                parent::__construct($ps, $enc);
            }

            protected function findOrder(int $orderId): ?Order
            {
                return $orderId === $this->stub->id ? $this->stub : null;
            }
        };
    }

    /**
     * Build the POST the bank sends to responseURL / errorURL.
     */
    private function bankRequest(array $payload): Request
    {
        return Request::create('/payment/callback', 'POST', [
            'trandata' => app(AlRajhiEncryptionService::class)->encrypt(json_encode([$payload])),
        ]);
    }

    private function makeOrder(int $id, float $total, array $extra = []): Order
    {
        $order = new Order();
        $order->forceFill(array_merge([
            'id' => $id,
            'reference' => '#TEST' . $id,
            'total' => $total,
            'status' => 'pending',
        ], $extra));
        $order->exists = true;

        return $order;
    }
}
