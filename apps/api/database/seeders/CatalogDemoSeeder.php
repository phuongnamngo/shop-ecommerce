<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'demo-brand'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Demo Brand',
                'status' => Brand::STATUS_ACTIVE,
            ],
        );

        $parent = Category::query()->firstOrCreate(
            ['slug' => 'apparel'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Apparel',
                'position' => 1,
                'status' => Category::STATUS_ACTIVE,
            ],
        );

        $child = Category::query()->firstOrCreate(
            ['slug' => 't-shirts'],
            [
                'code' => (string) Str::ulid(),
                'parent_id' => $parent->id,
                'name' => 'T-Shirts',
                'position' => 1,
                'status' => Category::STATUS_ACTIVE,
            ],
        );

        $size = Attribute::query()->firstOrCreate(
            ['slug' => 'size'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Size',
                'position' => 1,
            ],
        );

        $optS = AttributeOption::query()->firstOrCreate(
            ['attribute_id' => $size->id, 'label' => 'S'],
            [
                'code' => (string) Str::ulid(),
                'position' => 1,
            ],
        );

        $optM = AttributeOption::query()->firstOrCreate(
            ['attribute_id' => $size->id, 'label' => 'M'],
            [
                'code' => (string) Str::ulid(),
                'position' => 2,
            ],
        );

        $product = Product::query()->firstOrCreate(
            ['slug' => 'demo-tee'],
            [
                'code' => (string) Str::ulid(),
                'brand_id' => $brand->id,
                'name' => 'Demo Tee',
                'status' => Product::STATUS_ACTIVE,
                'published_at' => now(),
            ],
        );

        $product->categories()->syncWithoutDetaching([$child->id]);

        $variantS = ProductVariant::query()->updateOrCreate(
            ['sku' => 'DEMO-TEE-S'],
            [
                'code' => (string) Str::ulid(),
                'product_id' => $product->id,
                'price' => 199000,
                'is_default' => true,
                'status' => ProductVariant::STATUS_ACTIVE,
            ],
        );

        $variantM = ProductVariant::query()->updateOrCreate(
            ['sku' => 'DEMO-TEE-M'],
            [
                'code' => (string) Str::ulid(),
                'product_id' => $product->id,
                'price' => 199000,
                'is_default' => false,
                'status' => ProductVariant::STATUS_ACTIVE,
            ],
        );

        // Drop factory auto-default if product was created empty then factory-touched elsewhere
        $product->variants()
            ->whereNotIn('id', [$variantS->id, $variantM->id])
            ->delete();

        $variantS->attributeOptions()->sync([
            $optS->id => ['attribute_id' => $size->id],
        ]);
        $variantM->attributeOptions()->sync([
            $optM->id => ['attribute_id' => $size->id],
        ]);

        ProductImage::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'path' => 'demo/products/1.jpg',
            ],
            [
                'alt' => 'Demo Tee',
                'position' => 0,
                'is_primary' => true,
            ],
        );
    }
}
