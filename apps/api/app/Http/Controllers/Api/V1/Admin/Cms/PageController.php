<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Cms\StorePageRequest;
use App\Http\Requests\Api\V1\Admin\Cms\UpdatePageRequest;
use App\Http\Resources\Cms\AdminPageResource;
use App\Models\CmsPage;
use App\Services\Cms\CmsPageService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CmsError;
use App\Support\CmsException;
use App\Support\CmsNotFound;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(private readonly CmsPageService $pages) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[QueryParameter('status', description: 'Filter by status.', type: 'string')]
    #[Response(200, 'Paginated CMS pages.', type: 'array{data: list<AdminPageResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = CmsPage::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminPageResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    #[Response(201, 'Created CMS page.', type: 'array{data: AdminPageResource, meta: object}')]
    public function store(StorePageRequest $request): JsonResponse
    {
        try {
            $page = $this->pages->create($request->validated());
        } catch (CmsException $e) {
            return CmsError::from($e);
        }

        return ApiResponse::success(AdminPageResource::make($page)->resolve(), status: 201);
    }

    #[Response(200, 'CMS page.', type: 'array{data: AdminPageResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $page = CmsPage::query()->find($id);
        if ($page === null) {
            return CmsNotFound::page();
        }

        return ApiResponse::success(AdminPageResource::make($page)->resolve());
    }

    #[Response(200, 'Updated CMS page.', type: 'array{data: AdminPageResource, meta: object}')]
    public function update(UpdatePageRequest $request, int $id): JsonResponse
    {
        $page = CmsPage::query()->find($id);
        if ($page === null) {
            return CmsNotFound::page();
        }

        try {
            $page = $this->pages->update($page, $request->validated());
        } catch (CmsException $e) {
            return CmsError::from($e);
        }

        return ApiResponse::success(AdminPageResource::make($page)->resolve());
    }

    #[Response(200, 'Deleted CMS page.', type: 'array{data: null, meta: object}')]
    public function destroy(int $id): JsonResponse
    {
        $page = CmsPage::query()->find($id);
        if ($page === null) {
            return CmsNotFound::page();
        }

        $this->pages->delete($page);

        return ApiResponse::success(null);
    }
}
