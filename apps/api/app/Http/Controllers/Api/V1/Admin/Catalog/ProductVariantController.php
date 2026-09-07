<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreProductVariantRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateProductVariantRequest;
use App\Http\Resources\Catalog\AdminProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogVariantService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function __construct(private readonly CatalogVariantService $variants) {}

    public function store(StoreProductVariantRequest $request, int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product === null) {
            return CatalogNotFound::response();
        }

        try {
            $variant = $this->variants->create($product, $request->validated());
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminProductVariantResource::make($variant)->resolve(), status: 201);
    }

    public function update(UpdateProductVariantRequest $request, int $id, int $variantId): JsonResponse
    {
        $variant = $this->findVariant($id, $variantId);
        if ($variant === null) {
            return CatalogNotFound::response();
        }

        try {
            $variant = $this->variants->update($variant, $request->validated());
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminProductVariantResource::make($variant)->resolve());
    }

    public function destroy(int $id, int $variantId): JsonResponse
    {
        $variant = $this->findVariant($id, $variantId);
        if ($variant === null) {
            return CatalogNotFound::response();
        }

        try {
            $this->variants->delete($variant);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

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
}
