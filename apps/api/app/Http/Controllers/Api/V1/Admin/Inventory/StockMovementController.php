<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Inventory\StoreStockMovementRequest;
use App\Http\Resources\Inventory\StockItemResource;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Support\ApiResponse;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class StockMovementController extends Controller
{
    #[BodyParameter('qty', description: 'Non-zero signed quantity. Receipt and issue use a positive value; adjustment may be positive or negative.', type: 'positive-int|negative-int', required: true)]
    #[Response(201, 'Updated stock item.', type: 'array{data: StockItemResource, meta: object}')]
    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $stock = DB::transaction(function () use ($data) {
            $stock = StockItem::query()->where('warehouse_id', $data['warehouse_id'])->where('product_variant_id', $data['product_variant_id'])->lockForUpdate()->firstOrFail();
            $delta = $data['type'] === 'issue' ? -$data['qty'] : $data['qty'];
            if ($data['type'] === 'issue' && ($data['qty'] <= 0 || $stock->qty_on_hand - $stock->qty_reserved < $data['qty'])) {
                throw new CommerceException(ErrorCode::INVENTORY_INSUFFICIENT_STOCK, 'Insufficient stock.');
            }
            if ($stock->qty_on_hand + $delta < $stock->qty_reserved) {
                throw new CommerceException(ErrorCode::INVENTORY_INSUFFICIENT_STOCK, 'Insufficient stock.');
            }
            $stock->increment('qty_on_hand', $delta);
            StockMovement::query()->create(['warehouse_id' => $stock->warehouse_id, 'product_variant_id' => $stock->product_variant_id, 'type' => $data['type'], 'qty' => $delta, 'note' => $data['note'] ?? null]);

            return $stock->refresh();
        });

        $stock->loadMissing(['warehouse', 'variant.product']);

        return ApiResponse::success(StockItemResource::make($stock)->resolve(), status: 201);
    }
}
