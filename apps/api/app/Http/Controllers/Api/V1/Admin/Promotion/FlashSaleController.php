<?php

namespace App\Http\Controllers\Api\V1\Admin\Promotion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Promotion\StoreFlashSaleRequest;
use App\Http\Requests\Api\V1\Admin\Promotion\UpdateFlashSaleRequest;
use App\Http\Resources\Promotion\AdminFlashSaleResource;
use App\Models\FlashSale;
use App\Services\Promotion\PromotionFlashSaleService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FlashSaleController extends Controller
{
    public function __construct(private readonly PromotionFlashSaleService $flashSales) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20, example: 20)]
    #[QueryParameter('q', description: 'Search name or code.', type: 'string', example: 'Noon')]
    #[QueryParameter('status', description: 'Filter by status.', type: 'string', example: 'active')]
    #[Response(200, 'Paginated flash sales.', type: 'array{data: list<AdminFlashSaleResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = FlashSale::query()->with('items')->latest('id');

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

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminFlashSaleResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(201, 'Created flash sale.', type: 'array{data: AdminFlashSaleResource, meta: object}')]
    public function store(StoreFlashSaleRequest $request): JsonResponse
    {
        $sale = $this->flashSales->create($request->validated());

        return ApiResponse::success(AdminFlashSaleResource::make($sale)->resolve(), status: 201);
    }

    #[Response(200, 'Flash sale detail.', type: 'array{data: AdminFlashSaleResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $sale = FlashSale::query()->with('items')->find($id);
        if ($sale === null) {
            throw new CommerceException(ErrorCode::FLASH_SALE_NOT_FOUND, 'Flash sale not found.', status: 404);
        }

        return ApiResponse::success(AdminFlashSaleResource::make($sale)->resolve());
    }

    #[Response(200, 'Updated flash sale.', type: 'array{data: AdminFlashSaleResource, meta: object}')]
    public function update(UpdateFlashSaleRequest $request, int $id): JsonResponse
    {
        $sale = FlashSale::query()->find($id);
        if ($sale === null) {
            throw new CommerceException(ErrorCode::FLASH_SALE_NOT_FOUND, 'Flash sale not found.', status: 404);
        }

        $sale = $this->flashSales->update($sale, $request->validated());

        return ApiResponse::success(AdminFlashSaleResource::make($sale)->resolve());
    }

    #[Response(200, 'Deleted flash sale.', type: 'array{data: null, meta: object}')]
    public function destroy(int $id): JsonResponse
    {
        $sale = FlashSale::query()->find($id);
        if ($sale === null) {
            throw new CommerceException(ErrorCode::FLASH_SALE_NOT_FOUND, 'Flash sale not found.', status: 404);
        }

        $this->flashSales->delete($sale);

        return ApiResponse::success(null);
    }
}
