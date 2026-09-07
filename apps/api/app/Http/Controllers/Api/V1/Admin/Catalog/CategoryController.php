<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateCategoryRequest;
use App\Http\Resources\Catalog\AdminCategoryResource;
use App\Models\AdminUser;
use App\Models\Category;
use App\Services\Catalog\CatalogCategoryService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogCategoryService $categories) {}

    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->latest('id');

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$escaped.'%']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->exists('parent_id')) {
            $parentId = $request->input('parent_id');
            $query->where('parent_id', $parentId === '' || $parentId === null ? null : (int) $parentId);
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminCategoryResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $category = $this->categories->create($request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminCategoryResource::make($category)->resolve(), status: 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category === null) {
            return CatalogNotFound::response();
        }

        return ApiResponse::success(AdminCategoryResource::make($category)->resolve());
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category === null) {
            return CatalogNotFound::response();
        }

        try {
            /** @var AdminUser $admin */
            $admin = $request->user('admin');
            $category = $this->categories->update($category, $request->validated(), $admin->id);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminCategoryResource::make($category)->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category === null) {
            return CatalogNotFound::response();
        }

        try {
            $this->categories->delete($category);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(null);
    }
}
