<?php

use App\Models\Customer;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyLedgerEntry;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;

it('rejects a second ledger row for the same order and reason', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'grand_total' => 2500]);
    $account = LoyaltyAccount::query()->create(['customer_id' => $customer->id, 'points_balance' => 2]);
    $row = [
        'loyalty_account_id' => $account->id,
        'delta' => 2,
        'reason' => 'order.paid',
        'reference_type' => Order::class,
        'reference_id' => $order->id,
        'balance_after' => 2,
    ];
    LoyaltyLedgerEntry::query()->create($row);

    expect(fn () => LoyaltyLedgerEntry::query()->create($row))
        ->toThrow(UniqueConstraintViolationException::class);
});
