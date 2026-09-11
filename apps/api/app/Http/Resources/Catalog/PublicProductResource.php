<?php

namespace App\Http\Resources\Catalog;

use App\Models\Brand;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogCategoryService;
use App\Services\Promotion\FlashSalePricingService;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Product
 */
class PublicProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offers = $this->flashOffers();

        $payload = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'brand' => $this->publicBrand(),
            'primary_image' => $this->primaryImage(),
            'default_variant' => $this->whenLoaded('defaultVariant', fn () => $this->defaultVariant === null ? null : $this->publicDefaultVariant($offers)),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'rating_avg' => ((int) ($this->rating_count ?? 0)) === 0
                ? null
                : round((float) $this->rating_avg, 1),
        ];

        if ($this->relationLoaded('categories')) {
            $payload['description'] = $this->description;
            $payload['meta_title'] = $this->meta_title;
            $payload['meta_description'] = $this->meta_description;
            $payload['categories'] = $this->publicCategories();
            $payload['images'] = CatalogImageResource::collection($this->whenLoaded('images'));
            $payload['variants'] = $this->publicVariants($offers);
        }

        return $payload;
    }

    /**
     * @return Collection<int, FlashSaleItem>
     */
    private function flashOffers(): Collection
    {
        $ids = [];
        if ($this->relationLoaded('defaultVariant') && $this->defaultVariant !== null) {
            $ids[] = $this->defaultVariant->id;
        }
        if ($this->relationLoaded('variants')) {
            $ids = array_merge($ids, $this->variants->pluck('id')->all());
        }

        return app(FlashSalePricingService::class)->offersForVariants($ids);
    }

    /**
     * @param  Collection<int, FlashSaleItem>  $offers
     * @return array<string, mixed>
     */
    private function publicDefaultVariant(Collection $offers): array
    {
        $variant = $this->defaultVariant;
        $payload = [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'compare_at_price' => $variant->compare_at_price,
        ];

        return $this->applyFlashOverlay($payload, $variant, $offers->get($variant->id));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyFlashOverlay(array $payload, ProductVariant $variant, ?FlashSaleItem $offer): array
    {
        if ($offer === null) {
            return $payload;
        }

        $payload['price'] = $offer->sale_price;
        $payload['compare_at_price'] = $variant->price;
        $payload['flash_sale'] = [
            'id' => $offer->flash_sale_id,
            'ends_at' => $offer->flashSale?->ends_at,
            'qty_remaining' => $offer->qtyRemaining(),
        ];

        return $payload;
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    private function publicBrand(): ?array
    {
        if (! $this->relationLoaded('brand') || $this->brand === null) {
            return null;
        }

        if ($this->brand->status !== Brand::STATUS_ACTIVE) {
            return null;
        }

        return [
            'id' => $this->brand->id,
            'name' => $this->brand->name,
            'slug' => $this->brand->slug,
        ];
    }

    /**
     * @return array{url: string, thumbnail_url: string, alt: string|null}|null
     */
    private function primaryImage(): ?array
    {
        if (! $this->relationLoaded('images')) {
            return null;
        }

        $image = $this->images->firstWhere('is_primary', true);
        if ($image === null) {
            return null;
        }

        return [
            'url' => CatalogImagePath::url($image->path),
            'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
            'alt' => $image->alt,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publicCategories(): array
    {
        $categories = app(CatalogCategoryService::class);

        return $this->categories
            ->filter(fn ($category) => $categories->isPublicVisible($category))
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'position' => $category->position,
                'description' => $category->description,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FlashSaleItem>  $offers
     * @return list<array<string, mixed>>
     */
    private function publicVariants(Collection $offers): array
    {
        if (! $this->relationLoaded('variants')) {
            return [];
        }

        return $this->variants
            ->filter(fn (ProductVariant $variant) => $variant->status === ProductVariant::STATUS_ACTIVE)
            ->map(function (ProductVariant $variant) use ($offers) {
                $payload = [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'is_default' => $variant->is_default,
                    'attributes' => $variant->relationLoaded('attributeOptions')
                        ? $variant->attributeOptions->map(fn ($option) => [
                            'id' => $option->attribute?->id,
                            'name' => $option->attribute?->name,
                            'slug' => $option->attribute?->slug,
                            'option' => [
                                'id' => $option->id,
                                'label' => $option->label,
                            ],
                        ])->values()->all()
                        : [],
                    'images' => $variant->relationLoaded('images')
                        ? CatalogImageResource::collection($variant->images)->resolve()
                        : [],
                ];

                return $this->applyFlashOverlay($payload, $variant, $offers->get($variant->id));
            })
            ->values()
            ->all();
    }
}
