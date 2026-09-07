<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\CheckoutRequest;
use App\Models\CustomerAddress;
use App\Models\GeoWard;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Support\ApiResponse;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;

final class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $carts, private readonly CheckoutService $checkout) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        $customer = $request->user('customer');
        if ($customer === null) {
            $cart = $this->carts->guest($request->header('X-Cart-Token'));
        } else {
            $cart = $this->carts->customer($customer);
        }

        if (isset($data['customer_address_id'])) {
            if ($customer === null || ! CustomerAddress::query()->whereKey($data['customer_address_id'])->where('customer_id', $customer->id)->exists()) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'The address does not belong to this customer.', 'customer_address_id');
            }
        } else {
            $address = $data['shipping_address'];
            $validGeo = GeoWard::query()
                ->where('code', $address['ward_code'])
                ->whereHas('district', fn ($district) => $district->where('code', $address['district_code'])
                    ->whereHas('province', fn ($province) => $province->where('code', $address['province_code'])))
                ->exists();
            if (! $validGeo) {
                throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Shipping address geo codes are invalid.', 'shipping_address');
            }
        }

        $method = ShippingMethod::query()->whereKey($data['shipping_method_id'])->where('status', 'active')->first();
        $rate = ShippingRate::query()->whereKey($data['shipping_rate_id'])->where('shipping_method_id', $data['shipping_method_id'])->whereNull('region_code')->first();
        if ($method === null || $rate === null) {
            throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Shipping method or rate is unavailable.', 'shipping_rate_id');
        }

        $order = $this->checkout->checkout($cart, $customer, $data);

        return ApiResponse::success([
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'subtotal' => $order->subtotal,
            'discount_total' => $order->discount_total,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'items' => $order->items,
            'next_action' => 'payment_pending',
        ], status: 201);
    }
}
