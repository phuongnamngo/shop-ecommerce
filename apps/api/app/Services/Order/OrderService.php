<?php

namespace App\Services\Order;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\StockItem;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    private const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['fulfilling', 'cancelled'],
        'fulfilling' => ['cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function transition(Order $order, string $toStatus, AdminUser $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $admin, $note): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $order->status;
            if (! in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw new CommerceException(ErrorCode::ORDER_INVALID_TRANSITION, "Cannot transition order from {$fromStatus} to {$toStatus}.", 'status', 409);
            }

            if ($toStatus === 'cancelled') {
                $this->releaseActiveReservations($order);
            }

            if ($toStatus === 'paid') {
                $this->settlePendingTransaction($order);
            }

            $order->update(['status' => $toStatus]);
            $order->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_admin_id' => $admin->id,
                'note' => $note,
            ]);

            return $order->refresh()->load('items', 'statusHistories');
        });
    }

    public function markPaidBySystem(Order $order, string $note): Order
    {
        return DB::transaction(function () use ($order, $note): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'paid') {
                return $order->refresh()->load('items', 'statusHistories');
            }
            if ($order->status !== 'pending') {
                throw new CommerceException(ErrorCode::ORDER_INVALID_TRANSITION, "Cannot mark paid from {$order->status}.", 'status', 409);
            }

            $this->settlePendingTransaction($order);
            $fromStatus = $order->status;
            $order->update(['status' => 'paid']);
            $order->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => 'paid',
                'changed_by_admin_id' => null,
                'note' => $note,
            ]);

            return $order->refresh()->load('items', 'statusHistories');
        });
    }

    public function markShipped(Order $order, AdminUser $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'fulfilling') {
                throw new CommerceException(ErrorCode::ORDER_INVALID_TRANSITION, "Cannot ship order from {$order->status}.", 'status', 409);
            }

            $fromStatus = $order->status;
            $order->update(['status' => 'shipped']);
            $order->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => 'shipped',
                'changed_by_admin_id' => $admin->id,
                'note' => $note,
            ]);

            return $order->refresh()->load('items', 'statusHistories');
        });
    }

    public function cancelExpiredPending(): int
    {
        $orderIds = Order::query()
            ->where('status', 'pending')
            ->whereHas('reservations', fn ($q) => $q->where('status', 'active')->where('expires_at', '<=', now()))
            ->pluck('id');

        $count = 0;
        foreach ($orderIds as $orderId) {
            DB::transaction(function () use ($orderId, &$count): void {
                $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();
                if ($order === null || $order->status !== 'pending') {
                    return;
                }

                $stillExpired = $order->reservations()
                    ->where('status', 'active')
                    ->where('expires_at', '<=', now())
                    ->exists();
                if (! $stillExpired) {
                    return;
                }

                $this->releaseActiveReservations($order);
                PaymentTransaction::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                $fromStatus = $order->status;
                $order->update(['status' => 'cancelled']);
                $order->statusHistories()->create([
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                    'changed_by_admin_id' => null,
                    'note' => 'system:ttl_expired',
                ]);
                $count++;
            });
        }

        return $count;
    }

    private function releaseActiveReservations(Order $order): void
    {
        $reservations = $order->reservations()->where('status', 'active')->orderBy('product_variant_id')->lockForUpdate()->get();
        foreach ($reservations as $reservation) {
            $stock = StockItem::query()
                ->where('warehouse_id', $reservation->warehouse_id)
                ->where('product_variant_id', $reservation->product_variant_id)
                ->lockForUpdate()
                ->firstOrFail();
            $stock->update(['qty_reserved' => max(0, $stock->qty_reserved - $reservation->qty)]);
            $reservation->update(['status' => 'released']);
        }
    }

    private function settlePendingTransaction(Order $order): void
    {
        $txn = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();
        if ($txn !== null) {
            $txn->update(['status' => 'succeeded']);
        }
    }
}
