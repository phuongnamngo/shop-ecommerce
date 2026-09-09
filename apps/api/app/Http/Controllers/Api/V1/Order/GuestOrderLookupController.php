<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ApiResponse;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GuestOrderLookupController extends Controller
{
    #[QueryParameter('token', description: 'Guest lookup token issued once at checkout.', type: 'string')]
    #[Response(200, 'Guest order lookup.', type: 'array{data: array{number: string, status: string, currency: string, subtotal: string, discount_total: string, shipping_total: string, tax_total: string, grand_total: string, items: list<array{name: string, sku: string, qty: int, unit_price: string, line_total: string}>, shipping_address: array{recipient_name: string, phone: string, province_code: string, district_code: string, ward_code: string, address_line: string}|null}, meta: object}')]
    public function show(Request $request): JsonResponse
    {
        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            throw new CommerceException(ErrorCode::ORDER_LOOKUP_INVALID, 'Order was not found.', status: 404);
        }

        $hash = hash('sha256', $token);
        $order = Order::query()
            ->with('items')
            ->where('guest_lookup_token_hash', $hash)
            ->where('guest_lookup_token_expires_at', '>', now())
            ->first();

        if ($order === null || ! hash_equals((string) $order->guest_lookup_token_hash, $hash)) {
            throw new CommerceException(ErrorCode::ORDER_LOOKUP_INVALID, 'Order was not found.', status: 404);
        }

        return ApiResponse::success([
            'number' => $order->number,
            'status' => $order->status,
            'currency' => $order->currency,
            'subtotal' => $order->subtotal,
            'discount_total' => $order->discount_total,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'name' => $item->name,
                'sku' => $item->sku,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'shipping_address' => $order->shipping_address_snapshot,
        ]);
    }
}
