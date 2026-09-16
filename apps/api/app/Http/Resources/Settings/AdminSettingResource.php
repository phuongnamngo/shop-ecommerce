<?php

namespace App\Http\Resources\Settings;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Setting
 */
class AdminSettingResource extends JsonResource
{
    /**
     * @return array{key: string, group: mixed, value: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'value' => $this->value,
        ];
    }
}
