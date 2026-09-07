<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('creates a category with auto slug and seo fields', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/categories', [
            'name' => 'Apparel',
            'description' => 'Clothes',
            'meta_title' => 'Apparel meta',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', Str::slug('Apparel'))
        ->assertJsonPath('data.description', 'Clothes')
        ->assertJsonPath('data.status', Category::STATUS_ACTIVE);
});

it('creates a category with an overridden slug', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/categories', [
            'name' => 'Apparel',
            'slug' => 'custom-apparel',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'custom-apparel');
});

it('rejects a parent cycle', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/categories/'.$parent->id, [
            'parent_id' => $child->id,
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_CATEGORY_CYCLE, 'field' => 'parent_id']);
});

it('forbids deleting a category that has children', function () {
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/categories/'.$parent->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_RESOURCE_IN_USE]);
});

it('forbids deleting a category that has products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/categories/'.$category->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_RESOURCE_IN_USE]);
});
