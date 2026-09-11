<?php

namespace App\Http\Controllers\Api\V1\Admin\Engagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Engagement\UpdateAdminReviewRequest;
use App\Http\Resources\Engagement\AdminReviewResource;
use App\Models\ProductReview;
use App\Services\Engagement\ReviewService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[QueryParameter('q', description: 'Search product name or customer name/email.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by review status.', type: 'string')]
    #[QueryParameter('product_id', description: 'Filter by product id.', type: 'int')]
    #[Response(200, 'Paginated admin reviews.', type: 'array{data: list<AdminReviewResource>, meta: object}')]
    public function index(Request $request): JsonResponse
    {
        $query = ProductReview::query()
            ->with(['customer', 'product', 'images'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }
        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $like = '%'.$escaped.'%';
            $query->where(function ($inner) use ($like): void {
                $inner->whereHas('product', fn ($products) => $products->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('customer', function ($customers) use ($like): void {
                        $customers->whereRaw('LOWER(name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminReviewResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(200, 'Admin review detail.', type: 'array{data: AdminReviewResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        return ApiResponse::success((new AdminReviewResource($this->find($id)))->resolve());
    }

    #[Response(200, 'Moderated review.', type: 'array{data: AdminReviewResource, meta: object}')]
    public function update(UpdateAdminReviewRequest $request, int $id): JsonResponse
    {
        $review = $this->reviews->moderate($this->find($id), (string) $request->validated('status'));

        return ApiResponse::success((new AdminReviewResource($review))->resolve());
    }

    private function find(int $id): ProductReview
    {
        $review = ProductReview::query()->with(['customer', 'product', 'images'])->find($id);
        if ($review === null) {
            throw new CommerceException(ErrorCode::REVIEW_NOT_FOUND, 'Review not found.', 'id', 404);
        }

        return $review;
    }
}
