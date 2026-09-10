<?php

namespace App\Http\Requests\Api\V1\Admin\Catalog;

use App\Models\ProductImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class StoreCatalogImageRequest extends FormRequest
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
            'path' => ['required', 'string', 'max:255'],
            'alt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $path = $this->input('path');
            if (! is_string($path) || $path === '') {
                return;
            }

            if (str_contains($path, '..')) {
                $validator->errors()->add('path', 'Image path must be under catalog/.');

                return;
            }

            $onProductGallery = $this->pathOnProductGallery($path);
            if (! $onProductGallery && ! str_starts_with($path, 'catalog/')) {
                $validator->errors()->add('path', 'Image path must be under catalog/.');

                return;
            }

            if (! Storage::disk('public')->exists($path)) {
                $validator->errors()->add('path', 'Image path does not exist.');
            }
        });
    }

    private function pathOnProductGallery(string $path): bool
    {
        $productId = $this->route('id');
        if (! is_numeric($productId)) {
            return false;
        }

        return ProductImage::query()
            ->where('product_id', (int) $productId)
            ->where('path', $path)
            ->exists();
    }
}
