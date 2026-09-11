<?php

namespace App\Services\Engagement;

use App\Models\Customer;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class WishlistService
{
    public const DEFAULT_NAME = 'Default';

    public function ensureDefault(Customer $customer): Wishlist
    {
        return DB::transaction(function () use ($customer): Wishlist {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $existing = Wishlist::query()
                ->where('customer_id', $customer->id)
                ->where('name', self::DEFAULT_NAME)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return Wishlist::query()->create([
                'customer_id' => $customer->id,
                'name' => self::DEFAULT_NAME,
            ]);
        });
    }

    public function show(Customer $customer): Wishlist
    {
        return $this->load($this->ensureDefault($customer));
    }

    public function addItem(Customer $customer, int $productVariantId): Wishlist
    {
        $wishlist = $this->ensureDefault($customer);

        $exists = WishlistItem::query()
            ->where('wishlist_id', $wishlist->id)
            ->where('product_variant_id', $productVariantId)
            ->exists();
        if ($exists) {
            throw new CommerceException(ErrorCode::WISHLIST_ITEM_EXISTS, 'Variant is already on the wishlist.', 'product_variant_id', 409);
        }

        try {
            WishlistItem::query()->create([
                'wishlist_id' => $wishlist->id,
                'product_variant_id' => $productVariantId,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new CommerceException(ErrorCode::WISHLIST_ITEM_EXISTS, 'Variant is already on the wishlist.', 'product_variant_id', 409);
        }

        return $this->load($wishlist);
    }

    public function removeItem(Customer $customer, int $itemId): void
    {
        $wishlist = $this->ensureDefault($customer);
        $item = WishlistItem::query()
            ->where('wishlist_id', $wishlist->id)
            ->whereKey($itemId)
            ->first();

        if ($item === null) {
            throw new CommerceException(ErrorCode::WISHLIST_ITEM_NOT_FOUND, 'Wishlist item not found.', 'id', 404);
        }

        $item->delete();
    }

    private function load(Wishlist $wishlist): Wishlist
    {
        return $wishlist->load([
            'items' => fn ($q) => $q->orderBy('id'),
            'items.variant.product',
            'items.variant.images',
            'items.variant.product.images',
        ]);
    }
}
