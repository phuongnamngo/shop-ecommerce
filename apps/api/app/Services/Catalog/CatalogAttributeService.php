<?php

namespace App\Services\Catalog;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Support\CatalogException;
use App\Support\CatalogSlug;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogAttributeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Attribute
    {
        $slug = CatalogSlug::resolve($data['name'], $data['slug'] ?? null);
        CatalogSlug::assertUnique('attributes', $slug);

        return DB::transaction(function () use ($data, $slug): Attribute {
            $attribute = Attribute::query()->create([
                'code' => (string) Str::ulid(),
                'name' => $data['name'],
                'slug' => $slug,
                'position' => $data['position'] ?? 0,
            ]);

            foreach ($data['options'] ?? [] as $option) {
                $this->createOption($attribute, $option);
            }

            return $attribute->load('options');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Attribute $attribute, array $data): Attribute
    {
        $name = $data['name'] ?? $attribute->name;
        $slug = $attribute->slug;
        if (array_key_exists('slug', $data)) {
            $slug = CatalogSlug::forUpdate('attributes', $attribute->slug, $name, $data['slug'], $attribute->id);
        }

        $attribute->fill([
            'name' => $name,
            'slug' => $slug,
            'position' => $data['position'] ?? $attribute->position,
        ])->save();

        return $attribute->refresh()->load('options');
    }

    public function delete(Attribute $attribute): void
    {
        if ($this->attributeInUse($attribute->id)) {
            throw new CatalogException(
                ErrorCode::CATALOG_RESOURCE_IN_USE,
                'Attribute is attached to variants.',
                status: 422,
            );
        }

        $attribute->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOption(Attribute $attribute, array $data): AttributeOption
    {
        return AttributeOption::query()->create([
            'attribute_id' => $attribute->id,
            'code' => (string) Str::ulid(),
            'label' => $data['label'],
            'position' => $data['position'] ?? 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOption(AttributeOption $option, array $data): AttributeOption
    {
        $option->fill([
            'label' => $data['label'] ?? $option->label,
            'position' => $data['position'] ?? $option->position,
        ])->save();

        return $option->refresh();
    }

    public function deleteOption(AttributeOption $option): void
    {
        if ($this->optionInUse($option->id)) {
            throw new CatalogException(
                ErrorCode::CATALOG_RESOURCE_IN_USE,
                'Attribute option is attached to variants.',
                status: 422,
            );
        }

        $option->delete();
    }

    private function attributeInUse(int $attributeId): bool
    {
        return DB::table('product_variant_attribute_options')
            ->where('attribute_id', $attributeId)
            ->exists();
    }

    private function optionInUse(int $optionId): bool
    {
        return DB::table('product_variant_attribute_options')
            ->where('attribute_option_id', $optionId)
            ->exists();
    }
}
