<?php

namespace App\Http\Controllers\Api\V1\Shipping;

use App\Contracts\ShippingGateway;
use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use App\Models\WebhookEvent;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class GhnWebhookController extends Controller
{
    public function __construct(private readonly ShippingGateway $shipping) {}

    #[HeaderParameter('Token', description: 'GHN webhook token.', type: 'string', required: true)]
    #[Response(200, 'Webhook acknowledged.', type: 'array{data: array{ok: true}, meta: object}')]
    public function store(Request $request): JsonResponse
    {
        $expected = (string) config('commerce.ghn.webhook_token');
        $provided = (string) $request->header('Token', '');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return ApiResponse::error(ErrorCode::AUTH_UNAUTHENTICATED, 'Invalid webhook token.', status: 401);
        }

        $event = $this->shipping->parseWebhook($request->all());
        if ($event->orderCode === '') {
            return ApiResponse::success(['ok' => true]);
        }

        $shipment = OrderShipment::query()->where('tracking_number', $event->orderCode)->first();
        if ($shipment === null) {
            return ApiResponse::success(['ok' => true]);
        }

        $key = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'ghn:'.$event->orderCode.':'.$event->rawStatus);
        $isNew = false;
        try {
            DB::transaction(function () use ($event, $request, $key, &$isNew): void {
                WebhookEvent::query()->create([
                    'provider' => 'ghn',
                    'event_type' => $event->rawStatus !== '' ? $event->rawStatus : 'status',
                    'payload' => $request->all(),
                    'status' => 'received',
                    'idempotency_key' => $key,
                ]);
                $isNew = true;
            });
        } catch (\Throwable) {
            $isNew = false;
        }

        if (! $isNew) {
            return ApiResponse::success(['ok' => true]);
        }

        $mapped = $this->mapStatus($event->rawStatus);
        $payload = array_merge(is_array($shipment->payload) ? $shipment->payload : [], $request->all());
        $updates = ['payload' => $payload];
        if ($mapped !== null) {
            $updates['status'] = $mapped;
        }
        $shipment->update($updates);

        return ApiResponse::success(['ok' => true]);
    }

    private function mapStatus(string $raw): ?string
    {
        return match ($raw) {
            'ready_to_pick', 'picking', 'picked', 'storing' => 'shipped',
            'transporting', 'sorting', 'delivering' => 'in_transit',
            'delivered' => 'delivered',
            'waiting_to_return', 'return' => 'returning',
            'returned' => 'returned',
            'cancel' => 'cancelled',
            default => null,
        };
    }
}
