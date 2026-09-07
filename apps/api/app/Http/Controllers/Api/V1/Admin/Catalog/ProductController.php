<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateProductRequest;
use App\Http\Resources\Catalog\AdminProductResource;
use App\Models\AdminUser;
use App\Models\Product;
use App\Services\Catalog\CatalogProductService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogProductService $products) {}

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with($this->products->adminRelations())
            ->latest('id');

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$escaped.'%']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->integer('brand_id'));
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->integer('category_id');
            $query->whereHas('categories', fn ($categories) => $categories->where('categories.id', $categoryId));
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminProductResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $product = $this->products->create($request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminProductResource::make($product)->resolve(), status: 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::query()->with($this->products->adminRelations())->find($id);
        if ($product === null) {
            return CatalogNotFound::response();
        }

        return ApiResponse::success(AdminProductResource::make($product)->resolve());
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product === null) {
            return CatalogNotFound::response();
        }

        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $product = $this->products->update($product, $request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminProductResource::make($product)->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product === null) {
            return CatalogNotFound::response();
        }

        $this->products->delete($product);

        return ApiResponse::success(null);
    }
}
