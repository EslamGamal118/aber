<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Interfaces\PaymentGatewayInterface;
use App\Listeners\NotifyProviderOfPaidOrder;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Provider;
use App\Services\AlRajhiEncryptionService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Guarantees the payment-first workflow:
 *   create order  -> pending_payment, hidden from provider, no provider alerts
 *   CAPTURED      -> paid + pending, visible, provider alerted exactly once
 *   NOT CAPTURED  -> still hidden, no alerts
 */
class PaymentFirstOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Client $client;
    protected Provider $provider;
    protected Product $product;

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

        Http::fake([
            '*/pg/payment/hosted.htm' => Http::response([[
                'status' => '1',
                'result' => '100200300:https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm',
            ]], 200),
        ]);

        $this->client = Client::create(['name' => 'Client', 'email' => 'c@example.com', 'phone' => '0500000001', 'password' => 'secret']);
        $this->provider = Provider::create(['name' => 'Truck', 'email' => 'p@example.com', 'phone' => '0500000002', 'password' => 'secret']);
        $this->product = Product::create(['name' => 'Burger', 'price' => 25]);
    }

    /* ------------------------------------------------------------------ */
    /* 1. Order creation                                                   */
    /* ------------------------------------------------------------------ */

    public function test_store_creates_pending_payment_order_and_returns_hosted_page_url_without_alerting_provider(): void
    {
        Event::fake([OrderPaid::class]);
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/client/orders', [
            'provider_id' => $this->provider->id,
            'items' => [['elementId' => $this->product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.status', Order::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('order.payment_status', Order::PAYMENT_STATUS_PENDING)
            ->assertJsonPath('order.is_paid', false)
            ->assertJsonPath('order.payment_method', Order::PAYMENT_METHOD_ALRAJHI)
            ->assertJsonPath('url', 'https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm?PaymentID=100200300');

        $order = Order::first();
        $this->assertSame(50.0, (float) $order->total);
        $this->assertFalse($order->isVisibleToProvider());

        // No provider-facing side effects at creation time
        Event::assertNotDispatched(OrderPaid::class);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_unpaid_order_is_invisible_to_the_provider(): void
    {
        $order = $this->createPendingOrder();

        Sanctum::actingAs($this->provider);

        $this->getJson('/api/vendor/orders')->assertOk()->assertJsonCount(0, 'orders');
        $this->getJson('/api/vendor/orders/' . $order->id)->assertNotFound();
        $this->putJson('/api/vendor/orders/' . $order->id . '/status', ['status' => 'accepted'])->assertNotFound();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_client_cannot_push_an_unpaid_order_into_the_provider_feed(): void
    {
        $order = $this->createPendingOrder();
        Sanctum::actingAs($this->client);

        $this->putJson('/api/client/orders/' . $order->id, ['status' => 'pending'])->assertStatus(422);
        $this->putJson('/api/client/orders/' . $order->id, ['status' => 'accepted'])->assertStatus(422);

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
        $this->assertFalse($order->fresh()->isVisibleToProvider());

        // ...but may still cancel it
        $this->putJson('/api/client/orders/' . $order->id . '/cancel')->assertOk();
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_client_cannot_create_new_order_notifications_for_providers(): void
    {
        Sanctum::actingAs($this->client);

        $this->postJson('/api/client/notifications', [
            'provider_id' => $this->provider->id,
            'title' => 'New order',
            'content' => 'fake',
            'category' => 'new_order',
        ])->assertStatus(422);

        $this->assertDatabaseCount('notifications', 0);
    }

    /* ------------------------------------------------------------------ */
    /* 2. CAPTURED -> release + notify                                     */
    /* ------------------------------------------------------------------ */

    public function test_captured_callback_releases_order_to_provider_and_notifies_exactly_once(): void
    {
        Event::fake([OrderPaid::class]);
        $order = $this->createPendingOrder();

        $response = $this->post('/payment/callback', ['trandata' => $this->bankTrandata([
            'result' => 'CAPTURED',
            'trackId' => $order->id . '_1700000000',
            'paymentId' => 'PAY-1',
            'tranId' => 'TRX-1',
        ])]);

        $response->assertOk();
        $this->assertStringContainsString('status=success', $response->getContent());

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_PENDING, $order->status); // b. active / awaiting provider acceptance
        $this->assertSame('TRX-1', $order->transaction_id);
        $this->assertSame('PAY-1', $order->payment_reference);
        $this->assertNotNull($order->paid_at);
        $this->assertTrue($order->isVisibleToProvider());

        // c. provider alert fired once, on the provider's private channel
        Event::assertDispatchedTimes(OrderPaid::class, 1);
        Event::assertDispatched(OrderPaid::class, fn (OrderPaid $e) => $e->order->id === $order->id
            && $e->broadcastOn()[0]->name === 'private-provider.' . $this->provider->id);

        // Duplicate bank callback (retry) must not re-notify
        $this->post('/payment/callback', ['trandata' => $this->bankTrandata([
            'result' => 'CAPTURED',
            'trackId' => $order->id . '_1700000001',
            'paymentId' => 'PAY-1',
            'tranId' => 'TRX-1',
        ])])->assertOk();

        Event::assertDispatchedTimes(OrderPaid::class, 1);
    }

    public function test_paid_order_appears_in_provider_feed_and_can_be_accepted(): void
    {
        $order = $this->createPendingOrder();
        app(PaymentService::class)->completeOrderPayment($order, ['tranId' => 'TRX-2', 'paymentId' => 'PAY-2']);

        Sanctum::actingAs($this->provider);

        $this->getJson('/api/vendor/orders')->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $order->id)
            ->assertJsonPath('orders.0.status', Order::STATUS_PENDING);

        $this->getJson('/api/vendor/orders/' . $order->id)->assertOk();

        $this->putJson('/api/vendor/orders/' . $order->id . '/status', ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('order.status', 'accepted');
    }

    public function test_listener_creates_provider_database_notification_once(): void
    {
        $order = $this->createPendingOrder();
        app(PaymentService::class)->completeOrderPayment($order->fresh(), ['tranId' => 'TRX-3']);

        $listener = app(NotifyProviderOfPaidOrder::class);
        $listener->handle(new OrderPaid($order->fresh()));
        $listener->handle(new OrderPaid($order->fresh())); // queue retry

        $this->assertDatabaseCount('notifications', 1);
        $notification = Notification::first();
        $this->assertSame($this->provider->id, $notification->provider_id);
        $this->assertSame($this->client->id, $notification->client_id);
        $this->assertSame('new_order', $notification->category);
        $this->assertSame('client', $notification->sender_type);

        Sanctum::actingAs($this->provider);
        $this->getJson('/api/vendor/notifications')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_listener_refuses_to_notify_for_unpaid_order(): void
    {
        $order = $this->createPendingOrder();

        app(NotifyProviderOfPaidOrder::class)->handle(new OrderPaid($order));

        $this->assertDatabaseCount('notifications', 0);
    }

    /* ------------------------------------------------------------------ */
    /* 3. Failed / cancelled payment                                       */
    /* ------------------------------------------------------------------ */

    public function test_failed_payment_keeps_order_hidden_and_silent(): void
    {
        Event::fake([OrderPaid::class]);
        $order = $this->createPendingOrder();

        // Declined on the hosted page -> responseURL with NOT CAPTURED
        $this->post('/payment/callback', ['trandata' => $this->bankTrandata([
            'result' => 'NOT CAPTURED',
            'trackId' => $order->id . '_1700000000',
        ])])->assertOk();

        // Cancelled by the customer -> errorURL with plain payload
        $this->post('/payment/failed', [
            'trackId' => $order->id . '_1700000000',
            'ErrorText' => 'Transaction cancelled by user',
        ])->assertOk();

        $order->refresh();
        $this->assertFalse($order->is_paid);
        $this->assertSame(Order::PAYMENT_STATUS_FAILED, $order->payment_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertFalse($order->isVisibleToProvider());

        Event::assertNotDispatched(OrderPaid::class);
        $this->assertDatabaseCount('notifications', 0);

        Sanctum::actingAs($this->provider);
        $this->getJson('/api/vendor/orders')->assertOk()->assertJsonCount(0, 'orders');

        // The customer can retry the payment
        Sanctum::actingAs($this->client);
        $this->postJson('/api/client/orders/' . $order->id . '/pay')->assertOk()->assertJsonStructure(['url']);
    }

    public function test_failed_callback_never_downgrades_a_paid_order(): void
    {
        $order = $this->createPendingOrder();
        app(PaymentService::class)->completeOrderPayment($order, ['tranId' => 'TRX-4']);

        $this->post('/payment/failed', ['trandata' => $this->bankTrandata([
            'result' => 'NOT CAPTURED',
            'trackId' => $order->id . '_1700000000',
        ])])->assertOk();

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }

    /* ------------------------------------------------------------------ */
    /* helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function createPendingOrder(): Order
    {
        Sanctum::actingAs($this->client);

        $this->postJson('/api/client/orders', [
            'provider_id' => $this->provider->id,
            'items' => [['elementId' => $this->product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::latest('id')->first();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);

        // Reset auth so later requests must authenticate explicitly
        $this->app['auth']->forgetGuards();

        return $order;
    }

    private function bankTrandata(array $payload): string
    {
        return app(AlRajhiEncryptionService::class)->encrypt(json_encode([$payload]));
    }
}
