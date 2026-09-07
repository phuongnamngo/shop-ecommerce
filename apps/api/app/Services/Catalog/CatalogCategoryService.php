<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Support\CatalogException;
use App\Support\CatalogSlug;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $adminId): Category
    {
        $slug = CatalogSlug::resolve($data['name'], $data['slug'] ?? null);
        CatalogSlug::assertUnique('categories', $slug);

        return Category::query()->create([
            'code' => (string) Str::ulid(),
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'position' => $data['position'] ?? 0,
            'status' => $data['status'] ?? Category::STATUS_ACTIVE,
            'description' => $data['description'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data, int $adminId): Category
    {
        $name = $data['name'] ?? $category->name;
        $slug = $category->slug;
        if (array_key_exists('slug', $data)) {
            $slug = CatalogSlug::forUpdate('categories', $category->slug, $name, $data['slug'], $category->id);
        }

        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $category->parent_id;
        $this->assertAcyclic($parentId !== null ? (int) $parentId : null, $category->id);

        $category->fill([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'position' => $data['position'] ?? $category->position,
            'status' => $data['status'] ?? $category->status,
            'description' => array_key_exists('description', $data) ? $data['description'] : $category->description,
            'meta_title' => array_key_exists('meta_title', $data) ? $data['meta_title'] : $category->meta_title,
            'meta_description' => array_key_exists('meta_description', $data) ? $data['meta_description'] : $category->meta_description,
            'updated_by' => $adminId,
        ])->save();

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            throw new CatalogException(
                ErrorCode::CATALOG_RESOURCE_IN_USE,
                'Category still has children or products.',
                status: 422,
            );
        }

        $category->delete();
    }

    /**
     * @return list<int>
     */
    public function descendantIds(int $rootId): array
    {
        $rows = DB::select('
            WITH RECURSIVE tree AS (
                SELECT id FROM categories WHERE id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT c.id FROM categories c INNER JOIN tree t ON c.parent_id = t.id WHERE c.deleted_at IS NULL
            )
            SELECT id FROM tree
        ', [$rootId]);

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publicTree(): array
    {
        $all = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $visible = $all->filter(function (Category $category) use ($all): bool {
            $current = $category;
            while ($current->parent_id !== null) {
                $parent = $all->get($current->parent_id);
                if ($parent === null) {
                    return false;
                }
                $current = $parent;
            }

            return true;
        });

        $childrenMap = [];
        foreach ($visible as $category) {
            $childrenMap[$category->parent_id ?? 0][] = $category;
        }

        return $this->nestTree($childrenMap, null);
    }

    /**
     * @param  array<int, list<Category>>  $childrenMap
     * @return list<array<string, mixed>>
     */
    private function nestTree(array $childrenMap, ?int $parentId): array
    {
        $nodes = $childrenMap[$parentId ?? 0] ?? [];

        return array_map(function (Category $category) use ($childrenMap): array {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'position' => $category->position,
                'description' => $category->description,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
                'children' => $this->nestTree($childrenMap, $category->id),
            ];
        }, $nodes);
    }

    private function assertAcyclic(?int $parentId, int $selfId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $selfId || in_array($parentId, $this->descendantIds($selfId), true)) {
            throw new CatalogException(
                ErrorCode::CATALOG_CATEGORY_CYCLE,
                'Category parent would create a cycle.',
                'parent_id',
                422,
            );
        }
    }

    public function isPublicVisible(Category $category): bool
    {
        $current = $category;

        while ($current !== null) {
            if ($current->status !== Category::STATUS_ACTIVE) {
                return false;
            }

            if ($current->parent_id === null) {
                return true;
            }

            $current = $current->relationLoaded('parent')
                ? $current->parent
                : Category::query()->find($current->parent_id);
        }

        return false;
    }
}
