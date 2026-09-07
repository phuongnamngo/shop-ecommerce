<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\PublicProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogCategoryService;
use App\Services\Catalog\CatalogProductService;
use App\Support\ApiResponse;
use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogProductService $products,
        private readonly CatalogCategoryService $categories,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->products->applyPublicVisibility(Product::query()->select('products.*'));

        if ($request->filled('brand_id')) {
            $query->where('products.brand_id', $request->integer('brand_id'));
        }

        if ($request->filled('category_id')) {
            $ids = $this->categories->descendantIds($request->integer('category_id'));
            $query->whereHas('categories', fn ($categories) => $categories->whereIn('categories.id', $ids));
        }

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereRaw('LOWER(products.name) LIKE ?', ['%'.$escaped.'%']);
        }

        $sort = (string) $request->string('sort', 'newest');
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $query->join('product_variants as catalog_default_variants', function ($join): void {
                $join->on('catalog_default_variants.product_id', '=', 'products.id')
                    ->where('catalog_default_variants.is_default', true)
                    ->whereNull('catalog_default_variants.deleted_at')
                    ->where('catalog_default_variants.status', ProductVariant::STATUS_ACTIVE);
            });
            $query->orderBy('catalog_default_variants.price', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('products.id', 'desc');
        } else {
            $query->orderBy('products.published_at', 'desc')->orderBy('products.id', 'desc');
        }

        $paginator = $query
            ->with(['brand', 'defaultVariant', 'images'])
            ->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            PublicProductResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->products->applyPublicVisibility(Product::query())
            ->where('slug', $slug)
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
