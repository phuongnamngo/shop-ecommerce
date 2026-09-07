<?php

use App\Models\Category;

it('returns a public tree hiding inactive branches', function () {
    $root = Category::factory()->create(['name' => 'Root', 'slug' => 'root', 'position' => 1, 'status' => Category::STATUS_ACTIVE]);
    Category::factory()->create([
        'name' => 'Child',
        'slug' => 'child',
        'parent_id' => $root->id,
        'position' => 1,
        'status' => Category::STATUS_ACTIVE,
        'description' => 'Child desc',
    ]);
    $inactive = Category::factory()->create(['name' => 'Hidden', 'slug' => 'hidden', 'status' => Category::STATUS_INACTIVE]);
    Category::factory()->create([
        'name' => 'Orphan',
        'slug' => 'orphan',
        'parent_id' => $inactive->id,
        'status' => Category::STATUS_ACTIVE,
    ]);

    $this->getJson('/api/v1/catalog/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'root')
        ->assertJsonPath('data.0.children.0.slug', 'child')
        ->assertJsonPath('data.0.children.0.description', 'Child desc');
});
