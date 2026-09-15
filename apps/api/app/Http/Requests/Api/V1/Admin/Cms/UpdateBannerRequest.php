<?php

namespace App\Http\Requests\Api\V1\Admin\Cms;

use App\Models\CmsBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placement' => ['sometimes', 'string', Rule::in([
                CmsBanner::PLACEMENT_PROMO_BAR,
                CmsBanner::PLACEMENT_HOMEPAGE_HERO,
            ])],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'link_url' => ['sometimes', 'nullable', 'string', 'max:2048', 'regex:/^(\/|https:\/\/)/'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in([CmsBanner::STATUS_ACTIVE, CmsBanner::STATUS_INACTIVE])],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $banner = CmsBanner::query()->find($this->route('id'));
            if ($banner === null) {
                return;
            }

            $placement = $this->exists('placement') ? $this->input('placement') : $banner->placement;
            $imageUrl = $this->exists('image_url') ? $this->input('image_url') : $banner->image_url;
            if ($placement === CmsBanner::PLACEMENT_HOMEPAGE_HERO && ($imageUrl === null || $imageUrl === '')) {
                $validator->errors()->add('image_url', 'The image url field is required.');
            }

            $starts = $this->exists('starts_at') ? $this->input('starts_at') : $banner->starts_at?->toIso8601String();
            $ends = $this->exists('ends_at') ? $this->input('ends_at') : $banner->ends_at?->toIso8601String();
            if ($starts && $ends && strtotime((string) $ends) < strtotime((string) $starts)) {
                $validator->errors()->add('ends_at', 'The end date must be after or equal to the start date.');
            }
        }];
    }
}
