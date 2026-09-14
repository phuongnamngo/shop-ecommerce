<?php

namespace App\Http\Resources\Payment;

use App\Models\PaymentTransaction;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentTransaction */
final class PaymentTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order?->number),
            'provider' => $this->provider,
            'provider_txn_id' => $this->provider_txn_id,
            'idempotency_key' => $this->idempotency_key,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'status' => $this->status,
            'refund' => $this->whenLoaded('refunds', fn () => $this->displayedRefund()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function displayedRefund(): ?array
    {
        $refunds = $this->refunds;
        $refund = $refunds->firstWhere('status', Refund::STATUS_PENDING)
            ?? $refunds->firstWhere('status', Refund::STATUS_FAILED)
            ?? $refunds->firstWhere('status', Refund::STATUS_SUCCEEDED);

        if ($refund === null) {
            return null;
        }

        return (new RefundResource($refund))->resolve();
    }
}
