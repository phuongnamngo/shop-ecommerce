<?php

namespace App\Http\Controllers\Api\V1\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Payment\StoreOrderRefundRequest;
use App\Http\Resources\Payment\RefundResource;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Payment\RefundService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    #[BodyParameter('reason', description: 'Staff reason for the full refund.', type: 'string', required: true)]
    #[Response(201, 'Created pending refund.', type: 'array{data: RefundResource, meta: object}')]
    public function store(StoreOrderRefundRequest $request, int $id): JsonResponse
    {
        $refund = $this->refunds->create(
            Order::query()->findOrFail($id),
            $request->user('admin'),
            $request->string('reason')->toString(),
        );

        return ApiResponse::success((new RefundResource($refund))->resolve(), status: 201);
    }

    #[Response(200, 'Approved refund.', type: 'array{data: RefundResource, meta: object}')]
    public function approve(Request $request, int $id): JsonResponse
    {
        $refund = $this->refunds->approve(Refund::query()->findOrFail($id), $this->admin($request));

        return ApiResponse::success((new RefundResource($refund))->resolve());
    }

    #[Response(200, 'Rejected refund.', type: 'array{data: RefundResource, meta: object}')]
    public function reject(Request $request, int $id): JsonResponse
    {
        $refund = $this->refunds->reject(Refund::query()->findOrFail($id), $this->admin($request));

        return ApiResponse::success((new RefundResource($refund))->resolve());
    }

    #[Response(200, 'Retried refund.', type: 'array{data: RefundResource, meta: object}')]
    public function retry(Request $request, int $id): JsonResponse
    {
        $refund = $this->refunds->retry(Refund::query()->findOrFail($id), $this->admin($request));

        return ApiResponse::success((new RefundResource($refund))->resolve());
    }

    private function admin(Request $request): AdminUser
    {
        return $request->user('admin');
    }
}
