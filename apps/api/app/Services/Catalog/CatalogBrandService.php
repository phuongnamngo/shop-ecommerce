<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Support\CatalogException;
use App\Support\CatalogSlug;
use App\Support\ErrorCode;
use Illuminate\Support\Str;

final class CatalogBrandService
{
    /**
     * @param  array{name: string, slug?: string|null, status?: string|null}  $data
     */
    public function create(array $data, int $adminId): Brand
    {
        $slug = CatalogSlug::resolve($data['name'], $data['slug'] ?? null);
        CatalogSlug::assertUnique('brands', $slug);

        return Brand::query()->create([
            'code' => (string) Str::ulid(),
            'name' => $data['name'],
            'slug' => $slug,
            'status' => $data['status'] ?? Brand::STATUS_ACTIVE,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }

    /**
     * @param  array{name?: string, slug?: string|null, status?: string|null}  $data
     */
    public function update(Brand $brand, array $data, int $adminId): Brand
    {
        $name = $data['name'] ?? $brand->name;
        $slug = $brand->slug;
        if (array_key_exists('slug', $data)) {
            $slug = CatalogSlug::forUpdate('brands', $brand->slug, $name, $data['slug'], $brand->id);
        }

        $brand->fill([
            'name' => $name,
            'slug' => $slug,
            'status' => $data['status'] ?? $brand->status,
            'updated_by' => $adminId,
        ])->save();

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw new CatalogException(
                ErrorCode::CATALOG_RESOURCE_IN_USE,
                'Brand still has products.',
                status: 422,
            );
        }

        $brand->delete();
    }
}
