<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;

final class AdminOrderResource extends OrderResource
{
    public function toArray(Request $request): array
    {
        return ['customer_id' => $this->customer_id] + parent::toArray($request);
    }
}
