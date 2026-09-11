<?php

namespace App\Http\Requests\Api\V1\Admin\Promotion;

use App\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDiscountRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:26', Rule::unique('discounts', 'code')->ignore($id)],
            'type' => ['sometimes', 'string', Rule::in([Discount::TYPE_FIXED, Discount::TYPE_PERCENTAGE])],
            'value' => ['sometimes', 'numeric'],
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
            /** @var Discount|null $discount */
            $discount = Discount::query()->find($this->route('id'));
            $type = $this->input('type', $discount?->type);
            $value = $this->input('value', $discount?->value);

            if ($type === Discount::TYPE_PERCENTAGE && is_numeric($value)) {
                if ((float) $value <= 0 || (float) $value > 100) {
                    $validator->errors()->add('value', 'Percentage value must be greater than 0 and at most 100.');
                }
            }
            if ($type === Discount::TYPE_FIXED && is_numeric($value) && (float) $value <= 0) {
                $validator->errors()->add('value', 'Fixed value must be greater than 0.');
            }

            if ($this->exists('rule') && $this->input('rule') !== null) {
                $conditions = $this->input('rule.conditions');
                if (is_array($conditions) && array_diff(array_keys($conditions), ['min_subtotal']) !== []) {
                    $validator->errors()->add('rule.conditions', 'Only min_subtotal is supported.');
                }
            }
        });
    }
}
