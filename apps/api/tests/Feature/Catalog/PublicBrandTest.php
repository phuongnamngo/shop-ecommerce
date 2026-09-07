<?php

use App\Models\Brand;

it('lists only active brands without code', function () {
    Brand::factory()->create(['name' => 'Shown', 'slug' => 'shown', 'status' => Brand::STATUS_ACTIVE]);
    Brand::factory()->create(['name' => 'Hidden', 'slug' => 'hidden-draft', 'status' => Brand::STATUS_DRAFT]);

    $this->getJson('/api/v1/catalog/brands')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Shown')
        ->assertJsonMissing(['code']);
});
