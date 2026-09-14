<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Services\Order\OrderService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RefundService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OrderService $orders,
    ) {}

    public function create(Order $order, AdminUser $admin, string $reason): Refund
    {
        return DB::transaction(function () use ($order, $admin, $reason): Refund {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $txn = PaymentTransaction::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();
            $this->assertCanCreate($order, $txn);

            return Refund::query()->create([
                'payment_transaction_id' => $txn->id,
                'amount' => $txn->amount,
                'status' => Refund::STATUS_PENDING,
                'reason' => $reason,
                'requested_by_admin_id' => $admin->id,
                'idempotency_key' => (string) Str::uuid(),
            ]);
        });
    }

    public function approve(Refund $refund, AdminUser $admin): Refund
    {
        $gatewayFailed = false;
        $updated = DB::transaction(function () use ($refund, $admin, &$gatewayFailed): Refund {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== Refund::STATUS_PENDING) {
                throw new CommerceException(ErrorCode::REFUND_INVALID_STATUS, 'Refund cannot be approved in this status.', 'status', 409);
            }

            return $this->executeRefund($refund, $admin, $gatewayFailed);
        });

        if ($gatewayFailed) {
            throw new CommerceException(ErrorCode::REFUND_GATEWAY_FAILED, 'Payment gateway refund failed.', status: 422);
        }

        return $updated;
    }

    public function retry(Refund $refund, AdminUser $admin): Refund
    {
        $gatewayFailed = false;
        $updated = DB::transaction(function () use ($refund, $admin, &$gatewayFailed): Refund {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== Refund::STATUS_FAILED) {
                throw new CommerceException(ErrorCode::REFUND_INVALID_STATUS, 'Only failed refunds can be retried.', 'status', 409);
            }

            return $this->executeRefund($refund, $admin, $gatewayFailed);
        });

        if ($gatewayFailed) {
            throw new CommerceException(ErrorCode::REFUND_GATEWAY_FAILED, 'Payment gateway refund failed.', status: 422);
        }

        return $updated;
    }

    public function reject(Refund $refund, AdminUser $admin): Refund
    {
        return DB::transaction(function () use ($refund, $admin): Refund {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if (! in_array($refund->status, [Refund::STATUS_PENDING, Refund::STATUS_FAILED], true)) {
                throw new CommerceException(ErrorCode::REFUND_INVALID_STATUS, 'Refund cannot be rejected in this status.', 'status', 409);
            }

            $refund->update([
                'status' => Refund::STATUS_REJECTED,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $refund->refresh();
        });
    }

    private function executeRefund(Refund $refund, AdminUser $admin, bool &$gatewayFailed): Refund
    {
        $txn = PaymentTransaction::query()->whereKey($refund->payment_transaction_id)->lockForUpdate()->firstOrFail();
        $order = Order::query()->whereKey($txn->order_id)->lockForUpdate()->firstOrFail();
        $this->assertStillRefundable($order, $txn);

        $needsGateway = $txn->provider === 'vnpay' && filled($txn->provider_txn_id);
        if ($needsGateway) {
            $result = $this->gateway->refund($txn, $refund);
            if (! $result->ok) {
                $refund->update([
                    'status' => Refund::STATUS_FAILED,
                    'payload' => $result->payload,
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => now(),
                ]);
                $gatewayFailed = true;

                return $refund->refresh();
            }

            $refund->update([
                'status' => Refund::STATUS_SUCCEEDED,
                'provider_refund_id' => $result->providerRefundId,
                'payload' => $result->payload,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]);
        } else {
            $refund->update([
                'status' => Refund::STATUS_SUCCEEDED,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]);
        }

        $this->orders->cancelAfterRefund($order, $admin, 'refund:'.$refund->id);

        return $refund->refresh();
    }

    private function assertCanCreate(Order $order, ?PaymentTransaction $txn): void
    {
        if ($txn !== null) {
            $existing = Refund::query()
                ->where('payment_transaction_id', $txn->id)
                ->lockForUpdate()
                ->get();
            if ($existing->contains(fn (Refund $refund) => in_array($refund->status, [Refund::STATUS_PENDING, Refund::STATUS_FAILED], true))) {
                throw new CommerceException(ErrorCode::REFUND_ALREADY_OPEN, 'An open refund already exists for this payment.', status: 409);
            }
            if ($existing->contains(fn (Refund $refund) => $refund->status === Refund::STATUS_SUCCEEDED)) {
                throw new CommerceException(ErrorCode::REFUND_ALREADY_SUCCEEDED, 'This payment has already been refunded.', status: 409);
            }
        }

        $this->assertEligible($order, $txn);
    }

    private function assertStillRefundable(Order $order, PaymentTransaction $txn): void
    {
        $this->assertEligible($order, $txn);
    }

    private function assertEligible(Order $order, ?PaymentTransaction $txn): void
    {
        if (! in_array($order->status, ['paid', 'fulfilling'], true)
            || $order->shipments()->withTrashed()->exists()
            || $txn === null
            || $txn->status !== 'succeeded'
            || ! in_array($txn->provider, ['cod', 'vnpay'], true)
            || number_format((float) $txn->amount, 2, '.', '') !== number_format((float) $order->grand_total, 2, '.', '')
        ) {
            throw new CommerceException(ErrorCode::REFUND_NOT_ELIGIBLE, 'Order is not eligible for refund.', status: 422);
        }
    }
}
