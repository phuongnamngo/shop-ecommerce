<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Catalog\CatalogSearchService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __construct(private readonly CatalogSearchService $search) {}

    #[QueryParameter('q', description: 'Suggest query. Shorter than 2 characters returns empty groups.', type: 'string')]
    #[Response(200, 'Autocomplete groups for products, categories, and brands.')]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success($this->search->suggest((string) $request->string('q')));
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }
    }
}
