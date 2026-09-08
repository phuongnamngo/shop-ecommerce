<?php

namespace App\Services\Shipment;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Services\Order\OrderService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;

final class ShipmentService
{
    public function __construct(private readonly OrderService $orders) {}

    public function shipFull(Order $order, AdminUser $admin, string $trackingNumber, ?string $carrierCode = null): OrderShipment
    {
        return DB::transaction(function () use ($order, $admin, $trackingNumber, $carrierCode): OrderShipment {
            $order = Order::query()->whereKey($order->id)->with('items')->lockForUpdate()->firstOrFail();
            if ($order->status !== 'fulfilling') {
                throw new CommerceException(ErrorCode::SHIPMENT_INVALID_STATUS, 'Order must be fulfilling to ship.', 'status', 409);
            }
            if ($order->shipments()->exists()) {
                throw new CommerceException(ErrorCode::SHIPMENT_ALREADY_EXISTS, 'Order already has a shipment.', status: 409);
            }
            if (trim($trackingNumber) === '') {
                throw new CommerceException(ErrorCode::SHIPMENT_TRACKING_REQUIRED, 'Tracking number is required.', 'tracking_number');
            }

            $shipment = $order->shipments()->create([
                'shipping_method_id' => $order->shipping_method_id,
                'tracking_number' => $trackingNumber,
                'carrier_code' => $carrierCode,
                'status' => 'shipped',
            ]);

            $reservations = $order->reservations()->where('status', 'active')->orderBy('product_variant_id')->lockForUpdate()->get()->keyBy('product_variant_id');
            foreach ($order->items as $item) {
                $shipment->items()->create([
                    'order_item_id' => $item->id,
                    'qty' => $item->qty,
                ]);
                $reservation = $reservations->get($item->product_variant_id);
                if ($reservation === null) {
                    throw new CommerceException(ErrorCode::SHIPMENT_INVALID_STATUS, 'Missing active reservation for order item.');
                }
                $stock = StockItem::query()
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $stock->update([
                    'qty_on_hand' => max(0, $stock->qty_on_hand - $item->qty),
                    'qty_reserved' => max(0, $stock->qty_reserved - $item->qty),
                ]);
                StockMovement::query()->create([
                    'warehouse_id' => $stock->warehouse_id,
                    'product_variant_id' => $item->product_variant_id,
                    'type' => 'issue',
                    'qty' => -$item->qty,
                    'reference_type' => OrderShipment::class,
                    'reference_id' => $shipment->id,
                    'note' => 'order shipment '.$trackingNumber,
                ]);
                $reservation->update(['status' => 'consumed']);
            }

            $this->orders->markShipped($order, $admin, 'shipment:'.$trackingNumber);

            return $shipment->load('items');
        });
    }
}
