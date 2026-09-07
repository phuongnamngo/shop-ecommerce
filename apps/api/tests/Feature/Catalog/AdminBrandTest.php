<?php

use App\Models\Brand;
use App\Models\Product;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows staff to list brands', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/catalog/brands')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('forbids staff from creating a brand', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/catalog/brands', ['name' => 'Acme'])
        ->assertForbidden();
});

it('creates a brand with auto slug', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/brands', ['name' => 'Acme'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme')
        ->assertJsonPath('data.slug', Str::slug('Acme'))
        ->assertJsonPath('data.status', Brand::STATUS_ACTIVE)
        ->assertJsonStructure(['data' => ['id', 'code', 'name', 'slug', 'status', 'created_by', 'updated_by']]);
});

it('rejects duplicate slug', function () {
    Brand::factory()->create(['slug' => 'acme']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/brands', ['name' => 'Other', 'slug' => 'acme'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_SLUG_TAKEN, 'field' => 'slug']);
});

it('returns CATALOG_NOT_FOUND for missing brand', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/catalog/brands/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_NOT_FOUND]);
});

it('forbids deleting a brand that still has products', function () {
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/brands/'.$brand->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_RESOURCE_IN_USE]);
});

it('clamps admin brand per_page to 100', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/catalog/brands?per_page=200')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});
