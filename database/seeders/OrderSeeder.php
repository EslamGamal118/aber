<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Services\ProviderNotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orders covering every Al Rajhi payment state and order lifecycle state.
 *
 * Reference   Client  Provider  status           payment_status  Notes
 * ---------   ------  --------  ---------------  --------------  -----------------------------------------
 * #SEED0001   1       burger    pending_payment  pending         HPP URL issued, customer never paid (hidden from provider)
 * #SEED0002   2       coffee    pending_payment  failed          NOT CAPTURED / cancelled on hosted page (hidden, retryable)
 * #SEED0003   3       burger    pending          paid            CAPTURED 10 min ago - awaiting provider acceptance
 * #SEED0004   1       coffee    accepted         paid            Provider accepted, being prepared
 * #SEED0005   2       burger    for_delivery     paid            Out for delivery
 * #SEED0006   4       burger    done             paid            Completed yesterday
 * #SEED0007   3       coffee    done             paid            Completed last week (analytics history)
 * #SEED0008   1       burger    rejected         paid            Provider rejected (refund case)
 * #SEED0009   4       coffee    cancelled        pending         Client cancelled before paying
 * #SEED0010   2       burger    cancelled        paid            Client cancelled after paying (refund case)
 *
 * Re-runnable: previously seeded #SEED* orders are replaced.
 */
class OrderSeeder extends Seeder
{
    public const REFERENCE_PREFIX = '#SEED';

    /**
     * [reference => spec]. "items" => [[product name, qty, [option titles]], ...]
     */
    public const ORDERS = [
        '#SEED0001' => ['client' => 0, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_PENDING_PAYMENT, 'payment' => 'unpaid', 'minutes_ago' => 15,
            'items' => [['كلاسيك بيف برجر', 1, ['دبل']], ['بطاطس مقلية', 1, []], ['بيبسي', 2, []]]],
        '#SEED0002' => ['client' => 1, 'provider' => 'coffee@aber.test', 'status' => Order::STATUS_PENDING_PAYMENT, 'payment' => 'failed', 'minutes_ago' => 45,
            'items' => [['لاتيه', 2, ['كبير', 'شوفان']], ['كوكيز شوكولاتة', 1, []]]],
        '#SEED0003' => ['client' => 2, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_PENDING, 'payment' => 'paid', 'minutes_ago' => 10,
            'items' => [['كرسبي تشيكن برجر', 2, ['حار']], ['حلقات بصل', 1, []]]],
        '#SEED0004' => ['client' => 0, 'provider' => 'coffee@aber.test', 'status' => Order::STATUS_ACCEPTED, 'payment' => 'paid', 'minutes_ago' => 25,
            'items' => [['آيس لاتيه', 1, ['كبير']], ['سبانش لاتيه', 1, []]]],
        '#SEED0005' => ['client' => 1, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_FOR_DELIVERY, 'payment' => 'paid', 'minutes_ago' => 50,
            'items' => [['سموكي BBQ برجر', 1, []], ['ماشروم سويس برجر', 1, ['عادي']], ['عصير برتقال طازج', 2, []]], 'promo' => 10],
        '#SEED0006' => ['client' => 3, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_DONE, 'payment' => 'paid', 'minutes_ago' => 60 * 26,
            'items' => [['كلاسيك بيف برجر', 2, ['عادي', 'جبن إضافي']], ['بطاطس مقلية', 2, ['كبير']]]],
        '#SEED0007' => ['client' => 2, 'provider' => 'coffee@aber.test', 'status' => Order::STATUS_DONE, 'payment' => 'paid', 'minutes_ago' => 60 * 24 * 7,
            'items' => [['كولد برو', 1, []], ['إسبريسو', 1, ['دبل']]]],
        '#SEED0008' => ['client' => 0, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_REJECTED, 'payment' => 'paid', 'minutes_ago' => 60 * 5,
            'items' => [['جريلد تشيكن برجر', 1, []]], 'rejection_reason' => 'نفاد المخزون'],
        '#SEED0009' => ['client' => 3, 'provider' => 'coffee@aber.test', 'status' => Order::STATUS_CANCELLED, 'payment' => 'unpaid', 'minutes_ago' => 60 * 3,
            'items' => [['قهوة سعودية', 3, []]]],
        '#SEED0010' => ['client' => 1, 'provider' => 'burger@aber.test', 'status' => Order::STATUS_CANCELLED, 'payment' => 'paid', 'minutes_ago' => 60 * 30,
            'items' => [['ساندويتش بيض وجبن', 2, []], ['شاي كرك', 2, []]]],
    ];

    public function run(): void
    {
        $clients = Client::whereIn('email', array_column(ClientSeeder::CLIENTS, 'email'))->orderBy('id')->get()->values();
        $providers = ProviderSeeder::activeProviders()->keyBy('email');
        $products = Product::all()->keyBy('name');

        if ($clients->isEmpty() || $providers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('OrderSeeder skipped: run Client/Provider/Product seeders first.');
            return;
        }

        DB::transaction(function () use ($clients, $providers, $products) {
            $this->purgePreviousRun();

            foreach (self::ORDERS as $reference => $spec) {
                $client = $clients[$spec['client']] ?? null;
                $provider = $providers[$spec['provider']] ?? null;

                if (!$client || !$provider) {
                    continue;
                }

                $createdAt = Carbon::now()->subMinutes($spec['minutes_ago']);

                $order = new Order();
                $order->forceFill([
                    'reference' => $reference,
                    'userId' => $client->id,
                    'provider_id' => $provider->id,
                    'buyerName' => $client->name,
                    'status' => $spec['status'],
                    'promoCodeDiscount' => $spec['promo'] ?? 0,
                    'rejection_reason' => $spec['rejection_reason'] ?? null,
                    'total' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ] + $this->paymentAttributes($spec['payment'], $reference, $createdAt))->save();

                $total = $this->seedItems($order, $spec['items'], $products, $createdAt);

                $order->forceFill([
                    'total' => max($total - ($spec['promo'] ?? 0), 0),
                    'payment_link' => $spec['payment'] === 'unpaid'
                        ? 'https://securepayments.alrajhibank.com.sa/pg/paymentpage.htm?PaymentID=' . $this->fakePaymentId($reference)
                        : null,
                ])->save();

                if ($spec['payment'] === 'paid') {
                    $this->seedProviderNotification($order, $client->id, $provider->id, $createdAt);
                }
            }
        });
    }

    /**
     * Payment columns mirroring what AlRajhiService / PaymentService write.
     */
    protected function paymentAttributes(string $payment, string $reference, Carbon $createdAt): array
    {
        $paymentId = $this->fakePaymentId($reference);

        return match ($payment) {
            // Hosted page issued, awaiting the customer
            'unpaid' => [
                'is_paid' => false,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
                'payment_reference' => $paymentId,
                'transaction_id' => null,
                'paid_at' => null,
            ],
            // NOT CAPTURED / cancelled on the hosted page
            'failed' => [
                'is_paid' => false,
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
                'payment_reference' => $paymentId,
                'transaction_id' => null,
                'paid_at' => null,
            ],
            // CAPTURED
            'paid' => [
                'is_paid' => true,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
                'payment_reference' => $paymentId,
                'transaction_id' => $this->fakeTransactionId($reference),
                'paid_at' => $createdAt->copy()->addMinutes(2),
            ],
        };
    }

    /**
     * @param array<int, array{0: string, 1: int, 2: array<int, string>}> $items
     */
    protected function seedItems(Order $order, array $items, $products, Carbon $createdAt): float
    {
        $total = 0;

        foreach ($items as [$productName, $quantity, $optionNames]) {
            $product = $products[$productName] ?? null;
            if (!$product) {
                continue;
            }

            $options = $optionNames
                ? ProductOption::where('product_id', $product->id)->whereIn('option', $optionNames)->get()
                : collect();

            $additionPrice = (float) $options->sum('price');
            $lineTotal = ($product->price * $quantity) + $additionPrice;
            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'elementId' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'additions' => $options->isNotEmpty() ? json_encode($options->pluck('id')->all()) : null,
                'additionPrice' => $additionPrice,
                'total' => $lineTotal,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return $total;
    }

    /**
     * The in-app alert the OrderPaid pipeline would have produced for a paid order.
     */
    protected function seedProviderNotification(Order $order, int $clientId, int $providerId, Carbon $createdAt): void
    {
        Notification::create([
            'client_id' => $clientId,
            'provider_id' => $providerId,
            'title' => 'طلب جديد مدفوع',
            'content' => sprintf(
                'لديك طلب جديد %s بقيمة %s ر.س تم دفعه عبر مصرف الراجحي، بانتظار قبولك.',
                $order->reference,
                number_format((float) $order->total, 2)
            ),
            'sender_type' => 'client',
            'category' => ProviderNotificationService::CATEGORY_NEW_ORDER,
            'is_read' => $order->status !== Order::STATUS_PENDING,
            'created_at' => $createdAt->copy()->addMinutes(2),
            'updated_at' => $createdAt->copy()->addMinutes(2),
        ]);
    }

    protected function purgePreviousRun(): void
    {
        $orderIds = Order::where('reference', 'like', self::REFERENCE_PREFIX . '%')->pluck('id');

        if ($orderIds->isEmpty()) {
            return;
        }

        OrderItem::whereIn('order_id', $orderIds)->delete();
        Notification::where('category', ProviderNotificationService::CATEGORY_NEW_ORDER)
            ->where('content', 'like', '%' . self::REFERENCE_PREFIX . '%')
            ->delete();
        Order::whereIn('id', $orderIds)->delete();
    }

    protected function fakePaymentId(string $reference): string
    {
        return '1002' . str_pad((string) crc32($reference), 10, '0', STR_PAD_LEFT);
    }

    protected function fakeTransactionId(string $reference): string
    {
        return '5231' . substr(str_pad((string) crc32(strrev($reference)), 12, '0', STR_PAD_LEFT), 0, 12);
    }
}
