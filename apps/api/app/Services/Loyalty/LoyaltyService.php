<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyLedgerEntry;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;

final class LoyaltyService
{
    public function creditForPaidOrder(Order $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $points = $this->pointsFor($order);
        if ($points < 1) {
            return;
        }

        $account = LoyaltyAccount::query()->firstOrCreate(
            ['customer_id' => $order->customer_id],
            ['points_balance' => 0],
        );
        $account = LoyaltyAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

        $exists = LoyaltyLedgerEntry::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('reason', 'order.paid')
            ->exists();
        if ($exists) {
            return;
        }

        $balance = (int) $account->points_balance;
        try {
            $account->ledgerEntries()->create([
                'delta' => $points,
                'reason' => 'order.paid',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'balance_after' => $balance + $points,
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $account->update(['points_balance' => $balance + $points]);
    }

    public function reverseForCancelledOrder(Order $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $paid = LoyaltyLedgerEntry::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('reason', 'order.paid')
            ->first();
        if ($paid === null) {
            return;
        }

        $account = LoyaltyAccount::query()
            ->where('customer_id', $order->customer_id)
            ->lockForUpdate()
            ->first();
        if ($account === null) {
            return;
        }

        $alreadyReversed = LoyaltyLedgerEntry::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('reason', 'order.cancelled')
            ->exists();
        if ($alreadyReversed) {
            return;
        }

        $earned = (int) $paid->delta;
        $balance = (int) $account->points_balance;
        if ($balance < $earned) {
            return;
        }

        try {
            $account->ledgerEntries()->create([
                'delta' => -$earned,
                'reason' => 'order.cancelled',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'balance_after' => $balance - $earned,
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $account->update(['points_balance' => $balance - $earned]);
    }

    private function pointsFor(Order $order): int
    {
        return intdiv((int) floor((float) $order->grand_total), 1000);
    }
}
