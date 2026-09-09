<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\StoreCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Services\Cart\CartService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    #[HeaderParameter('X-Cart-Token', description: 'Opaque guest cart token.', type: 'string', format: 'uuid', required: true)]
    #[Response(200, 'Guest cart.', type: 'array{data: CartResource, meta: array{cart_token: string}}')]
    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->present($this->carts->guest($request->header('X-Cart-Token')));

        return ApiResponse::success((new CartResource($cart))->resolve(), ['cart_token' => $cart->session_id]);
    }

    #[Response(201, 'Created guest cart.', type: 'array{data: CartResource, meta: array{cart_token: string}}')]
    public function createGuest(): JsonResponse
    {
        $cart = $this->carts->present($this->carts->createGuest());

        return ApiResponse::success((new CartResource($cart))->resolve(), ['cart_token' => $cart->session_id], 201);
    }

    #[HeaderParameter('X-Cart-Token', description: 'Opaque guest cart token.', type: 'string', format: 'uuid', required: true)]
    #[Response(201, 'Updated guest cart.', type: 'array{data: CartResource, meta: array{cart_token: string}}')]
    public function storeItem(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->guest($request->header('X-Cart-Token'));
        $cart = $this->carts->add($cart, $request->integer('product_variant_id'), $request->integer('qty'));

        return ApiResponse::success((new CartResource($cart))->resolve(), ['cart_token' => $cart->session_id], 201);
    }

    #[Response(200, 'Customer cart.', type: 'array{data: CartResource, meta: object}')]
    public function customerShow(Request $request): JsonResponse
    {
        $cart = $this->carts->present($this->carts->customer($request->user('customer')));

        return ApiResponse::success((new CartResource($cart))->resolve());
    }

    #[Response(201, 'Updated customer cart.', type: 'array{data: CartResource, meta: object}')]
    public function customerStoreItem(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->add($this->carts->customer($request->user('customer')), $request->integer('product_variant_id'), $request->integer('qty'));

        return ApiResponse::success((new CartResource($cart))->resolve(), status: 201);
    }

    #[Response(200, 'Updated customer cart.', type: 'array{data: CartResource, meta: object}')]
    public function customerUpdateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->update($this->carts->customer($request->user('customer')), $itemId, $request->integer('qty'));

        return ApiResponse::success((new CartResource($cart))->resolve());
    }

    #[Response(200, 'Removed customer cart item.', type: 'array{data: null, meta: object}')]
    public function customerDestroyItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->customer($request->user('customer'));
        $item = $cart->items()->find($itemId);
        if ($item === null) {
            abort(404);
        }
        $item->delete();

        return ApiResponse::success(null);
    }

    #[Response(200, 'Merged customer cart.', type: 'array{data: CartResource, meta: object}')]
    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate(['guest_token' => ['required', 'uuid']]);
        $cart = $this->carts->merge($request->user('customer'), $data['guest_token']);

        return ApiResponse::success((new CartResource($cart))->resolve());
    }

    #[HeaderParameter('X-Cart-Token', description: 'Opaque guest cart token.', type: 'string', format: 'uuid', required: true)]
    #[Response(200, 'Updated guest cart.', type: 'array{data: CartResource, meta: object}')]
    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->guest($request->header('X-Cart-Token'));
        $cart = $this->carts->update($cart, $itemId, $request->integer('qty'));

        return ApiResponse::success((new CartResource($cart))->resolve());
    }

    #[Response(200, 'Removed guest cart item.', type: 'array{data: null, meta: object}')]
    #[HeaderParameter('X-Cart-Token', description: 'Opaque guest cart token.', type: 'string', format: 'uuid', required: true)]
    public function destroyItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->guest($request->header('X-Cart-Token'));
        $cart->items()->findOrFail($itemId)->delete();

        return ApiResponse::success(null);
    }
}
