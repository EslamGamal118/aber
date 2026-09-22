<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\ProviderNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Background job that alerts the provider about a newly PAID order.
 * Auto-discovered by Laravel's event discovery (app/Listeners).
 */
class NotifyProviderOfPaidOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(protected ProviderNotificationService $notifier)
    {
    }

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->fresh();

        // Defence in depth: never alert a provider about an order that is not paid,
        // even if an event somehow reaches the queue for an unpaid order.
        if (!$order || !$order->isVisibleToProvider()) {
            return;
        }

        $this->notifier->notifyNewPaidOrder($order);
    }
}
