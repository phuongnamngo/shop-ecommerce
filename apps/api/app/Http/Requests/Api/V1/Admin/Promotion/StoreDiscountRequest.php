<?php

namespace App\Http\Requests\Api\V1\Admin\Promotion;

use App\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDiscountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:26', 'unique:discounts,code'],
            'type' => ['required', 'string', Rule::in([Discount::TYPE_FIXED, Discount::TYPE_PERCENTAGE])],
            'value' => ['required', 'numeric'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'string', Rule::in([Discount::STATUS_ACTIVE, Discount::STATUS_INACTIVE])],
            'rule' => ['sometimes', 'nullable', 'array'],
            'rule.conditions' => ['required_with:rule', 'array'],
            'rule.conditions.min_subtotal' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $value = $this->input('value');
            if ($type === Discount::TYPE_PERCENTAGE && is_numeric($value)) {
                if ((float) $value <= 0 || (float) $value > 100) {
                    $validator->errors()->add('value', 'Percentage value must be greater than 0 and at most 100.');
                }
            }
            if ($type === Discount::TYPE_FIXED && is_numeric($value) && (float) $value <= 0) {
                $validator->errors()->add('value', 'Fixed value must be greater than 0.');
            }

            $conditions = $this->input('rule.conditions');
            if (is_array($conditions) && array_diff(array_keys($conditions), ['min_subtotal']) !== []) {
                $validator->errors()->add('rule.conditions', 'Only min_subtotal is supported.');
            }
        });
    }
}
