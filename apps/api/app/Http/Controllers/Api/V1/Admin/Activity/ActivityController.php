<?php

namespace App\Http\Controllers\Api\V1\Admin\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Activity\IndexActivityRequest;
use App\Http\Resources\Activity\ActivityResource;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[QueryParameter('log_name', description: 'Filter by log name: order, product, product_variant, stock_movement.', type: 'string')]
    #[Response(200, 'Paginated activity.', type: 'array{data: list<ActivityResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(IndexActivityRequest $request): JsonResponse
    {
        $logName = $request->validated('log_name');

        $query = Activity::query()
            ->with('causer')
            ->when(is_string($logName) && $logName !== '', fn ($q) => $q->where('log_name', $logName))
            ->orderByDesc('id');

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            ActivityResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }
}
