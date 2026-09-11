<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Services\Promotion\FlashSalePricingService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CartService
{
    public function __construct(private readonly FlashSalePricingService $pricing) {}

    public function createGuest(): Cart
    {
        return Cart::query()->create([
            'session_id' => (string) Str::uuid(),
            'currency' => 'VND',
            'status' => 'active',
        ]);
    }

    public function guest(?string $token): Cart
    {
        if (! is_string($token) || ! Str::isUuid($token)) {
            throw new CommerceException(ErrorCode::CART_INVALID_TOKEN, 'A valid cart token is required.', 'X-Cart-Token');
        }

        return Cart::query()
            ->where('session_id', $token)
            ->where('status', 'active')
            ->firstOr(fn () => throw new CommerceException(ErrorCode::CART_INVALID_TOKEN, 'Cart token is invalid or expired.', 'X-Cart-Token'));
    }

    public function customer(Customer $customer): Cart
    {
        return Cart::query()->firstOrCreate(['customer_id' => $customer->id, 'status' => 'active'], ['currency' => 'VND']);
    }

    public function add(Cart $cart, int $variantId, int $qty): Cart
    {
        $variant = ProductVariant::query()
            ->where('status', ProductVariant::STATUS_ACTIVE)
            ->whereHas('product', fn ($query) => $query->where('status', 'active')->whereNotNull('published_at'))
            ->find($variantId);

        if ($variant === null) {
            throw new CommerceException(ErrorCode::CART_NOT_FOUND, 'The product variant is not available.', 'product_variant_id');
        }
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $item->qty = ($item->exists ? $item->qty : 0) + $qty;
        $item->unit_price = $this->pricing->unitPrice($variant);
        $item->save();

        return $this->present($cart->refresh());
    }

    public function update(Cart $cart, int $itemId, int $qty): Cart
    {
        $item = $cart->items()->find($itemId);
        if ($item === null) {
            throw new CommerceException(ErrorCode::CART_NOT_FOUND, 'Cart item was not found.');
        }

        $variant = ProductVariant::query()
            ->where('status', ProductVariant::STATUS_ACTIVE)
            ->whereHas('product', fn ($query) => $query->where('status', 'active')->whereNotNull('published_at'))
            ->find($item->product_variant_id);
        if ($variant === null) {
            throw new CommerceException(ErrorCode::CART_NOT_FOUND, 'The product variant is not available.', 'product_variant_id');
        }

        $item->update(['qty' => $qty, 'unit_price' => $this->pricing->unitPrice($variant)]);

        return $this->present($cart->refresh());
    }

    public function merge(Customer $customer, string $token): Cart
    {
        return DB::transaction(function () use ($customer, $token) {
            $cart = Cart::query()->firstOrCreate(['customer_id' => $customer->id, 'status' => 'active'], ['currency' => 'VND']);
            $guestId = Cart::query()->where('session_id', $token)->where('status', 'active')->value('id');

            if ($guestId === null) {
                throw new CommerceException(ErrorCode::CART_INVALID_TOKEN, 'Cart token is invalid or expired.', 'guest_token');
            }

            $locked = Cart::query()->whereIn('id', [$guestId, $cart->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $guest = $locked->get($guestId);
            $cart = $locked->get($cart->id);

            foreach ($guest->items()->lockForUpdate()->get() as $item) {
                $this->add($cart, $item->product_variant_id, $item->qty);
            }
            $guest->update(['status' => 'merged']);

            return $this->present($cart->refresh());
        });
    }

    public function present(Cart $cart): Cart
    {
        $cart->load([
            'items.variant.product.images',
            'items.variant.images',
            'items.variant.attributeOptions.attribute',
        ]);

        $offers = $this->pricing->offersForVariants($cart->items->pluck('product_variant_id')->all());
        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if ($variant === null) {
                continue;
            }
            $price = $offers->get($variant->id)?->sale_price ?? $variant->price;
            if ((string) $item->unit_price !== (string) $price) {
                $item->forceFill(['unit_price' => $price])->save();
            }
        }

        return $cart;
    }
}
