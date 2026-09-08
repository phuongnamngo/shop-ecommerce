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

    #[BodyParameter('tracking_number', description: 'Manual carrier tracking number.', type: 'string', required: true)]
    #[BodyParameter('carrier_code', description: 'Optional carrier code.', type: 'string|null')]
    #[Response(201, 'Created shipment and marked order shipped.', type: 'array{data: array{id: int, tracking_number: string, status: string}, meta: object}')]
    public function store(StoreShipmentRequest $request, int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);
        $shipment = $this->shipments->shipFull(
            $order,
            $request->user('admin'),
            $request->string('tracking_number')->toString(),
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
