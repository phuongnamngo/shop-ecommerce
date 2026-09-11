<?php

namespace App\Http\Controllers\Api\V1\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Promotion\StoreCouponRequest;
use App\Http\Requests\Api\V1\Admin\Promotion\UpdateCouponRequest;
use App\Http\Resources\Promotion\AdminCouponResource;
use App\Models\Coupon;
use App\Services\Promotion\PromotionCouponService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CouponController extends Controller
{
    public function __construct(private readonly PromotionCouponService $coupons) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20, example: 20)]
    #[QueryParameter('q', description: 'Search coupon code.', type: 'string', example: 'SAVE')]
    #[QueryParameter('status', description: 'Filter by status.', type: 'string', example: 'active')]
    #[QueryParameter('discount_id', description: 'Filter by discount id.', type: 'int', example: 1)]
    #[Response(200, 'Paginated coupons.', type: 'array{data: list<AdminCouponResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query()->with('discount')->latest('id');

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereRaw('LOWER(code) LIKE ?', ['%'.$escaped.'%']);
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('discount_id')) {
            $query->where('discount_id', $request->integer('discount_id'));
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminCouponResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(201, 'Created coupon.', type: 'array{data: AdminCouponResource, meta: object}')]
    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->coupons->create($request->validated());

        return ApiResponse::success(AdminCouponResource::make($coupon)->resolve(), status: 201);
    }

    #[Response(200, 'Coupon detail.', type: 'array{data: AdminCouponResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::query()->with('discount')->find($id);
        if ($coupon === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Coupon not found.', status: 404);
        }

        return ApiResponse::success(AdminCouponResource::make($coupon)->resolve());
    }

    #[Response(200, 'Updated coupon.', type: 'array{data: AdminCouponResource, meta: object}')]
    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = Coupon::query()->find($id);
        if ($coupon === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Coupon not found.', status: 404);
        }

        $coupon = $this->coupons->update($coupon, $request->validated());

        return ApiResponse::success(AdminCouponResource::make($coupon)->resolve());
    }

    #[Response(200, 'Deleted coupon.', type: 'array{data: null, meta: object}')]
    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::query()->find($id);
        if ($coupon === null) {
            throw new CommerceException(ErrorCode::PROMOTION_NOT_FOUND, 'Coupon not found.', status: 404);
        }

        $this->coupons->delete($coupon);

        return ApiResponse::success(null);
    }
}
