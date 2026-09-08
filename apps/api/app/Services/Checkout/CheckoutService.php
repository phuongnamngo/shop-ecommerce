<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CheckoutService
{
    public function checkout(Cart $cart, ?Customer $customer, array $payload): Order
    {
        return DB::transaction(function () use ($cart, $customer, $payload): Order {
            $cart = Cart::query()->whereKey($cart->id)->where('status', 'active')->lockForUpdate()->first();
            if ($cart === null) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Cart is no longer active.');
            }

            $lines = $cart->items()->with('variant.product')->orderBy('product_variant_id')->get();
            if ($lines->isEmpty()) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Cart is empty.');
            }

            $subtotal = 0;
            foreach ($lines as $line) {
                $variant = $line->variant;
                if ($variant === null || $variant->status !== 'active' || $variant->product === null || $variant->product->status !== 'active' || $variant->product->published_at === null) {
                    throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'A cart item is no longer purchasable.');
                }
                $subtotal += (int) $variant->price * $line->qty;
            }

            $rate = ShippingRate::query()->whereKey($payload['shipping_rate_id'])
                ->where('shipping_method_id', $payload['shipping_method_id'])->whereNull('region_code')
                ->where(fn ($q) => $q->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subtotal))
                ->where(fn ($q) => $q->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subtotal))->first();
            if ($rate === null) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Shipping rate does not match the order subtotal.', 'shipping_rate_id');
            }

            [$coupon, $discount] = $this->coupon($payload['coupon_code'] ?? null, $customer, $subtotal);
            $warehouse = Warehouse::query()->where('is_default', true)->where('status', 'active')->first();
            if ($warehouse === null) {
                throw new CommerceException(ErrorCode::INVENTORY_NOT_FOUND, 'Default warehouse is unavailable.');
            }

            $variantIds = $lines->pluck('product_variant_id')->all();
            $stocks = StockItem::query()->where('warehouse_id', $warehouse->id)->whereIn('product_variant_id', $variantIds)
                ->orderBy('product_variant_id')->lockForUpdate()->get()->keyBy('product_variant_id');
            foreach ($lines as $line) {
                $stock = $stocks->get($line->product_variant_id);
                if ($stock === null || $stock->qty_on_hand - $stock->qty_reserved < $line->qty) {
                    throw new CommerceException(ErrorCode::INVENTORY_INSUFFICIENT_STOCK, 'Insufficient stock.', 'product_variant_id', 409);
                }
            }

            $address = isset($payload['customer_address_id'])
                ? CustomerAddress::query()->whereKey($payload['customer_address_id'])->where('customer_id', $customer?->id)->firstOrFail()->only(['recipient_name', 'phone', 'province_code', 'district_code', 'ward_code', 'address_line', 'postal_code'])
                : $payload['shipping_address'];
            $shipping = (int) $rate->price;
            $order = Order::query()->create([
                'number' => 'ORD-'.Str::upper((string) Str::ulid()), 'customer_id' => $customer?->id, 'status' => 'pending', 'currency' => 'VND',
                'subtotal' => $subtotal, 'discount_total' => $discount, 'shipping_total' => $shipping, 'tax_total' => 0,
                'grand_total' => $subtotal - $discount + $shipping, 'shipping_address_snapshot' => $address,
                'billing_address_snapshot' => $address, 'shipping_method_id' => $payload['shipping_method_id'], 'coupon_id' => $coupon?->id,
            ]);

            foreach ($lines as $line) {
                $variant = $line->variant;
                $order->items()->create(['product_variant_id' => $variant->id, 'sku' => $variant->sku, 'name' => $variant->product->name, 'qty' => $line->qty, 'unit_price' => $variant->price, 'line_total' => (int) $variant->price * $line->qty]);
                $stock = $stocks->get($variant->id);
                $stock->increment('qty_reserved', $line->qty);
                $order->reservations()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'cart_id' => $cart->id, 'qty' => $line->qty, 'status' => 'active']);
            }
            $order->statusHistories()->create(['from_status' => null, 'to_status' => 'pending', 'changed_by_customer_id' => $customer?->id]);
            if ($coupon !== null) {
                $coupon->increment('used_count');
                $coupon->redemptions()->create(['customer_id' => $customer?->id, 'order_id' => $order->id, 'redeemed_at' => now()]);
            }
            $cart->update(['status' => 'converted']);

            return $order->load('items', 'statusHistories');
        }, 3);
    }

    private function coupon(?string $code, ?Customer $customer, int $subtotal): array
    {
        if ($code === null || $code === '') {
            return [null, 0];
        }
        $coupon = Coupon::query()->where('code', $code)->where('status', 'active')->lockForUpdate()->first();
        if ($coupon === null || ($coupon->starts_at && $coupon->starts_at->isFuture()) || ($coupon->ends_at && $coupon->ends_at->isPast()) || ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses)) {
            throw new CommerceException(ErrorCode::COUPON_INVALID, 'Coupon is unavailable.', 'coupon_code');
        }
        if ($coupon->max_uses_per_customer !== null) {
            if ($customer === null || $coupon->redemptions()->where('customer_id', $customer->id)->count() >= $coupon->max_uses_per_customer) {
                throw new CommerceException(ErrorCode::COUPON_INVALID, 'Coupon customer limit reached.', 'coupon_code');
            }
        }
        $discount = $coupon->discount()->where('status', 'active')->first();
        if ($discount === null || ($discount->starts_at && $discount->starts_at->isFuture()) || ($discount->ends_at && $discount->ends_at->isPast())) {
            throw new CommerceException(ErrorCode::COUPON_INVALID, 'Discount is unavailable.', 'coupon_code');
        }
        $rules = $discount->rules()->get();
        if ($rules->count() > 1) {
            throw new CommerceException(ErrorCode::COUPON_INVALID, 'Coupon rules are unsupported.', 'coupon_code');
        }
        $conditions = $rules->first()?->conditions ?? [];
        if (array_diff(array_keys($conditions), ['min_subtotal']) !== [] || (($conditions['min_subtotal'] ?? 0) > $subtotal)) {
            throw new CommerceException(ErrorCode::COUPON_INVALID, 'Coupon conditions are not met.', 'coupon_code');
        }
        $value = (float) $discount->value;
        $amount = $discount->type === 'fixed' ? min((int) $value, $subtotal) : ($discount->type === 'percentage' && $value > 0 && $value <= 100 ? (int) floor($subtotal * $value / 100) : null);
        if ($amount === null || $amount <= 0) {
            throw new CommerceException(ErrorCode::COUPON_INVALID, 'Coupon discount is invalid.', 'coupon_code');
        }

        return [$coupon, $amount];
    }
}
