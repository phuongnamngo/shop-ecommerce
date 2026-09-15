<?php

namespace App\Http\Requests\Api\V1\Admin\Cms;

use App\Models\CmsBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBannerRequest extends FormRequest
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
            'placement' => ['required', 'string', Rule::in([
                CmsBanner::PLACEMENT_PROMO_BAR,
                CmsBanner::PLACEMENT_HOMEPAGE_HERO,
            ])],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image_url' => [
                Rule::requiredIf(fn () => $this->input('placement') === CmsBanner::PLACEMENT_HOMEPAGE_HERO),
                'nullable',
                'string',
                'max:2048',
            ],
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

            $starts = $this->input('starts_at');
            $ends = $this->input('ends_at');
            if ($starts && $ends && strtotime((string) $ends) < strtotime((string) $starts)) {
                $validator->errors()->add('ends_at', 'The end date must be after or equal to the start date.');
            }
        }];
    }
}
