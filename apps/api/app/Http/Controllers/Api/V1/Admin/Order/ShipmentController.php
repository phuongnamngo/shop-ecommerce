<?php

namespace App\Http\Controllers\Api\V1\Admin\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\StoreShipmentRequest;
use App\Models\Order;
use App\Services\Shipment\ShipmentService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    #[BodyParameter('tracking_number', description: 'Required for standard shipping; ignored for GHN.', type: 'string|null')]
    #[BodyParameter('carrier_code', description: 'Optional carrier code.', type: 'string|null')]
    #[Response(201, 'Created shipment and marked order shipped.', type: 'array{data: array{id: int, tracking_number: string, status: string}, meta: object}')]
    public function store(StoreShipmentRequest $request, int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);
        $tracking = $request->input('tracking_number');
        $shipment = $this->shipments->shipFull(
            $order,
            $request->user('admin'),
            is_string($tracking) ? $tracking : null,
            $request->input('carrier_code'),
        );

        return ApiResponse::success([
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'carrier_code' => $shipment->carrier_code,
            'status' => $shipment->status,
        ], status: 201);
    }
}
