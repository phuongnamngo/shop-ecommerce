<?php

use App\Models\Warehouse;

it('reuses an existing default warehouse instead of inserting another', function () {
    $existing = Warehouse::factory()->default()->create(['name' => 'Main DC']);

    $resolved = Warehouse::ensureDefault();

    expect($resolved->id)->toBe($existing->id)
        ->and(Warehouse::query()->where('is_default', true)->count())->toBe(1);
});

it('creates Default Warehouse when no default exists', function () {
    $resolved = Warehouse::ensureDefault();

    expect($resolved->is_default)->toBeTrue()
        ->and($resolved->name)->toBe('Default Warehouse')
        ->and(Warehouse::query()->where('is_default', true)->count())->toBe(1);
});
