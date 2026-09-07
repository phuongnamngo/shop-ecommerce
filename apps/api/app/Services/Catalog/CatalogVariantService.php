<?php

namespace App\Services\Catalog;

use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CatalogException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogVariantService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Product $product, array $data): ProductVariant
    {
        $this->assertSkuUnique($data['sku']);

        return DB::transaction(function () use ($product, $data): ProductVariant {
            if ($data['is_default'] ?? false) {
                $this->unsetDefault($product->id);
            }

            $variant = ProductVariant::query()->create([
                'code' => (string) Str::ulid(),
                'product_id' => $product->id,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'is_default' => $data['is_default'] ?? false,
                'status' => $data['status'] ?? ProductVariant::STATUS_ACTIVE,
            ]);

            $this->syncOptions($variant, $data['attribute_option_ids'] ?? []);

            return $variant->refresh()->load(['attributeOptions.attribute', 'images']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        if (array_key_exists('sku', $data) && $data['sku'] !== $variant->sku) {
            $this->assertSkuUnique($data['sku'], $variant->id);
        }

        return DB::transaction(function () use ($variant, $data): ProductVariant {
            $isDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $variant->is_default;

            if ($variant->is_default && $isDefault === false) {
                throw new CatalogException(
                    ErrorCode::CATALOG_VARIANT_INVARIANT,
                    'Cannot unset the only default variant.',
                    status: 422,
                );
            }

            if ($isDefault && ! $variant->is_default) {
                $this->unsetDefault($variant->product_id, $variant->id);
            }

            $variant->fill([
                'sku' => $data['sku'] ?? $variant->sku,
                'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $variant->barcode,
                'price' => $data['price'] ?? $variant->price,
                'compare_at_price' => array_key_exists('compare_at_price', $data) ? $data['compare_at_price'] : $variant->compare_at_price,
                'is_default' => $isDefault,
                'status' => $data['status'] ?? $variant->status,
            ])->save();

            if (array_key_exists('attribute_option_ids', $data)) {
                $this->syncOptions($variant, $data['attribute_option_ids']);
            }

            return $variant->refresh()->load(['attributeOptions.attribute', 'images']);
        });
    }

    public function delete(ProductVariant $variant): void
    {
        $remaining = ProductVariant::query()->where('product_id', $variant->product_id)->count();
        if ($remaining <= 1) {
            throw new CatalogException(
                ErrorCode::CATALOG_VARIANT_INVARIANT,
                'Cannot delete the last variant.',
                status: 422,
            );
        }

        $variant->delete();
    }

    public function assertSkuUnique(string $sku, ?int $ignoreId = null): void
    {
        $query = DB::table('product_variants')->where('sku', $sku)->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new CatalogException(
                ErrorCode::CATALOG_SKU_TAKEN,
                'SKU has already been taken.',
                'sku',
                422,
            );
        }
    }

    private function unsetDefault(int $productId, ?int $exceptId = null): void
    {
        $query = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('is_default', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function syncOptions(ProductVariant $variant, array $optionIds): void
    {
        if ($optionIds === []) {
            $variant->attributeOptions()->sync([]);

            return;
        }

        $options = AttributeOption::query()->whereIn('id', $optionIds)->get()->keyBy('id');
        $sync = [];
        foreach ($optionIds as $optionId) {
            $option = $options->get($optionId);
            if ($option === null) {
                continue;
            }
            $sync[$option->id] = ['attribute_id' => $option->attribute_id];
        }

        $variant->attributeOptions()->sync($sync);
    }
}
