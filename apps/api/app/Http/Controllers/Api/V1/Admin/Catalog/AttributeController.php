<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreAttributeRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateAttributeRequest;
use App\Http\Resources\Catalog\AdminAttributeResource;
use App\Models\Attribute;
use App\Services\Catalog\CatalogAttributeService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function __construct(private readonly CatalogAttributeService $attributes) {}

    public function index(Request $request): JsonResponse
    {
        $query = Attribute::query()->with('options')->orderBy('position')->orderBy('id');

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.$escaped.'%']);
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminAttributeResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function store(StoreAttributeRequest $request): JsonResponse
    {
        try {
            $attribute = $this->attributes->create($request->validated());
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminAttributeResource::make($attribute)->resolve(), status: 201);
    }

    public function show(int $id): JsonResponse
    {
        $attribute = Attribute::query()->with('options')->find($id);
        if ($attribute === null) {
            return CatalogNotFound::response();
        }

        return ApiResponse::success(AdminAttributeResource::make($attribute)->resolve());
    }

    public function update(UpdateAttributeRequest $request, int $id): JsonResponse
    {
        $attribute = Attribute::query()->find($id);
        if ($attribute === null) {
            return CatalogNotFound::response();
        }

        try {
            $attribute = $this->attributes->update($attribute, $request->validated());
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(AdminAttributeResource::make($attribute)->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $attribute = Attribute::query()->find($id);
        if ($attribute === null) {
            return CatalogNotFound::response();
        }

        try {
            $this->attributes->delete($attribute);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(null);
    }
}
