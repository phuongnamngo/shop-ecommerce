<?php

namespace App\Http\Requests\Api\V1\Admin\Catalog;

use App\Models\AttributeOption;
use Illuminate\Validation\Validator;

final class CatalogAttributeOptionRules
{
    /**
     * @param  array<int, mixed>  $variants
     */
    public static function assertUniqueAttributes(Validator $validator, array $variants): void
    {
        foreach ($variants as $index => $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $ids = $variant['attribute_option_ids'] ?? [];
            if (! is_array($ids) || $ids === []) {
                continue;
            }

            $attributeIds = AttributeOption::query()
                ->whereIn('id', $ids)
                ->pluck('attribute_id', 'id');

            $seen = [];
            foreach ($ids as $optionId) {
                $attributeId = $attributeIds[$optionId] ?? null;
                if ($attributeId === null) {
                    continue;
                }
                if (isset($seen[$attributeId])) {
                    $validator->errors()->add(
                        "variants.{$index}.attribute_option_ids",
                        'A variant cannot have two options of the same attribute.',
                    );
                    break;
                }
                $seen[$attributeId] = true;
            }
        }
    }
}
