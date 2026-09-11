<?php

namespace App\Services\Engagement;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewImage;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogImageService;
use App\Support\CatalogImagePath;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ReviewService
{
    public function __construct(private readonly CatalogImageService $images) {}

    /**
     * @return array{eligible: bool, reason: 'ok'|'not_purchased'|'pending'|'rejected'|'approved', existing_review: array{id: int, status: string}|null}
     */
    public function eligibility(Customer $customer, int $productId): array
    {
        $existing = ProductReview::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing !== null) {
            $reason = match ($existing->status) {
                ProductReview::STATUS_PENDING => 'pending',
                ProductReview::STATUS_REJECTED => 'rejected',
                ProductReview::STATUS_APPROVED => 'approved',
                default => 'pending',
            };

            return [
                'eligible' => false,
                'reason' => $reason,
                'existing_review' => [
                    'id' => $existing->id,
                    'status' => $existing->status,
                ],
            ];
        }

        if (! $this->hasPurchased($customer, $productId)) {
            return [
                'eligible' => false,
                'reason' => 'not_purchased',
                'existing_review' => null,
            ];
        }

        return [
            'eligible' => true,
            'reason' => 'ok',
            'existing_review' => null,
        ];
    }

    public function hasPurchased(Customer $customer, int $productId): bool
    {
        $variantIds = ProductVariant::withTrashed()
            ->where('product_id', $productId)
            ->pluck('id');

        if ($variantIds->isEmpty()) {
            return false;
        }

        return Order::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['shipped', 'completed'])
            ->whereHas('items', fn ($items) => $items->whereIn('product_variant_id', $variantIds))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<UploadedFile>  $files
     */
    public function create(Customer $customer, array $payload, array $files): ProductReview
    {
        $productId = (int) $payload['product_id'];
        $eligibility = $this->eligibility($customer, $productId);
        if ($eligibility['reason'] === 'not_purchased') {
            throw new CommerceException(ErrorCode::REVIEW_NOT_ELIGIBLE, 'A completed purchase is required to review this product.', 'product_id', 422);
        }
        if ($eligibility['existing_review'] !== null) {
            throw new CommerceException(ErrorCode::REVIEW_ALREADY_EXISTS, 'You have already reviewed this product.', 'product_id', 409);
        }

        $this->assertVariantBelongsToProduct($payload['product_variant_id'] ?? null, $productId);

        try {
            $review = ProductReview::query()->create([
                'customer_id' => $customer->id,
                'product_id' => $productId,
                'product_variant_id' => $payload['product_variant_id'] ?? null,
                'rating' => (int) $payload['rating'],
                'body' => $payload['body'] ?? null,
                'status' => ProductReview::STATUS_PENDING,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new CommerceException(ErrorCode::REVIEW_ALREADY_EXISTS, 'You have already reviewed this product.', 'product_id', 409);
        }

        $this->storeImages($review, $files, 0);

        return $this->load($review);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<UploadedFile>  $files
     * @param  list<int>  $removeIds
     */
    public function updateOwn(Customer $customer, ProductReview $review, array $payload, array $files, array $removeIds): ProductReview
    {
        if ((int) $review->customer_id !== (int) $customer->id) {
            throw new CommerceException(ErrorCode::REVIEW_NOT_FOUND, 'Review not found.', 'id', 404);
        }

        if ($review->status === ProductReview::STATUS_APPROVED
            || ! in_array($review->status, [ProductReview::STATUS_PENDING, ProductReview::STATUS_REJECTED], true)
        ) {
            throw new CommerceException(ErrorCode::REVIEW_NOT_EDITABLE, 'This review can no longer be edited.', 'id', 409);
        }

        $this->assertVariantBelongsToProduct(
            array_key_exists('product_variant_id', $payload) ? $payload['product_variant_id'] : $review->product_variant_id,
            (int) $review->product_id,
        );

        $keepCount = $review->images()
            ->when($removeIds !== [], fn ($q) => $q->whereNotIn('id', $removeIds))
            ->count();
        if ($keepCount + count($files) > 3) {
            throw new CommerceException(ErrorCode::VALIDATION_FAILED, 'A review may have at most 3 images.', 'images', 422);
        }

        DB::transaction(function () use ($review, $payload, $files, $removeIds): void {
            if ($removeIds !== []) {
                $toRemove = $review->images()->whereIn('id', $removeIds)->get();
                foreach ($toRemove as $image) {
                    Storage::disk('public')->delete([
                        $image->path,
                        CatalogImagePath::thumbnailPath($image->path),
                    ]);
                    $image->delete();
                }
            }

            if (array_key_exists('rating', $payload)) {
                $review->rating = (int) $payload['rating'];
            }
            if (array_key_exists('body', $payload)) {
                $review->body = $payload['body'];
            }
            if (array_key_exists('product_variant_id', $payload)) {
                $review->product_variant_id = $payload['product_variant_id'];
            }
            $review->status = ProductReview::STATUS_PENDING;
            $review->save();

            $nextSort = (int) $review->images()->max('sort');
            $this->storeImages($review, $files, $nextSort + 1);
        });

        return $this->load($review->refresh());
    }

    public function findOwned(Customer $customer, int $id): ProductReview
    {
        $review = ProductReview::query()
            ->where('customer_id', $customer->id)
            ->whereKey($id)
            ->first();

        if ($review === null) {
            throw new CommerceException(ErrorCode::REVIEW_NOT_FOUND, 'Review not found.', 'id', 404);
        }

        return $review;
    }

    /**
     * @return LengthAwarePaginator<int, ProductReview>
     */
    public function publicPaginator(Product $product, int $perPage): LengthAwarePaginator
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->with(['customer', 'images', 'variant.attributeOptions'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return array{5: int, 4: int, 3: int, 2: int, 1: int}
     */
    public function histogram(int $productId): array
    {
        $counts = ProductReview::query()
            ->where('product_id', $productId)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        return [
            5 => (int) ($counts[5] ?? 0),
            4 => (int) ($counts[4] ?? 0),
            3 => (int) ($counts[3] ?? 0),
            2 => (int) ($counts[2] ?? 0),
            1 => (int) ($counts[1] ?? 0),
        ];
    }

    /**
     * @return array{rating_avg: float|null, rating_count: int, rating_histogram: array{5: int, 4: int, 3: int, 2: int, 1: int}}
     */
    public function publicMeta(int $productId): array
    {
        $histogram = $this->histogram($productId);
        $count = array_sum($histogram);
        $sum = 0;
        foreach ($histogram as $star => $n) {
            $sum += ((int) $star) * $n;
        }

        return [
            'rating_avg' => $count === 0 ? null : round($sum / $count, 1),
            'rating_count' => $count,
            'rating_histogram' => $histogram,
        ];
    }

    public function moderate(ProductReview $review, string $status): ProductReview
    {
        if (! in_array($status, [ProductReview::STATUS_APPROVED, ProductReview::STATUS_REJECTED], true)) {
            throw new CommerceException(ErrorCode::VALIDATION_FAILED, 'Invalid review status.', 'status', 422);
        }

        $review->status = $status;
        $review->save();

        return $this->load($review);
    }

    public function load(ProductReview $review): ProductReview
    {
        return $review->refresh()->load(['images', 'customer', 'variant.attributeOptions', 'product']);
    }

    private function assertVariantBelongsToProduct(mixed $variantId, int $productId): void
    {
        if ($variantId === null || $variantId === '') {
            return;
        }

        $belongs = ProductVariant::query()
            ->whereKey((int) $variantId)
            ->where('product_id', $productId)
            ->exists();

        if (! $belongs) {
            throw new CommerceException(ErrorCode::VALIDATION_FAILED, 'The selected variant does not belong to this product.', 'product_variant_id', 422);
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function storeImages(ProductReview $review, array $files, int $startSort): void
    {
        foreach (array_values($files) as $index => $file) {
            $stored = $this->images->storeUploaded($file, 'reviews');
            ProductReviewImage::query()->create([
                'product_review_id' => $review->id,
                'path' => $stored['path'],
                'sort' => $startSort + $index,
            ]);
        }
    }
}
