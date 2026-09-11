<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreCustomerReviewRequest;
use App\Http\Requests\Api\V1\Customer\UpdateCustomerReviewRequest;
use App\Http\Resources\Engagement\CustomerReviewResource;
use App\Models\ProductReview;
use App\Services\Engagement\ReviewService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[Response(200, 'Customer reviews.', type: 'array{data: list<CustomerReviewResource>, meta: object}')]
    public function index(Request $request): JsonResponse
    {
        $paginator = ProductReview::query()
            ->where('customer_id', $request->user('customer')->id)
            ->with('images')
            ->latest('id')
            ->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            CustomerReviewResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(200, 'Review eligibility for a product.', type: 'array{data: array{eligible: bool, reason: string, existing_review: array{id: int, status: string}|null}, meta: object}')]
    public function eligibility(Request $request, int $productId): JsonResponse
    {
        return ApiResponse::success($this->reviews->eligibility($request->user('customer'), $productId));
    }

    #[Response(201, 'Created pending review.', type: 'array{data: CustomerReviewResource, meta: object}')]
    public function store(StoreCustomerReviewRequest $request): JsonResponse
    {
        $review = $this->reviews->create(
            $request->user('customer'),
            $request->validated(),
            $this->uploadedImages($request->file('images')),
        );

        return ApiResponse::success((new CustomerReviewResource($review))->resolve(), status: 201);
    }

    #[Response(200, 'Updated customer review.', type: 'array{data: CustomerReviewResource, meta: object}')]
    public function update(UpdateCustomerReviewRequest $request, int $id): JsonResponse
    {
        $customer = $request->user('customer');
        $review = $this->reviews->findOwned($customer, $id);
        $validated = $request->validated();
        $removeIds = array_map('intval', $validated['remove_image_ids'] ?? []);
        unset($validated['remove_image_ids'], $validated['images']);

        $review = $this->reviews->updateOwn(
            $customer,
            $review,
            $validated,
            $this->uploadedImages($request->file('images')),
            $removeIds,
        );

        return ApiResponse::success((new CustomerReviewResource($review))->resolve());
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @return list<UploadedFile>
     */
    private function uploadedImages(mixed $files): array
    {
        if ($files === null) {
            return [];
        }

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
    }
}
