<?php

namespace App\Http\Controllers\Api\V1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\PublicBannerResource;
use App\Models\CmsBanner;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class BannerController extends Controller
{
    #[Response(200, 'Live CMS banners grouped by placement.', type: 'array{data: array{promo_bar: list<PublicBannerResource>, homepage_hero: list<PublicBannerResource>}, meta: object}')]
    public function index(): JsonResponse
    {
        $now = now();

        /** @var Collection<int, CmsBanner> $banners */
        $banners = CmsBanner::query()
            ->where('status', CmsBanner::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return ApiResponse::success([
            'promo_bar' => PublicBannerResource::collection(
                $banners->where('placement', CmsBanner::PLACEMENT_PROMO_BAR)->values(),
            )->resolve(),
            'homepage_hero' => PublicBannerResource::collection(
                $banners->where('placement', CmsBanner::PLACEMENT_HOMEPAGE_HERO)->values(),
            )->resolve(),
        ]);
    }
}
