<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyLedgerEntry;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    #[Response(200, 'Customer loyalty balance.', type: 'array{data: array{points_balance: int, entries: list<array{delta: int, reason: string, order_id: int, balance_after: int, created_at: string}>}, meta: object}')]
    public function __invoke(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        $account = LoyaltyAccount::query()->where('customer_id', $customer->id)->first();
        if ($account === null) {
            return ApiResponse::success([
                'points_balance' => 0,
                'entries' => [],
            ]);
        }

        $entries = LoyaltyLedgerEntry::query()
            ->where('loyalty_account_id', $account->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (LoyaltyLedgerEntry $entry): array => [
                'delta' => (int) $entry->delta,
                'reason' => (string) $entry->reason,
                'order_id' => (int) $entry->reference_id,
                'balance_after' => (int) $entry->balance_after,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])
            ->all();

        return ApiResponse::success([
            'points_balance' => (int) $account->points_balance,
            'entries' => $entries,
        ]);
    }
}
