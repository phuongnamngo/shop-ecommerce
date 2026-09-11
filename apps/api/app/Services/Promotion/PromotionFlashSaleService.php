<?php

namespace App\Services\Promotion;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PromotionFlashSaleService
{
    /**
     * @param  array{
     *     name: string,
     *     starts_at: mixed,
     *     ends_at: mixed,
     *     code?: string|null,
     *     status?: string|null,
     *     items?: list<array{product_variant_id: int, sale_price: float|int|string, qty_cap?: int|null}>
     * }  $data
     */
    public function create(array $data): FlashSale
    {
        return DB::transaction(function () use ($data): FlashSale {
            $sale = FlashSale::query()->create([
                'code' => $data['code'] ?? (string) Str::ulid(),
                'name' => $data['name'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => $data['status'] ?? FlashSale::STATUS_SCHEDULED,
            ]);

            $this->replaceItems($sale, $data['items'] ?? []);

            return $sale->load('items');
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     starts_at?: mixed,
     *     ends_at?: mixed,
     *     code?: string|null,
     *     status?: string|null,
     *     items?: list<array{product_variant_id: int, sale_price: float|int|string, qty_cap?: int|null}>
     * }  $data
     */
    public function update(FlashSale $sale, array $data): FlashSale
    {
        return DB::transaction(function () use ($sale, $data): FlashSale {
            $sale->fill([
                'name' => $data['name'] ?? $sale->name,
                'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $sale->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $sale->ends_at,
                'status' => $data['status'] ?? $sale->status,
            ]);

            if (array_key_exists('code', $data) && $data['code'] !== null && $data['code'] !== '') {
                $sale->code = $data['code'];
            }

            $sale->save();

            if (array_key_exists('items', $data)) {
                $this->replaceItems($sale, $data['items'] ?? []);
            } else {
                $this->assertNoOverlapForSale($sale);
            }

            return $sale->refresh()->load('items');
        });
    }

    public function delete(FlashSale $sale): void
    {
        $sale->delete();
    }

    /**
     * @param  list<array{product_variant_id: int, sale_price: float|int|string, qty_cap?: int|null}>  $items
     */
    private function replaceItems(FlashSale $sale, array $items): void
    {
        $variantIds = array_map(fn (array $row): int => (int) $row['product_variant_id'], $items);
        if (count($variantIds) !== count(array_unique($variantIds))) {
            throw new CommerceException(ErrorCode::FLASH_SALE_INVALID, 'Duplicate product variants are not allowed.', 'items');
        }

        $variants = ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id');
        foreach ($items as $index => $row) {
            $variant = $variants->get((int) $row['product_variant_id']);
            if ($variant === null) {
                throw new CommerceException(
                    ErrorCode::FLASH_SALE_INVALID,
                    'The product variant was not found.',
                    'items.'.$index.'.product_variant_id',
                );
            }
            $salePrice = (float) $row['sale_price'];
            if ($salePrice <= 0 || $salePrice >= (float) $variant->price) {
                throw new CommerceException(
                    ErrorCode::FLASH_SALE_INVALID,
                    'Sale price must be greater than 0 and less than the list price.',
                    'items.'.$index.'.sale_price',
                );
            }
        }

        $existing = $sale->items()->get()->keyBy('product_variant_id');
        $keep = [];
        foreach ($items as $index => $row) {
            $variantId = (int) $row['product_variant_id'];
            $keep[] = $variantId;
            $qtyCap = array_key_exists('qty_cap', $row) ? $row['qty_cap'] : null;
            $current = $existing->get($variantId);
            if ($current !== null && $qtyCap !== null && (int) $qtyCap < (int) $current->qty_sold) {
                throw new CommerceException(
                    ErrorCode::FLASH_SALE_INVALID,
                    'qty_cap cannot be below qty_sold.',
                    'items.'.$index.'.qty_cap',
                );
            }
            if ($current !== null) {
                $current->update([
                    'sale_price' => $row['sale_price'],
                    'qty_cap' => $qtyCap,
                ]);
            } else {
                $sale->items()->create([
                    'product_variant_id' => $variantId,
                    'sale_price' => $row['sale_price'],
                    'qty_cap' => $qtyCap,
                    'qty_sold' => 0,
                ]);
            }
        }

        if ($keep === []) {
            $sale->items()->delete();
        } else {
            $sale->items()->whereNotIn('product_variant_id', $keep)->delete();
        }

        $this->assertNoOverlapForSale($sale->refresh()->load('items'));
    }

    private function assertNoOverlapForSale(FlashSale $sale): void
    {
        if (! in_array($sale->status, [FlashSale::STATUS_SCHEDULED, FlashSale::STATUS_ACTIVE], true)) {
            return;
        }

        foreach ($sale->items as $item) {
            $this->assertNoOverlap($sale, (int) $item->product_variant_id);
        }
    }

    private function assertNoOverlap(FlashSale $sale, int $variantId): void
    {
        $exists = FlashSaleItem::query()
            ->where('product_variant_id', $variantId)
            ->whereHas('flashSale', function ($query) use ($sale): void {
                $query->whereIn('status', [FlashSale::STATUS_SCHEDULED, FlashSale::STATUS_ACTIVE])
                    ->whereKeyNot($sale->id)
                    ->where('starts_at', '<=', $sale->ends_at)
                    ->where('ends_at', '>=', $sale->starts_at);
            })
            ->exists();

        if ($exists) {
            throw new CommerceException(
                ErrorCode::FLASH_SALE_OVERLAP,
                'This variant already has a scheduled or active flash sale in the same window.',
                'items',
                422,
            );
        }
    }
}
