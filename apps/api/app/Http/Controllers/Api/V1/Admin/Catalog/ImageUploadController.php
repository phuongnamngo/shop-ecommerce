<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Catalog\StoreCatalogUploadRequest;
use App\Services\Catalog\CatalogImageService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ImageUploadController extends Controller
{
    public function __construct(private readonly CatalogImageService $images) {}

    public function store(StoreCatalogUploadRequest $request): JsonResponse
    {
        try {
            $payload = $this->images->storeUploaded($request->file('file'));
        } catch (\Throwable) {
            return ApiResponse::validationErrors(['file' => ['Unable to process the uploaded image.']]);
        }

        return ApiResponse::success($payload, status: 201);
    }
}
