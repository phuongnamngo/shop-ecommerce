<?php

namespace App\Http\Controllers\Api\V1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\PublicPageResource;
use App\Models\CmsPage;
use App\Support\ApiResponse;
use App\Support\CmsNotFound;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    #[Response(200, 'Published CMS pages.', type: 'array{data: list<array{slug: string, title: string}>, meta: object}')]
    public function index(): JsonResponse
    {
        $pages = CmsPage::query()
            ->where('status', CmsPage::STATUS_PUBLISHED)
            ->orderBy('id')
            ->get(['slug', 'title']);

        return ApiResponse::success(
            $pages->map(fn (CmsPage $page) => [
                'slug' => $page->slug,
                'title' => $page->title,
            ])->values()->all(),
        );
    }

    #[Response(200, 'Published CMS page.', type: 'array{data: PublicPageResource, meta: object}')]
    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::query()
            ->where('status', CmsPage::STATUS_PUBLISHED)
            ->where('slug', $slug)
            ->first();

        if ($page === null) {
            return CmsNotFound::page();
        }

        return ApiResponse::success(PublicPageResource::make($page)->resolve());
    }
}
