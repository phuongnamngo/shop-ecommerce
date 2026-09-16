<?php

namespace App\Http\Resources\Activity;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     log_name: string|null,
     *     event: string|null,
     *     description: string,
     *     subject_type: string|null,
     *     subject_id: int|null,
     *     causer_id: int|null,
     *     causer_name: string|null,
     *     changes: array<string, array{old: mixed, new: mixed}>,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $causer = $this->causer;

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id !== null ? (int) $this->subject_id : null,
            'causer_id' => $this->causer_id !== null ? (int) $this->causer_id : null,
            'causer_name' => $causer instanceof AdminUser ? $causer->name : null,
            'changes' => $this->changes(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changes(): array
    {
        $properties = $this->properties;
        $bag = $properties === null ? [] : $properties->toArray();
        $attributes = is_array($bag['attributes'] ?? null) ? $bag['attributes'] : [];
        $old = is_array($bag['old'] ?? null) ? $bag['old'] : [];
        $keys = array_unique([...array_keys($attributes), ...array_keys($old)]);

        $changes = [];
        foreach ($keys as $key) {
            $changes[$key] = [
                'old' => $old[$key] ?? null,
                'new' => $attributes[$key] ?? null,
            ];
        }

        return $changes;
    }
}
