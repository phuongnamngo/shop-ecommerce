<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreCatalogImageRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateCatalogImageRequest;
use App\Http\Resources\Catalog\CatalogImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\CatalogImageService;
use App\Support\ApiResponse;
use App\Support\CatalogNotFound;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function __construct(private readonly CatalogImageService $images) {}

    public function store(StoreCatalogImageRequest $request, int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product === null) {
            return CatalogNotFound::response();
        }

        $image = $this->images->attachProduct($product, $request->validated());

        return ApiResponse::success(CatalogImageResource::make($image)->resolve(), status: 201);
    }

    public function update(UpdateCatalogImageRequest $request, int $id, int $imageId): JsonResponse
    {
        $image = $this->findImage($id, $imageId);
        if ($image === null) {
            return CatalogNotFound::response();
        }

        $image = $this->images->updateProductImage($image, $request->validated());

        return ApiResponse::success(CatalogImageResource::make($image)->resolve());
    }

    public function destroy(int $id, int $imageId): JsonResponse
    {
        $image = $this->findImage($id, $imageId);
        if ($image === null) {
            return CatalogNotFound::response();
        }

        $this->images->softDeleteImage($image);

        return ApiResponse::success(null);
    }

    private function findImage(int $productId, int $imageId): ?ProductImage
    {
        if (Product::query()->whereKey($productId)->doesntExist()) {
            return null;
        }

        return ProductImage::query()
            ->where('product_id', $productId)
            ->whereKey($imageId)
            ->first();
    }
}
