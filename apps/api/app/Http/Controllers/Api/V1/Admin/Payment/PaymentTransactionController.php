<?php

namespace App\Http\Controllers\Api\V1\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentTransactionController extends Controller
{
    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 20)]
    #[QueryParameter('provider', description: 'Filter by payment provider.', type: 'string')]
    #[QueryParameter('status', description: 'Filter by transaction status.', type: 'string')]
    #[QueryParameter('refund_status', description: 'Filter by displayed refund status (pending, failed, succeeded).', type: 'string')]
    #[QueryParameter('q', description: 'Search order number.', type: 'string')]
    #[QueryParameter('from', description: 'Filter transactions created on or after this date (Y-m-d).', type: 'string')]
    #[QueryParameter('to', description: 'Filter transactions created on or before this date (Y-m-d).', type: 'string')]
    #[Response(200, 'Paginated payment transactions with ledger totals.', type: 'array{data: list<PaymentTransactionResource>, meta: object}')]
    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->with(['order', 'refunds'])->latest('id');

        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->whereHas('order', fn ($orders) => $orders->whereRaw('LOWER(number) LIKE ?', ['%'.$escaped.'%']));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to')->toString());
        }

        $refundStatus = $request->string('refund_status')->toString();
        if ($refundStatus === Refund::STATUS_PENDING) {
            $query->whereHas('refunds', fn ($refunds) => $refunds->where('status', Refund::STATUS_PENDING));
        } elseif ($refundStatus === Refund::STATUS_FAILED) {
            $query->whereHas('refunds', fn ($refunds) => $refunds->where('status', Refund::STATUS_FAILED))
                ->whereDoesntHave('refunds', fn ($refunds) => $refunds->where('status', Refund::STATUS_PENDING));
        } elseif ($refundStatus === Refund::STATUS_SUCCEEDED) {
            $query->whereHas('refunds', fn ($refunds) => $refunds->where('status', Refund::STATUS_SUCCEEDED))
                ->whereDoesntHave('refunds', fn ($refunds) => $refunds->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_FAILED]));
        }

        $totals = $this->totals($query->clone());
        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            PaymentTransactionResource::collection($paginator->getCollection())->resolve(),
            array_merge(CatalogPaginator::meta($paginator), $totals),
        );
    }

    #[Response(200, 'Payment transaction detail.', type: 'array{data: PaymentTransactionResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        $txn = PaymentTransaction::query()->with(['order', 'refunds'])->find($id);
        if ($txn === null) {
            throw new CommerceException(ErrorCode::PAYMENT_NOT_FOUND, 'Payment transaction not found.', 'id', 404);
        }

        return ApiResponse::success((new PaymentTransactionResource($txn))->resolve());
    }

    /**
     * @param  Builder<PaymentTransaction>  $query
     * @return array{sum_succeeded: string, sum_refunded: string, count_pending: int, count_failed: int}
     */
    private function totals($query): array
    {
        $ids = $query->clone()->select('payment_transactions.id');

        return [
            'sum_succeeded' => number_format((float) $query->clone()->where('payment_transactions.status', 'succeeded')->sum('amount'), 2, '.', ''),
            'sum_refunded' => number_format((float) Refund::query()->where('status', Refund::STATUS_SUCCEEDED)->whereIn('payment_transaction_id', $ids)->sum('amount'), 2, '.', ''),
            'count_pending' => Refund::query()->where('status', Refund::STATUS_PENDING)->whereIn('payment_transaction_id', $ids)->count(),
            'count_failed' => Refund::query()->where('status', Refund::STATUS_FAILED)->whereIn('payment_transaction_id', $ids)->count(),
        ];
    }
}
