<?php

namespace App\Observers;

use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

final class CatalogSearchObserver
{
    public function saved(Brand|Category|AttributeOption|ProductVariant $model): void
    {
        $this->reindexRelated($model);
    }

    public function deleted(Brand|Category|AttributeOption|ProductVariant $model): void
    {
        $this->reindexRelated($model);
    }

    private function reindexRelated(Brand|Category|AttributeOption|ProductVariant $model): void
    {
        if ($model instanceof ProductVariant) {
            $this->reindexProduct($model->product_id);

            return;
        }

        if ($model instanceof Brand) {
            Product::query()->where('brand_id', $model->id)->get()->each->syncSearchIndex();

            return;
        }

        if ($model instanceof Category) {
            Product::query()
                ->whereHas('categories', fn ($query) => $query->where('categories.id', $model->id))
                ->get()
                ->each
                ->syncSearchIndex();

            return;
        }

        Product::query()
            ->whereHas(
                'variants.attributeOptions',
                fn ($query) => $query->where('attribute_options.id', $model->id),
            )
            ->get()
            ->each
            ->syncSearchIndex();
    }

    private function reindexProduct(?int $productId): void
    {
        if ($productId === null) {
            return;
        }

        $product = Product::query()->find($productId);
        if ($product === null) {
            return;
        }

        $product->syncSearchIndex();
    }
}
