<?php

namespace App\Http\Controllers\Api\V1\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Promotion\StoreDiscountRequest;
use App\Http\Requests\Api\V1\Admin\Promotion\UpdateDiscountRequest;
use App\Http\Resources\Promotion\AdminDiscountResource;
use App\Models\Discount;
use App\Services\Promotion\PromotionDiscountService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DiscountController extends Controller
{
    public function __construct(private readonly PromotionDiscountService $discounts) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20, example: 20)]
    #[QueryParameter('q', description: 'Search name or code.', type: 'string', example: 'Summer')]
    #[QueryParameter('status', description: 'Filter by status.', type: 'string', example: 'active')]
    #[QueryParameter('type', description: 'Filter by type.', type: 'string', example: 'percentage')]
    #[Response(200, 'Paginated discounts.', type: 'array{data: list<AdminDiscountResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = Discount::query()->with('rules')->latest('id');

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->where(function ($q) use ($escaped): void {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.$escaped.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$escaped.'%']);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('type')) {
            $query->where('type', (string) $request->string('type'));
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminDiscountResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(201, 'Created discount.', type: 'array{data: AdminDiscountResource, meta: object}')]
    public function store(StoreDiscountRequest $request): JsonResponse
    {
        $discount = $this->discounts->create($request->validated());

        return ApiResponse::success(AdminDiscountResource::make($discount)->resolve(), status: 201);
    }

    #[Response(200, 'Discount detail.', type: 'array{data: AdminDiscountResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $discount = Discount::query()->with('rules')->find($id);
        if ($discount === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Discount not found.', status: 404);
        }

        return ApiResponse::success(AdminDiscountResource::make($discount)->resolve());
    }

    #[Response(200, 'Updated discount.', type: 'array{data: AdminDiscountResource, meta: object}')]
    public function update(UpdateDiscountRequest $request, int $id): JsonResponse
    {
        $discount = Discount::query()->find($id);
        if ($discount === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Discount not found.', status: 404);
        }

        $discount = $this->discounts->update($discount, $request->validated());

        return ApiResponse::success(AdminDiscountResource::make($discount)->resolve());
    }

    #[Response(200, 'Deleted discount.', type: 'array{data: null, meta: object}')]
    public function destroy(int $id): JsonResponse
    {
        $discount = Discount::query()->find($id);
        if ($discount === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Discount not found.', status: 404);
        }

        $this->discounts->delete($discount);

        return ApiResponse::success(null);
    }
}
