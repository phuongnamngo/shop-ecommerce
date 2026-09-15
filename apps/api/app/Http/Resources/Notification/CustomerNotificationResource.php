<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
final class CustomerNotificationResource extends JsonResource
{
    /**
     * @return array{id: string, code: string, title: string, order_id: int, read_at: string|null, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id' => (string) $this->id,
            'code' => (string) ($data['code'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'order_id' => (int) ($data['order_id'] ?? 0),
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
