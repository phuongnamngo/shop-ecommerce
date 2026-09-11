<?php

namespace App\Http\Requests\Api\V1\Admin\Engagement;

use App\Models\ProductReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminReviewRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in([ProductReview::STATUS_APPROVED, ProductReview::STATUS_REJECTED])],
        ];
    }
}
