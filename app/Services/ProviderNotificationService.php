<?php

namespace App\Services;

use App\Helpers\PaymentLogger;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Provider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * All provider-facing alerts for orders live here and are ONLY reachable
 * through the payment completion pipeline (OrderPaid -> NotifyProviderOfPaidOrder).
 */
class ProviderNotificationService
{
    public const CATEGORY_NEW_ORDER = 'new_order';

    /**
     * Notification categories only the server-side payment pipeline may create.
     */
    public const RESERVED_CATEGORIES = [
        self::CATEGORY_NEW_ORDER,
        'order',
        'order_paid',
        'paid_order',
    ];

    public function __construct(protected SmsService $smsService)
    {
    }

    /**
     * Alert the provider that a paid order is waiting for acceptance.
     */
    public function notifyNewPaidOrder(Order $order): void
    {
        // Hard guard: unpaid orders never produce provider alerts.
        if (!$order->isVisibleToProvider()) {
            Log::warning('Provider notification suppressed for unpaid order', ['order_id' => $order->id]);
            return;
        }

        $provider = $order->provider ?: Provider::find($order->provider_id);

        if (!$provider) {
            Log::warning('Provider notification skipped - provider not found', ['order_id' => $order->id]);
            return;
        }

        $this->createDatabaseNotification($order, $provider);
        $this->sendSms($order, $provider);
        $this->sendPush($order, $provider);

        PaymentLogger::info('Provider notified of paid order', [
            'correlation_id' => PaymentLogger::correlationId($order->id),
            'internal_order_id' => $order->id,
            'provider_id' => $provider->id,
        ]);
    }

    /**
     * In-app notification (shown by GET /api/vendor/notifications).
     * Idempotent per order so a retried job never duplicates the alert.
     */
    protected function createDatabaseNotification(Order $order, Provider $provider): void
    {
        $exists = Notification::where('provider_id', $provider->id)
            ->where('category', self::CATEGORY_NEW_ORDER)
            ->where('content', 'like', '%' . $this->orderLabel($order) . '%')
            ->exists();

        if ($exists) {
            return;
        }

        Notification::create([
            'client_id' => $order->userId,
            'provider_id' => $provider->id,
            'title' => 'طلب جديد مدفوع',
            'content' => sprintf(
                'لديك طلب جديد %s بقيمة %s ر.س تم دفعه عبر مصرف الراجحي، بانتظار قبولك.',
                $this->orderLabel($order),
                number_format((float) $order->total, 2)
            ),
            'sender_type' => 'client',
            'category' => self::CATEGORY_NEW_ORDER,
        ]);
    }

    protected function sendSms(Order $order, Provider $provider): void
    {
        if (!config('orders.notify_provider.sms') || empty($provider->phone)) {
            return;
        }

        try {
            $this->smsService->send(
                $provider->phone,
                sprintf('عابر: لديك طلب جديد مدفوع %s بقيمة %s ر.س بانتظار قبولك.', $this->orderLabel($order), number_format((float) $order->total, 2))
            );
        } catch (Throwable $e) {
            Log::error('Provider order SMS failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Push notification hook. The project has no FCM integration (no device
     * token storage / Firebase credentials) yet; the WebSocket broadcast of
     * App\Events\OrderPaid covers realtime delivery. Wire FCM here when added.
     */
    protected function sendPush(Order $order, Provider $provider): void
    {
        if (!config('orders.notify_provider.push')) {
            return;
        }

        Log::info('Provider push notification requested (FCM not configured)', [
            'order_id' => $order->id,
            'provider_id' => $provider->id,
        ]);
    }

    protected function orderLabel(Order $order): string
    {
        return $order->reference ?: '#' . $order->id;
    }
}
