<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreAttributeOptionRequest;
use App\Http\Requests\Api\V1\Admin\Catalog\UpdateAttributeOptionRequest;
use App\Http\Resources\Catalog\AdminAttributeOptionResource;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Services\Catalog\CatalogAttributeService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use Illuminate\Http\JsonResponse;

class AttributeOptionController extends Controller
{
    public function __construct(private readonly CatalogAttributeService $attributes) {}

    public function store(StoreAttributeOptionRequest $request, int $id): JsonResponse
    {
        $attribute = Attribute::query()->find($id);
        if ($attribute === null) {
            return CatalogNotFound::response();
        }

        $option = $this->attributes->createOption($attribute, $request->validated());

        return ApiResponse::success(AdminAttributeOptionResource::make($option)->resolve(), status: 201);
    }

    public function update(UpdateAttributeOptionRequest $request, int $id, int $optionId): JsonResponse
    {
        $option = $this->findOption($id, $optionId);
        if ($option === null) {
            return CatalogNotFound::response();
        }

        $option = $this->attributes->updateOption($option, $request->validated());

        return ApiResponse::success(AdminAttributeOptionResource::make($option)->resolve());
    }

    public function destroy(int $id, int $optionId): JsonResponse
    {
        $option = $this->findOption($id, $optionId);
        if ($option === null) {
            return CatalogNotFound::response();
        }

        try {
            $this->attributes->deleteOption($option);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(null);
    }

    private function findOption(int $attributeId, int $optionId): ?AttributeOption
    {
        if (Attribute::query()->whereKey($attributeId)->doesntExist()) {
            return null;
        }

        return AttributeOption::query()
            ->where('attribute_id', $attributeId)
            ->whereKey($optionId)
            ->first();
    }
}
