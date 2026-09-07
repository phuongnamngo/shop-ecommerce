<?php

namespace App\Services\Order;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\StockItem;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    private const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['fulfilling', 'cancelled'],
        'fulfilling' => ['shipped', 'cancelled'],
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
                $reservations = $order->reservations()->where('status', 'active')->orderBy('product_variant_id')->lockForUpdate()->get();
                foreach ($reservations as $reservation) {
                    $stock = StockItem::query()->where('warehouse_id', $reservation->warehouse_id)->where('product_variant_id', $reservation->product_variant_id)->lockForUpdate()->firstOrFail();
                    $stock->update(['qty_reserved' => max(0, $stock->qty_reserved - $reservation->qty)]);
                    $reservation->update(['status' => 'released']);
                }
            }

            $order->update(['status' => $toStatus]);
            $order->statusHistories()->create(['from_status' => $fromStatus, 'to_status' => $toStatus, 'changed_by_admin_id' => $admin->id, 'note' => $note]);

            return $order->refresh()->load('items', 'statusHistories');
        });
    }
}
