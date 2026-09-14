<?php

namespace App\Http\Controllers\Api\V1\Shipping;

use App\Contracts\ShippingGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Shipping\ShippingQuoteRequest;
use App\Models\GeoWard;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\Cart\CartService;
use App\Services\Shipping\GhnQuoteRow;
use App\Support\ApiResponse;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use App\Support\ShippingWeight;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ShippingQuoteController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ShippingGateway $shipping,
    ) {}

    #[HeaderParameter('X-Cart-Token', description: 'Opaque guest cart token; omit for authenticated customer quotes.', type: 'string', format: 'uuid')]
    #[Response(200, 'Available shipping quotes for the cart destination.', type: 'array{data: list<array{shipping_method_id: int, code: string, name: string, fee: string, ghn_service_id: int|null, shipping_rate_id: int|null}>, meta: object}')]
    public function store(ShippingQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $customer = $request->user('customer');
        if ($customer === null) {
            $cart = $this->carts->guest($request->header('X-Cart-Token'));
        } else {
            $cart = $this->carts->customer($customer);
        }

        $lines = $cart->items()->with('variant')->get();
        if ($lines->isEmpty()) {
            throw new CommerceException(ErrorCode::CHECKOUT_INVALID_CART, 'Cart is empty.');
        }

        $validGeo = GeoWard::query()
            ->where('code', $data['ward_code'])
            ->whereHas('district', fn ($district) => $district->where('code', $data['district_code'])
                ->whereHas('province', fn ($province) => $province->where('code', $data['province_code'])))
            ->exists();
        if (! $validGeo) {
            throw new CommerceException(ErrorCode::SHIPPING_QUOTE_INVALID_GEO, 'Shipping address geo codes are invalid.');
        }

        $weightGrams = 0;
        foreach ($lines as $line) {
            $weightGrams += ShippingWeight::grams($line->variant?->weight_grams) * (int) $line->qty;
        }
        if ($weightGrams <= 0) {
            $weightGrams = ShippingWeight::grams(null);
        }

        $quotes = [];
        $ghnMethod = ShippingMethod::query()->where('code', 'ghn')->where('status', 'active')->first();
        if ($ghnMethod !== null) {
            foreach ($this->shipping->quote($data['district_code'], $data['ward_code'], $weightGrams) as $row) {
                /** @var GhnQuoteRow $row */
                $quotes[] = [
                    'shipping_method_id' => $ghnMethod->id,
                    'code' => 'ghn',
                    'name' => $row->name,
                    'fee' => number_format((float) $row->fee, 2, '.', ''),
                    'ghn_service_id' => $row->serviceId,
                    'shipping_rate_id' => null,
                ];
            }
        }

        $standard = ShippingMethod::query()->where('code', 'standard')->where('status', 'active')->first();
        if ($standard !== null) {
            $rate = ShippingRate::query()
                ->where('shipping_method_id', $standard->id)
                ->whereNull('region_code')
                ->first();
            if ($rate !== null) {
                $quotes[] = [
                    'shipping_method_id' => $standard->id,
                    'code' => 'standard',
                    'name' => $standard->name,
                    'fee' => number_format((float) $rate->price, 2, '.', ''),
                    'ghn_service_id' => null,
                    'shipping_rate_id' => $rate->id,
                ];
            }
        }

        return ApiResponse::success($quotes);
    }
}
