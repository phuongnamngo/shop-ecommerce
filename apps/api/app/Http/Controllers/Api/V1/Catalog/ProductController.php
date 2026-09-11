<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\PublicProductIndexRequest;
use App\Http\Resources\Catalog\PublicProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogProductService;
use App\Services\Catalog\CatalogSearchService;
use App\Support\ApiResponse;
use App\Support\CatalogError;
use App\Support\CatalogException;
use App\Support\CatalogNotFound;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogProductService $products,
        private readonly CatalogSearchService $search,
    ) {}

    #[QueryParameter('q', description: 'Full-text query. Empty browse returns all public products.', type: 'string')]
    #[QueryParameter('brand_id', description: 'Filter by brand id.', type: 'int')]
    #[QueryParameter('category_id', description: 'Filter by category id including descendants.', type: 'int')]
    #[QueryParameter('sort', description: 'newest, price_asc, or price_desc.', type: 'string', default: 'newest')]
    #[QueryParameter('price_bucket', description: 'lt_300k, 300_500k, or 500k_plus.', type: 'string')]
    #[QueryParameter('attribute_facets', description: 'Repeated facet tokens attribute-slug:option-slug.', type: 'list<string>')]
    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[Response(200, 'Public product listing from Meilisearch.', type: 'array{data: list<PublicProductResource>, meta: object}')]
    public function index(PublicProductIndexRequest $request): JsonResponse
    {
        try {
            $result = $this->search->list($request);
        } catch (CatalogException $e) {
            return CatalogError::from($e);
        }

        return ApiResponse::success(
            PublicProductResource::collection($result['products'])->resolve(),
            $result['meta'],
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->products->applyPublicVisibility(Product::query())
            ->where('slug', $slug)
            ->withAvg('approvedReviews as rating_avg', 'rating')
            ->withCount(['approvedReviews as rating_count'])
            ->with([
                'brand',
                'defaultVariant',
                'images',
                'categories.parent',
                'variants' => fn ($variants) => $variants->where('status', ProductVariant::STATUS_ACTIVE)->orderBy('id'),
                'variants.attributeOptions.attribute',
                'variants.images',
            ])
            ->first();

        if ($product === null) {
            return CatalogNotFound::response();
        }

        return ApiResponse::success(PublicProductResource::make($product)->resolve());
    }
}
