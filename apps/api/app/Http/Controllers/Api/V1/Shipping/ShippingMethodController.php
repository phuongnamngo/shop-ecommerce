<?php

namespace App\Http\Controllers\Api\V1\Shipping;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ShippingMethodController extends Controller
{
    #[Response(200, 'Active shipping methods with nationwide rates.', type: 'array{data: list<array{id: int, code: string, name: string, rates: list<array{id: int, price: string, min_order_amount: string|null, max_order_amount: string|null}>}>, meta: object}')]
    public function index(): JsonResponse
    {
        $methods = ShippingMethod::query()
            ->where('status', 'active')
            ->with(['rates' => fn ($query) => $query->whereNull('region_code')])
            ->orderBy('id')
            ->get();

        $data = $methods->map(fn (ShippingMethod $method) => [
            'id' => $method->id,
            'code' => $method->code,
            'name' => $method->name,
            'rates' => $method->rates->map(fn ($rate) => [
                'id' => $rate->id,
                'price' => number_format((float) $rate->price, 2, '.', ''),
                'min_order_amount' => $rate->min_order_amount === null ? null : number_format((float) $rate->min_order_amount, 2, '.', ''),
                'max_order_amount' => $rate->max_order_amount === null ? null : number_format((float) $rate->max_order_amount, 2, '.', ''),
            ])->values()->all(),
        ])->all();

        return ApiResponse::success($data);
    }
}
