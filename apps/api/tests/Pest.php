<?php

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/Support/flushTestingSearch.php';

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->beforeEach(function () {
    static $configured = false;
    if ($configured) {
        return;
    }
    test()->artisan('catalog:search-configure')->assertSuccessful();
    $configured = true;
})->in('Feature');

pest()->beforeEach(function () {
    waitForTestingSearchIdle();
    flushTestingSearchIndexes();
    waitForTestingSearchIdle();
    test()->artisan('catalog:search-configure')->assertSuccessful();
})->in('Feature/Catalog');

pest()->afterEach(function () {
    waitForTestingSearchIdle();
})->in('Feature/Catalog');

function catalogAdmin(string $role = 'admin'): AdminUser
{
    $admin = AdminUser::factory()->create();
    $admin->assignRole($role);

    return $admin;
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
