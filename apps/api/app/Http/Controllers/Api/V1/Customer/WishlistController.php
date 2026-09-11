<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreWishlistItemRequest;
use App\Http\Resources\Engagement\WishlistResource;
use App\Services\Engagement\WishlistService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlists) {}

    #[Response(200, 'Customer default wishlist.', type: 'array{data: WishlistResource, meta: object}')]
    public function show(Request $request): JsonResponse
    {
        $wishlist = $this->wishlists->show($request->user('customer'));

        return ApiResponse::success((new WishlistResource($wishlist))->resolve());
    }

    #[Response(201, 'Wishlist after adding an item.', type: 'array{data: WishlistResource, meta: object}')]
    public function storeItem(StoreWishlistItemRequest $request): JsonResponse
    {
        $wishlist = $this->wishlists->addItem(
            $request->user('customer'),
            (int) $request->validated('product_variant_id'),
        );

        return ApiResponse::success((new WishlistResource($wishlist))->resolve(), status: 201);
    }

    #[Response(200, 'Wishlist item removed.', type: 'array{data: null, meta: object}')]
    public function destroyItem(Request $request, int $id): JsonResponse
    {
        $this->wishlists->removeItem($request->user('customer'), $id);

        return ApiResponse::success(null);
    }
}
