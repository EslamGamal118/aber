<?php

namespace App\Services;

use App\Events\OrderPaid;
use App\Helpers\PaymentLogger;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Gateway-agnostic order payment state transitions.
 *
 * Payment-first workflow: this service is the ONLY place where an order is
 * released to the provider (status pending_payment -> pending) and the ONLY
 * place that triggers provider notifications, and it does so strictly after a
 * successful capture (AlRajhiService::callBack() with result CAPTURED).
 */
class PaymentService
{
    /**
     * Mark an order as paid, release it to the provider and alert the provider.
     * Idempotent: an already paid order is left untouched and NOT re-notified.
     */
    public function completeOrderPayment(Order $order, array $paymentDetails = []): bool
    {
        $released = DB::transaction(function () use ($order, $paymentDetails) {
            $current = $this->lockOrder($order);

            if (!$current) {
                return false;
            }

            if ($current->payment_status === Order::PAYMENT_STATUS_PAID) {
                $order->setRawAttributes($current->getAttributes(), true);

                PaymentLogger::info('Order already paid - callback ignored', [
                    'correlation_id' => PaymentLogger::correlationId($order->id),
                    'internal_order_id' => $order->id,
                ]);

                return false; // nothing new happened, do not notify again
            }

            $order->fill([
                // a. payment confirmed
                'is_paid' => true,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
                'transaction_id' => $paymentDetails['tranId'] ?? $paymentDetails['transId'] ?? null,
                'payment_reference' => $paymentDetails['paymentId'] ?? $paymentDetails['paymentid'] ?? $current->payment_reference,
                'paid_at' => now(),
                'payment_link' => null,
                // b. release to the provider: "pending" = new order awaiting acceptance
                'status' => $this->releasedStatusFor($current),
            ])->save();

            PaymentLogger::info('Payment status updated to paid - order released to provider', [
                'correlation_id' => PaymentLogger::correlationId($order->id),
                'internal_order_id' => $order->id,
                'provider_id' => $order->provider_id,
                'status' => $order->status,
                'transaction_id' => $order->transaction_id,
                'payment_reference' => $order->payment_reference,
                'auth_code' => $paymentDetails['authCode'] ?? $paymentDetails['auth'] ?? null,
                'gateway_ref' => $paymentDetails['ref'] ?? null,
            ]);

            return true;
        });

        if ($released) {
            // c. provider alerts (DB notification, SMS, WebSocket) - only now.
            $this->notifyProviderOfPayment($order);
        }

        return $released || $order->isPaid();
    }

    /**
     * Fire the provider-facing side effects for a freshly paid order.
     * Runs after the surrounding transaction commits so listeners / queued jobs
     * never observe an un-committed "paid" state.
     */
    public function notifyProviderOfPayment(Order $order): void
    {
        if (!$order->isVisibleToProvider()) {
            return;
        }

        DB::afterCommit(function () use ($order) {
            OrderPaid::dispatch($order);
        });
    }

    /**
     * Record a failed / declined / cancelled payment attempt.
     * The order stays invisible to the provider and no provider alert is sent.
     * A paid order is never downgraded.
     */
    public function failOrderPayment(Order $order, array $paymentDetails = []): bool
    {
        return DB::transaction(function () use ($order, $paymentDetails) {
            $current = $this->lockOrder($order);

            if (!$current) {
                return false;
            }

            if ($current->payment_status === Order::PAYMENT_STATUS_PAID) {
                $order->setRawAttributes($current->getAttributes(), true);
                return false;
            }

            $order->fill([
                'is_paid' => false,
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
                'status' => $current->status === Order::STATUS_CANCELLED
                    ? Order::STATUS_CANCELLED
                    : Order::STATUS_PENDING_PAYMENT,
                'transaction_id' => $paymentDetails['tranId'] ?? $paymentDetails['transId'] ?? $current->transaction_id,
                'payment_reference' => $paymentDetails['paymentId'] ?? $paymentDetails['paymentid'] ?? $current->payment_reference,
            ])->save();

            PaymentLogger::info('Payment status updated to failed - order remains hidden from provider', [
                'correlation_id' => PaymentLogger::correlationId($order->id),
                'internal_order_id' => $order->id,
                'result' => $paymentDetails['result'] ?? null,
                'error_text' => $paymentDetails['errorText'] ?? $paymentDetails['error_text'] ?? null,
                'error_code' => $paymentDetails['error'] ?? null,
            ]);

            return true;
        });
    }

    /**
     * Persist the hosted payment page URL / gateway payment id returned at initiation.
     */
    public function recordPaymentInitiation(Order $order, array $gatewayResponse): void
    {
        $order->fill([
            'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'payment_link' => $gatewayResponse['url'] ?? null,
            'payment_reference' => $gatewayResponse['payment_id'] ?? $order->payment_reference,
        ])->save();
    }

    /**
     * Status the order takes once paid. Orders normally sit in pending_payment;
     * a legacy order already in a provider status keeps it.
     */
    protected function releasedStatusFor(Order $current): string
    {
        return in_array($current->status, Order::PROVIDER_STATUSES, true)
            ? $current->status
            : Order::STATUS_PENDING;
    }

    /**
     * Acquire a row lock on the order for the duration of the transaction.
     */
    protected function lockOrder(Order $order): ?Order
    {
        return Order::whereKey($order->id)->lockForUpdate()->first();
    }
}
