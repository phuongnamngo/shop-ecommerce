<?php

namespace App\Http\Resources\Inventory;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Warehouse */
final class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'is_default' => $this->is_default, 'status' => $this->status];
    }
}
