<?php

namespace App\Http\Resources\Payment;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Refund */
final class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_transaction_id' => $this->payment_transaction_id,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'status' => $this->status,
            'reason' => $this->reason,
            'provider_refund_id' => $this->provider_refund_id,
            'idempotency_key' => $this->idempotency_key,
            'requested_by_admin_id' => $this->requested_by_admin_id,
            'reviewed_by_admin_id' => $this->reviewed_by_admin_id,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
