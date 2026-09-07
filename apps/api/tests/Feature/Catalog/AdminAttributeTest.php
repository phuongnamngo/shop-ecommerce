<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('creates an attribute with nested options', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/attributes', [
            'name' => 'Color',
            'options' => [
                ['label' => 'Red', 'position' => 1],
                ['label' => 'Blue', 'position' => 2],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', Str::slug('Color'))
        ->assertJsonCount(2, 'data.options')
        ->assertJsonMissingPath('data.status')
        ->assertJsonMissingPath('data.created_by')
        ->assertJsonPath('data.options.0.label', 'Red');
});

it('does not replace options when patching an attribute', function () {
    $attribute = Attribute::factory()->create(['name' => 'Size']);
    AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'label' => 'M']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/attributes/'.$attribute->id, [
            'name' => 'Garment Size',
            'options' => [['label' => 'Should not apply']],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Garment Size')
        ->assertJsonPath('data.slug', $attribute->slug)
        ->assertJsonCount(1, 'data.options')
        ->assertJsonPath('data.options.0.label', 'M');
});

it('rejects a duplicate attribute slug', function () {
    Attribute::factory()->create(['slug' => 'color']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/attributes', ['name' => 'Hue', 'slug' => 'color'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_SLUG_TAKEN, 'field' => 'slug']);
});

it('forbids deleting an attribute attached to a variant', function () {
    $attribute = Attribute::factory()->create();
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);
    $product = Product::factory()->create();
    $product->variants()->first()->attributeOptions()->attach($option->id, ['attribute_id' => $attribute->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/attributes/'.$attribute->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_RESOURCE_IN_USE]);
});

it('forbids staff from creating an attribute', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/catalog/attributes', ['name' => 'Color'])
        ->assertForbidden();
});
