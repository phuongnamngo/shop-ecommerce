<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Engagement\PublicProductReviewResource;
use App\Models\Product;
use App\Services\Catalog\CatalogProductService;
use App\Services\Engagement\ReviewService;
use App\Support\ApiResponse;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductReviewController extends Controller
{
    public function __construct(
        private readonly CatalogProductService $products,
        private readonly ReviewService $reviews,
    ) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[Response(200, 'Approved product reviews.', type: 'array{data: list<PublicProductReviewResource>, meta: object}')]
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = $this->products->applyPublicVisibility(Product::query())
            ->where('slug', $slug)
            ->first();

        if ($product === null) {
            return CatalogNotFound::response();
        }

        $paginator = $this->reviews->publicPaginator($product, CatalogPaginator::perPage($request));
        $rating = $this->reviews->publicMeta($product->id);

        return ApiResponse::success(
            PublicProductReviewResource::collection($paginator->getCollection())->resolve(),
            array_merge(CatalogPaginator::meta($paginator), $rating),
        );
    }
}
