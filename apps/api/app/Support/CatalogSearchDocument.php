<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

final class CatalogSearchDocument
{
    public const PRICE_BUCKET_LT_300K = 'lt_300k';

    public const PRICE_BUCKET_300_500K = '300_500k';

    public const PRICE_BUCKET_500K_PLUS = '500k_plus';

    /**
     * @var array<string, string>
     */
    public const PRICE_BUCKET_LABELS = [
        self::PRICE_BUCKET_LT_300K => 'Dưới 300.000đ',
        self::PRICE_BUCKET_300_500K => '300.000đ – dưới 500.000đ',
        self::PRICE_BUCKET_500K_PLUS => 'Từ 500.000đ',
    ];

    public static function priceBucket(int|float|string $price): string
    {
        $value = (float) $price;

        if ($value < 300000) {
            return self::PRICE_BUCKET_LT_300K;
        }

        if ($value < 500000) {
            return self::PRICE_BUCKET_300_500K;
        }

        return self::PRICE_BUCKET_500K_PLUS;
    }

    /**
     * @return list<string>
     */
    public static function attributeFacets(Product $product): array
    {
        $product->load(['variants.attributeOptions.attribute']);

        $optionsByAttribute = [];
        foreach ($product->variants as $variant) {
            if ($variant->status !== ProductVariant::STATUS_ACTIVE) {
                continue;
            }

            foreach ($variant->attributeOptions as $option) {
                $attribute = $option->attribute;
                if ($attribute === null) {
                    continue;
                }

                $optionsByAttribute[$attribute->id]['slug'] = $attribute->slug;
                $optionsByAttribute[$attribute->id]['options'][$option->id] = $option;
            }
        }

        $tokens = [];
        foreach ($optionsByAttribute as $group) {
            $slugCounts = [];
            foreach ($group['options'] as $option) {
                $optionSlug = Str::slug((string) $option->label);
                $slugCounts[$optionSlug] = ($slugCounts[$optionSlug] ?? 0) + 1;
            }

            foreach ($group['options'] as $option) {
                $optionSlug = Str::slug((string) $option->label);
                $token = $group['slug'].':'.$optionSlug;
                if ($slugCounts[$optionSlug] > 1) {
                    $token .= '-'.$option->id;
                }
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @return array<string, mixed>
     */
    public static function forProduct(Product $product): array
    {
        $product->load([
            'brand',
            'categories',
            'defaultVariant',
            'variants.attributeOptions.attribute',
        ]);

        $price = (float) ($product->defaultVariant?->price ?? 0);
        $labels = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->attributeOptions as $option) {
                if (is_string($option->label) && $option->label !== '') {
                    $labels[] = $option->label;
                }
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => (string) ($product->description ?? ''),
            'brand_name' => (string) ($product->brand?->name ?? ''),
            'category_names' => $product->categories->pluck('name')->filter()->values()->all(),
            'skus' => $product->variants->pluck('sku')->filter()->values()->all(),
            'attribute_labels' => array_values(array_unique($labels)),
            'brand_id' => $product->brand_id,
            'category_ids' => $product->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'price_bucket' => self::priceBucket($price),
            'attribute_facets' => self::attributeFacets($product),
            'price' => $price,
            'published_at' => $product->published_at?->getTimestamp() ?? 0,
        ];
    }
}
