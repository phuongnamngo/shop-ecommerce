<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreCatalogImageRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateCatalogImageRequest;
use App\Http\Resources\Catalog\CatalogImageResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Services\Catalog\CatalogImageService;
use App\Support\ApiResponse;
use App\Support\CatalogNotFound;
use Illuminate\Http\JsonResponse;

class ProductVariantImageController extends Controller
{
    public function __construct(private readonly CatalogImageService $images) {}

    public function store(StoreCatalogImageRequest $request, int $id, int $variantId): JsonResponse
    {
        $variant = $this->findVariant($id, $variantId);
        if ($variant === null) {
            return CatalogNotFound::response();
        }

        $image = $this->images->attachVariant($variant, $request->validated());

        return ApiResponse::success(CatalogImageResource::make($image)->resolve(), status: 201);
    }

    public function update(UpdateCatalogImageRequest $request, int $id, int $variantId, int $imageId): JsonResponse
    {
        $image = $this->findImage($id, $variantId, $imageId);
        if ($image === null) {
            return CatalogNotFound::response();
        }

        $image = $this->images->updateVariantImage($image, $request->validated());

        return ApiResponse::success(CatalogImageResource::make($image)->resolve());
    }

    public function destroy(int $id, int $variantId, int $imageId): JsonResponse
    {
        $image = $this->findImage($id, $variantId, $imageId);
        if ($image === null) {
            return CatalogNotFound::response();
        }

        $this->images->softDeleteImage($image);

        return ApiResponse::success(null);
    }

    private function findVariant(int $productId, int $variantId): ?ProductVariant
    {
        if (Product::query()->whereKey($productId)->doesntExist()) {
            return null;
        }

        return ProductVariant::query()
            ->where('product_id', $productId)
            ->whereKey($variantId)
            ->first();
    }

    private function findImage(int $productId, int $variantId, int $imageId): ?ProductVariantImage
    {
        $variant = $this->findVariant($productId, $variantId);
        if ($variant === null) {
            return null;
        }

        return ProductVariantImage::query()
            ->where('product_variant_id', $variant->id)
            ->whereKey($imageId)
            ->first();
    }
}
