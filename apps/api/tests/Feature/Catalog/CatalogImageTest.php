<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

it('uploads a jpeg original and thumbnail', function () {
    $response = $this->actingAs(catalogAdmin(), 'admin')
        ->post('/api/v1/admin/catalog/uploads/images', [
            'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.path', fn (string $path) => str_starts_with($path, 'catalog/'))
        ->assertJsonPath('data.thumbnail_path', fn (string $path) => str_contains($path, '_thumb'));

    Storage::disk('public')->assertExists($response->json('data.path'));
    Storage::disk('public')->assertExists($response->json('data.thumbnail_path'));
});

it('rejects attaching a path outside catalog/', function () {
    Storage::disk('public')->put('evil/x.jpg', 'not-an-image');
    $product = Product::factory()->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/images', [
            'path' => 'evil/x.jpg',
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED, 'field' => 'path']);
});

it('marks the first attached image as primary and can switch primary', function () {
    Storage::disk('public')->put('catalog/one.jpg', 'img');
    Storage::disk('public')->put('catalog/two.jpg', 'img');
    $product = Product::factory()->create();

    $first = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/images', [
            'path' => 'catalog/one.jpg',
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_primary', true)
        ->json('data.id');

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/images', [
            'path' => 'catalog/two.jpg',
            'is_primary' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_primary', true);

    expect(ProductImage::query()->find($first)?->is_primary)->toBeFalse();
});

it('attaches a variant image and exposes url on public detail', function () {
    Storage::disk('public')->put('catalog/pdp.jpg', 'img');
    $product = Product::factory()->published()->create(['slug' => 'imaged-tee']);
    $variant = $product->variants()->first();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/images', [
            'path' => 'catalog/pdp.jpg',
        ])
        ->assertCreated();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/variants/'.$variant->id.'/images', [
            'path' => 'catalog/pdp.jpg',
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_primary', true);

    $this->getJson('/api/v1/catalog/products/imaged-tee')
        ->assertOk()
        ->assertJsonPath('data.images.0.url', fn (string $url) => str_contains($url, 'catalog/pdp.jpg'))
        ->assertJsonPath('data.primary_image.url', fn (string $url) => str_contains($url, 'catalog/pdp.jpg'))
        ->assertJsonPath('data.variants.0.images.0.url', fn (string $url) => str_contains($url, 'catalog/pdp.jpg'));
});
