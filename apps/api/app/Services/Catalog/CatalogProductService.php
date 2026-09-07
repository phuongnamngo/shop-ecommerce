<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CatalogException;
use App\Support\CatalogSlug;
use App\Support\ErrorCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogProductService
{
    public function __construct(private readonly CatalogVariantService $variants) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $adminId): Product
    {
        $variantRows = $data['variants'] ?? [];
        $defaultCount = collect($variantRows)->where('is_default', true)->count();
        if ($variantRows === [] || $defaultCount !== 1) {
            throw new CatalogException(
                ErrorCode::CATALOG_VARIANT_INVARIANT,
                'A product must have at least one variant and exactly one default.',
                status: 422,
            );
        }

        $slug = CatalogSlug::resolve($data['name'], $data['slug'] ?? null);
        CatalogSlug::assertUnique('products', $slug);

        foreach ($variantRows as $row) {
            $this->variants->assertSkuUnique($row['sku']);
        }

        $skus = array_column($variantRows, 'sku');
        if (count($skus) !== count(array_unique($skus))) {
            throw new CatalogException(
                ErrorCode::CATALOG_SKU_TAKEN,
                'SKU has already been taken.',
                'sku',
                422,
            );
        }

        return DB::transaction(function () use ($data, $adminId, $slug, $variantRows): Product {
            $status = $data['status'] ?? Product::STATUS_DRAFT;
            $publishedAt = $data['published_at'] ?? null;
            if ($status === Product::STATUS_ACTIVE && $publishedAt === null) {
                $publishedAt = now();
            }

            $product = Product::query()->create([
                'code' => (string) Str::ulid(),
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'status' => $status,
                'published_at' => $publishedAt,
                'description' => $data['description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($data['category_ids']);
            }

            foreach ($variantRows as $row) {
                $this->variants->create($product, $row);
            }

            return $product->refresh()->load($this->adminRelations());
        });
    }

    public function applyPublicVisibility(Builder $query): Builder
    {
        return $query
            ->where('products.status', Product::STATUS_ACTIVE)
            ->whereNotNull('products.published_at')
            ->where('products.published_at', '<=', now())
            ->whereHas('defaultVariant', function ($variants): void {
                $variants->where('status', ProductVariant::STATUS_ACTIVE);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, int $adminId): Product
    {
        $name = $data['name'] ?? $product->name;
        $slug = $product->slug;
        if (array_key_exists('slug', $data)) {
            $slug = CatalogSlug::forUpdate('products', $product->slug, $name, $data['slug'], $product->id);
        }

        $status = $data['status'] ?? $product->status;
        $publishedAt = array_key_exists('published_at', $data) ? $data['published_at'] : $product->published_at;
        if ($status === Product::STATUS_ACTIVE && $publishedAt === null) {
            $publishedAt = now();
        }

        $product->fill([
            'brand_id' => array_key_exists('brand_id', $data) ? $data['brand_id'] : $product->brand_id,
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
            'published_at' => $publishedAt,
            'description' => array_key_exists('description', $data) ? $data['description'] : $product->description,
            'meta_title' => array_key_exists('meta_title', $data) ? $data['meta_title'] : $product->meta_title,
            'meta_description' => array_key_exists('meta_description', $data) ? $data['meta_description'] : $product->meta_description,
            'updated_by' => $adminId,
        ])->save();

        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync($data['category_ids']);
        }

        return $product->refresh()->load($this->adminRelations());
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load('variants.images');
            $product->images()->delete();
            foreach ($product->variants as $variant) {
                $variant->images()->delete();
                $variant->delete();
            }
            $product->delete();
        });
    }

    /**
     * @return list<string>
     */
    public function adminRelations(): array
    {
        return ['brand', 'categories', 'variants.attributeOptions.attribute', 'variants.images', 'images'];
    }
}
