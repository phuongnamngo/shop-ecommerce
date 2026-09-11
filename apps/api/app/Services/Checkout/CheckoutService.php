<?php

namespace App\Services\Checkout;

use App\Contracts\PaymentGateway;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Services\Promotion\FlashSalePricingService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CheckoutService
{
    public function __construct(
        private readonly PaymentGateway $payments,
        private readonly FlashSalePricingService $pricing,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{order: Order, payment: array{provider: string, status: string, redirect_url?: string}, lookup_token?: string}
     */
    public function checkout(Cart $cart, ?Customer $customer, array $payload): array
    {
        return DB::transaction(function () use ($cart, $customer, $payload): array {
            $cart = Cart::query()->whereKey($cart->id)->where('status', 'active')->lockForUpdate()->first();
            if ($cart === null) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Cart is no longer active.');
            }

            $methodCode = (string) $payload['payment_method_code'];
            $paymentMethod = PaymentMethod::query()->where('code', $methodCode)->where('is_active', true)->first();
            if ($paymentMethod === null || ! in_array($methodCode, ['cod', 'vnpay'], true)) {
                throw new CommerceException(ErrorCode::PAYMENT_METHOD_INVALID, 'Payment method is unavailable.', 'payment_method_code');
            }

            $lines = $cart->items()->with('variant.product')->orderBy('product_variant_id')->get();
            if ($lines->isEmpty()) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Cart is empty.');
            }

            foreach ($lines as $line) {
                $variant = $line->variant;
                if ($variant === null || $variant->status !== 'active' || $variant->product === null || $variant->product->status !== 'active' || $variant->product->published_at === null) {
                    throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'A cart item is no longer purchasable.');
                }
            }

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

            $offers = $this->pricing->offersForVariants($variantIds);
            $lockedOffers = collect();
            $offerIds = $offers->pluck('id')->all();
            if ($offerIds !== []) {
                $lockedOffers = FlashSaleItem::query()
                    ->whereIn('id', $offerIds)
                    ->orderBy('product_variant_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_variant_id');
            }

            $linePricing = [];
            $subtotal = 0;
            foreach ($lines as $line) {
                $variant = $line->variant;
                $offer = $lockedOffers->get($variant->id);
                if ($offer !== null && $offer->qty_cap !== null && ((int) $offer->qty_sold + $line->qty) > (int) $offer->qty_cap) {
                    throw new CommerceException(ErrorCode::FLASH_SALE_QTY_EXCEEDED, 'Flash sale quantity exceeded.', 'product_variant_id', 409);
                }
                $unit = $offer?->sale_price ?? $variant->price;
                $linePricing[$variant->id] = ['unit' => $unit, 'offer' => $offer];
                $subtotal += (int) $unit * $line->qty;
            }

            $rate = ShippingRate::query()->whereKey($payload['shipping_rate_id'])
                ->where('shipping_method_id', $payload['shipping_method_id'])->whereNull('region_code')
                ->where(fn ($q) => $q->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subtotal))
                ->where(fn ($q) => $q->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subtotal))->first();
            if ($rate === null) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Shipping rate does not match the order subtotal.', 'shipping_rate_id');
            }

            [$coupon, $discount] = $this->coupon($payload['coupon_code'] ?? null, $customer, $subtotal);

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
            $lookupToken = $customer === null ? $this->issueGuestLookupToken($order) : null;

            $expiresAt = now()->addMinutes((int) config('commerce.reservation_ttl_minutes', 30));
            foreach ($lines as $line) {
                $variant = $line->variant;
                $priced = $linePricing[$variant->id];
                $unit = $priced['unit'];
                $offer = $priced['offer'];
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'flash_sale_item_id' => $offer?->id,
                    'sku' => $variant->sku,
                    'name' => $variant->product->name,
                    'qty' => $line->qty,
                    'unit_price' => $unit,
                    'line_total' => (int) $unit * $line->qty,
                ]);
                if ($offer !== null) {
                    $offer->increment('qty_sold', $line->qty);
                }
                $stock = $stocks->get($variant->id);
                $stock->increment('qty_reserved', $line->qty);
                $order->reservations()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $variant->id,
                    'cart_id' => $cart->id,
                    'qty' => $line->qty,
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                ]);
            }
            $order->statusHistories()->create(['from_status' => null, 'to_status' => 'pending', 'changed_by_customer_id' => $customer?->id]);
            if ($coupon !== null) {
                $coupon->increment('used_count');
                $coupon->redemptions()->create(['customer_id' => $customer?->id, 'order_id' => $order->id, 'redeemed_at' => now()]);
            }

            $txn = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'provider' => $methodCode,
                'idempotency_key' => (string) Str::uuid(),
                'amount' => $order->grand_total,
                'status' => 'pending',
            ]);
            $initiation = $this->payments->initiate($order, $txn);
            if (($initiation['redirect_url'] ?? null) !== null) {
                $txn->update(['payload' => ['redirect_url' => $initiation['redirect_url']]]);
            }

            $cart->update(['status' => 'converted']);

            $payment = [
                'provider' => $methodCode,
                'status' => 'pending',
            ];
            if ($methodCode === 'vnpay') {
                $payment['redirect_url'] = $initiation['redirect_url'];
            }

            $result = [
                'order' => $order->load('items', 'statusHistories'),
                'payment' => $payment,
            ];
            if ($lookupToken !== null) {
                $result['lookup_token'] = $lookupToken;
            }

            return $result;
        }, 3);
    }

    private function issueGuestLookupToken(Order $order): string
    {
        $plain = bin2hex(random_bytes(32));
        $order->update([
            'guest_lookup_token_hash' => hash('sha256', $plain),
            'guest_lookup_token_cipher' => Crypt::encryptString($plain),
            'guest_lookup_token_expires_at' => now()->addDays(30),
        ]);

        return $plain;
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
