<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreBrandRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateBrandRequest;
use App\Http\Resources\Catalog\AdminBrandResource;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Services\Catalog\CatalogBrandService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private readonly CatalogBrandService $brands) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = Brand::query()
            ->latest('id')
            ->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminBrandResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $brand = $this->brands->create($request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminBrandResource::make($brand)->resolve(), status: 201);
    }

    public function show(int $id): JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand === null) {
            return CatalogNotFound::response();
        }

        return ApiResponse::success(AdminBrandResource::make($brand)->resolve());
    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand === null) {
            return CatalogNotFound::response();
        }

        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $brand = $this->brands->update($brand, $request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminBrandResource::make($brand)->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand === null) {
            return CatalogNotFound::response();
        }

        try {
            $this->brands->delete($brand);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(null);
    }
}
