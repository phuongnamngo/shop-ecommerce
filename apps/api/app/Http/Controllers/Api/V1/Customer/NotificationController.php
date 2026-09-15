<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\MarkNotificationReadRequest;
use App\Http\Resources\Notification\CustomerNotificationResource;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 15, example: 15)]
    #[Response(200, 'Paginated customer notifications.', type: 'array{data: list<CustomerNotificationResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int, unread_count: int}}')]
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        $paginator = $customer->notifications()
            ->orderByDesc('created_at')
            ->paginate(CatalogPaginator::perPage($request, 15));

        $meta = CatalogPaginator::meta($paginator);
        $meta['unread_count'] = $customer->unreadNotifications()->count();

        return ApiResponse::success(
            CustomerNotificationResource::collection($paginator->items())->resolve(),
            $meta,
        );
    }

    #[Response(200, 'Notification marked read.', type: 'array{data: CustomerNotificationResource, meta: object}')]
    public function update(MarkNotificationReadRequest $request, string $id): JsonResponse
    {
        $notification = $request->user('customer')->notifications()->whereKey($id)->first();
        if ($notification === null) {
            throw new CommerceException(ErrorCode::NOTIFICATION_NOT_FOUND, 'Notification not found.', 'id', 404);
        }
        $notification->markAsRead();

        return ApiResponse::success((new CustomerNotificationResource($notification->refresh()))->resolve());
    }

    #[Response(200, 'All customer notifications marked read.', type: 'array{data: null, meta: array{unread_count: int}}')]
    public function readAll(Request $request): JsonResponse
    {
        $request->user('customer')->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::success(null, ['unread_count' => 0]);
    }
}
