<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\WebhookEvent;
use App\Services\Order\OrderService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class PaymentService
{
    public function __construct(private readonly OrderService $orders) {}

    public function settleSuccess(PaymentTransaction $txn, ?string $providerTxnId, string $note): void
    {
        DB::transaction(function () use ($txn, $providerTxnId, $note): void {
            $txn = PaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($txn->order_id)->lockForUpdate()->firstOrFail();

            if ($txn->status === 'succeeded') {
                return;
            }
            if ($order->status === 'cancelled') {
                return;
            }
            if ($txn->status !== 'pending') {
                throw new CommerceException(ErrorCode::PAYMENT_ALREADY_SETTLED, 'Payment is not pending.');
            }

            $txn->update([
                'status' => 'succeeded',
                'provider_txn_id' => $providerTxnId,
            ]);
            $this->orders->markPaidBySystem($order, $note);
        });
    }

    public function markFailed(PaymentTransaction $txn, string $note): void
    {
        DB::transaction(function () use ($txn, $note): void {
            $txn = PaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();
            if ($txn->status !== 'pending') {
                return;
            }
            $txn->update([
                'status' => 'failed',
                'payload' => array_merge($txn->payload ?? [], ['note' => $note]),
            ]);
        });
    }

    public function markExpired(PaymentTransaction $txn): void
    {
        DB::transaction(function () use ($txn): void {
            $txn = PaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();
            if ($txn->status === 'pending') {
                $txn->update(['status' => 'expired']);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordWebhook(string $provider, string $eventType, array $payload, string $txnRef, string $responseCode): bool
    {
        $key = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $provider.':'.$txnRef.':'.$responseCode);
        try {
            WebhookEvent::query()->create([
                'provider' => $provider,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => 'received',
                'idempotency_key' => $key,
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function findTxnByOrderNumber(string $orderNumber): ?PaymentTransaction
    {
        $order = Order::query()->where('number', $orderNumber)->first();
        if ($order === null) {
            return null;
        }

        return PaymentTransaction::query()->where('order_id', $order->id)->latest('id')->first();
    }
}
