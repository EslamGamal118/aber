<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired ONLY from PaymentService::completeOrderPayment() after the Al Rajhi
 * "CAPTURED" callback has been committed. This is the single entry point for
 * every provider-facing side effect of a new order (feed, DB notification,
 * SMS, WebSocket broadcast).
 */
class OrderPaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    /**
     * Private WebSocket channel of the provider that owns the order.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('provider.' . $this->order->provider_id)];
    }

    public function broadcastAs(): string
    {
        return 'order.paid';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'status' => $this->order->status,
            'payment_status' => $this->order->payment_status,
            'total' => $this->order->total,
            'paid_at' => optional($this->order->paid_at)->toIso8601String(),
        ];
    }
}
