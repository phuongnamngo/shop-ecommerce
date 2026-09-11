<?php

namespace App\Services\Catalog;

use App\Http\Requests\Api\V1\Catalog\PublicProductIndexRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogException;
use App\Support\CatalogImagePath;
use App\Support\CatalogPaginator;
use App\Support\CatalogSearchDocument;
use App\Support\ErrorCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Meilisearch\Client;
use Throwable;

final class CatalogSearchService
{
    public function __construct(
        private readonly CatalogProductService $products,
        private readonly CatalogCategoryService $categories,
    ) {}

    /**
     * @return array{products: Collection<int, Product>, meta: array<string, mixed>}
     */
    public function list(PublicProductIndexRequest $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = CatalogPaginator::perPage($request);
        $q = trim((string) $request->string('q'));
        $sort = (string) $request->string('sort', 'newest');
        if (! in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) {
            $sort = 'newest';
        }

        $filter = $this->listingFilter($request);
        $sorts = match ($sort) {
            'price_asc' => ['price:asc', 'id:desc'],
            'price_desc' => ['price:desc', 'id:desc'],
            default => ['published_at:desc', 'id:desc'],
        };

        $this->waitUntilIdleInTests();

        $options = [
            'offset' => ($page - 1) * $perPage,
            'limit' => $perPage,
            'sort' => $sorts,
            'facets' => ['brand_id', 'category_ids', 'price_bucket', 'attribute_facets'],
        ];
        if ($filter !== '') {
            $options['filter'] = $filter;
        }

        try {
            $result = $this->queryClient()
                ->index((new Product)->searchableAs())
                ->search($q, $options);
        } catch (Throwable $e) {
            $this->unavailable($e);
        }

        $hits = $this->hits($result);
        $ids = [];
        foreach ($hits as $hit) {
            $id = (int) ($hit['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $visible = $this->products
            ->applyPublicVisibility(Product::query()->whereIn('products.id', $ids))
            ->with(['brand', 'defaultVariant', 'images'])
            ->withAvg('approvedReviews as rating_avg', 'rating')
            ->withCount(['approvedReviews as rating_count'])
            ->get()
            ->keyBy('id');

        $products = collect($ids)
            ->map(fn (int $id) => $visible->get($id))
            ->filter()
            ->values();

        $total = $this->estimatedTotal($result);
        $meta = CatalogPaginator::fromTotal($page, $perPage, $total);
        $meta['facets'] = $this->mapFacets($this->facetDistribution($result));

        return ['products' => $products, 'meta' => $meta];
    }

    /**
     * @return array{
     *     products: list<array{id: int, name: string, slug: string, thumbnail_url: string|null, price: mixed}>,
     *     categories: list<array{id: int, name: string, slug: string}>,
     *     brands: list<array{id: int, name: string, slug: string}>
     * }
     */
    public function suggest(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [
                'products' => [],
                'categories' => [],
                'brands' => [],
            ];
        }

        $this->waitUntilIdleInTests();

        try {
            $productHits = $this->hits(
                $this->queryClient()->index((new Product)->searchableAs())->search($q, ['limit' => 5]),
            );
            $categoryHits = $this->hits(
                $this->queryClient()->index((new Category)->searchableAs())->search($q, ['limit' => 3]),
            );
            $brandHits = $this->hits(
                $this->queryClient()->index((new Brand)->searchableAs())->search($q, ['limit' => 3]),
            );
        } catch (Throwable $e) {
            $this->unavailable($e);
        }

        $productIds = $this->hitIds($productHits);
        $products = $this->products
            ->applyPublicVisibility(Product::query()->whereIn('products.id', $productIds))
            ->with(['defaultVariant', 'images'])
            ->get()
            ->keyBy('id');

        $productItems = [];
        foreach ($productIds as $id) {
            $product = $products->get($id);
            if ($product === null) {
                continue;
            }
            $image = $product->images->firstWhere('is_primary', true);
            $productItems[] = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail_url' => $image === null
                    ? null
                    : CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
                'price' => $product->defaultVariant?->price,
            ];
        }

        return [
            'products' => $productItems,
            'categories' => $this->hydrateSuggest(Category::class, $this->hitIds($categoryHits)),
            'brands' => $this->hydrateSuggest(Brand::class, $this->hitIds($brandHits), fn (Brand $brand) => $brand->status === Brand::STATUS_ACTIVE),
        ];
    }

    private function listingFilter(PublicProductIndexRequest $request): string
    {
        $parts = [];

        if ($request->filled('brand_id')) {
            $parts[] = 'brand_id = '.$request->integer('brand_id');
        }

        if ($request->filled('category_id')) {
            $ids = $this->categories->descendantIds($request->integer('category_id'));
            if ($ids === []) {
                $parts[] = 'id = -1';
            } else {
                $parts[] = 'category_ids IN ['.implode(', ', $ids).']';
            }
        }

        $bucket = $request->input('price_bucket');
        if (is_string($bucket) && $bucket !== '') {
            $parts[] = 'price_bucket = "'.$bucket.'"';
        }

        $tokens = $this->resolveFacetTokens($request->input('attribute_facets', []));
        $groups = [];
        foreach ($tokens as $token) {
            $slug = explode(':', $token, 2)[0];
            $groups[$slug][] = 'attribute_facets = "'.str_replace('"', '\\"', $token).'"';
        }
        foreach ($groups as $clauses) {
            $parts[] = count($clauses) === 1
                ? $clauses[0]
                : '('.implode(' OR ', $clauses).')';
        }

        return implode(' AND ', $parts);
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<string>
     */
    private function resolveFacetTokens(array $raw): array
    {
        $resolved = [];
        foreach ($raw as $token) {
            if (! is_string($token) || $token === '') {
                continue;
            }
            if ($this->facetTokenExists($token)) {
                $resolved[] = $token;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function facetTokenExists(string $token): bool
    {
        $pos = strpos($token, ':');
        if ($pos === false) {
            return false;
        }

        $attrSlug = substr($token, 0, $pos);
        $optionPart = substr($token, $pos + 1);
        $attribute = Attribute::query()->where('slug', $attrSlug)->first();
        if ($attribute === null) {
            return false;
        }

        return AttributeOption::query()
            ->where('attribute_id', $attribute->id)
            ->get()
            ->contains(function (AttributeOption $option) use ($optionPart): bool {
                $base = Str::slug((string) $option->label);

                return $optionPart === $base || $optionPart === $base.'-'.$option->id;
            });
    }

    /**
     * @param  array<string, array<string, int>>  $distribution
     * @return array<string, mixed>
     */
    private function mapFacets(array $distribution): array
    {
        $brandCounts = $distribution['brand_id'] ?? [];
        $brandIds = array_map('intval', array_keys($brandCounts));
        $brands = Brand::query()->whereIn('id', $brandIds)->get()->keyBy('id');
        $brandFacets = [];
        foreach ($brandCounts as $id => $count) {
            $brand = $brands->get((int) $id);
            if ($brand === null) {
                continue;
            }
            $brandFacets[] = [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'count' => (int) $count,
            ];
        }

        $categoryCounts = $distribution['category_ids'] ?? [];
        $categoryIds = array_map('intval', array_keys($categoryCounts));
        $categories = Category::query()->whereIn('id', $categoryIds)->get()->keyBy('id');
        $categoryFacets = [];
        foreach ($categoryCounts as $id => $count) {
            $category = $categories->get((int) $id);
            if ($category === null) {
                continue;
            }
            $categoryFacets[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => (int) $count,
            ];
        }

        $bucketCounts = $distribution['price_bucket'] ?? [];
        $priceBuckets = [];
        foreach (CatalogSearchDocument::PRICE_BUCKET_LABELS as $token => $label) {
            $priceBuckets[] = [
                'token' => $token,
                'label' => $label,
                'count' => (int) ($bucketCounts[$token] ?? 0),
            ];
        }

        $attrCounts = $distribution['attribute_facets'] ?? [];
        $grouped = [];
        foreach ($attrCounts as $token => $count) {
            $pos = strpos((string) $token, ':');
            if ($pos === false) {
                continue;
            }
            $slug = substr((string) $token, 0, $pos);
            $grouped[$slug][(string) $token] = (int) $count;
        }

        $attributes = [];
        $attrModels = Attribute::query()->whereIn('slug', array_keys($grouped))->get()->keyBy('slug');
        $options = AttributeOption::query()
            ->whereIn('attribute_id', $attrModels->pluck('id'))
            ->get()
            ->groupBy('attribute_id');

        foreach ($grouped as $slug => $tokens) {
            $attribute = $attrModels->get($slug);
            if ($attribute === null) {
                continue;
            }
            $attrOptions = $options->get($attribute->id, collect());
            $mapped = [];
            foreach ($tokens as $token => $count) {
                $optionPart = substr($token, strlen($slug) + 1);
                $label = $optionPart;
                foreach ($attrOptions as $option) {
                    $base = Str::slug((string) $option->label);
                    if ($optionPart === $base || $optionPart === $base.'-'.$option->id) {
                        $label = (string) $option->label;
                        break;
                    }
                }
                $mapped[] = [
                    'token' => $token,
                    'label' => $label,
                    'count' => $count,
                ];
            }
            $attributes[] = [
                'slug' => $slug,
                'name' => $attribute->name,
                'options' => $mapped,
            ];
        }

        return [
            'brands' => $brandFacets,
            'categories' => $categoryFacets,
            'price_buckets' => $priceBuckets,
            'attributes' => $attributes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hits
     * @return list<int>
     */
    private function hitIds(array $hits): array
    {
        $ids = [];
        foreach ($hits as $hit) {
            $id = (int) ($hit['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  class-string<Brand|Category>  $class
     * @param  list<int>  $ids
     * @param  (callable(Brand|Category): bool)|null  $ok
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function hydrateSuggest(string $class, array $ids, ?callable $ok = null): array
    {
        $models = $class::query()->whereIn('id', $ids)->get()->keyBy('id');
        $items = [];
        foreach ($ids as $id) {
            $model = $models->get($id);
            if ($model === null) {
                continue;
            }
            if ($ok !== null && ! $ok($model)) {
                continue;
            }
            if ($model instanceof Category && ! $this->categories->isPublicVisible($model)) {
                continue;
            }
            $items[] = [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
            ];
        }

        return $items;
    }

    private function waitUntilIdleInTests(): void
    {
        if (! app()->runningUnitTests() || ! function_exists('waitForTestingSearchIdle')) {
            return;
        }

        waitForTestingSearchIdle();
    }

    private function queryClient(): Client
    {
        $timeout = [
            'timeout' => 2.0,
            'connect_timeout' => 1.0,
        ];
        $http = class_exists(\GuzzleHttp\Client::class)
            ? new \GuzzleHttp\Client($timeout)
            : null;

        return new Client(
            (string) config('scout.meilisearch.host'),
            config('scout.meilisearch.key'),
            $http,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function hits(mixed $result): array
    {
        if (is_object($result) && method_exists($result, 'getHits')) {
            return $result->getHits();
        }

        return is_array($result) ? ($result['hits'] ?? []) : [];
    }

    private function estimatedTotal(mixed $result): int
    {
        if (is_object($result) && method_exists($result, 'getEstimatedTotalHits')) {
            return (int) $result->getEstimatedTotalHits();
        }
        if (is_object($result) && method_exists($result, 'getTotalHits')) {
            return (int) ($result->getTotalHits() ?? 0);
        }

        return (int) (is_array($result) ? ($result['estimatedTotalHits'] ?? $result['totalHits'] ?? 0) : 0);
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function facetDistribution(mixed $result): array
    {
        if (is_object($result) && method_exists($result, 'getFacetDistribution')) {
            return $result->getFacetDistribution() ?? [];
        }

        return is_array($result) ? ($result['facetDistribution'] ?? []) : [];
    }

    private function unavailable(Throwable $e): never
    {
        report($e);

        throw new CatalogException(
            ErrorCode::CATALOG_SEARCH_UNAVAILABLE,
            'Catalog search is temporarily unavailable.',
            status: 503,
        );
    }
}
