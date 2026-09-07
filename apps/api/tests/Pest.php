<?php

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function catalogAdmin(string $role = 'admin'): AdminUser
{
    $admin = AdminUser::factory()->create();
    $admin->assignRole($role);

    return $admin;
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
