<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Cms\StoreBannerRequest;
use App\Http\Requests\Api\V1\Admin\Cms\UpdateBannerRequest;
use App\Http\Resources\Cms\AdminBannerResource;
use App\Models\CmsBanner;
use App\Services\Cms\CmsBannerService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CmsNotFound;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(private readonly CmsBannerService $banners) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[QueryParameter('placement', description: 'Filter by placement.', type: 'string')]
    #[Response(200, 'Paginated CMS banners.', type: 'array{data: list<AdminBannerResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = CmsBanner::query()->latest('id');

        if ($request->filled('placement')) {
            $query->where('placement', (string) $request->string('placement'));
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminBannerResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(201, 'Created CMS banner.', type: 'array{data: AdminBannerResource, meta: object}')]
    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = $this->banners->create($request->validated());

        return ApiResponse::success(AdminBannerResource::make($banner)->resolve(), status: 201);
    }

    #[Response(200, 'CMS banner.', type: 'array{data: AdminBannerResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $banner = CmsBanner::query()->find($id);
        if ($banner === null) {
            return CmsNotFound::banner();
        }

        return ApiResponse::success(AdminBannerResource::make($banner)->resolve());
    }

    #[Response(200, 'Updated CMS banner.', type: 'array{data: AdminBannerResource, meta: object}')]
    public function update(UpdateBannerRequest $request, int $id): JsonResponse
    {
        $banner = CmsBanner::query()->find($id);
        if ($banner === null) {
            return CmsNotFound::banner();
        }

        $banner = $this->banners->update($banner, $request->validated());

        return ApiResponse::success(AdminBannerResource::make($banner)->resolve());
    }

    #[Response(200, 'Deleted CMS banner.', type: 'array{data: null, meta: object}')]
    public function destroy(int $id): JsonResponse
    {
        $banner = CmsBanner::query()->find($id);
        if ($banner === null) {
            return CmsNotFound::banner();
        }

        $this->banners->delete($banner);

        return ApiResponse::success(null);
    }
}
