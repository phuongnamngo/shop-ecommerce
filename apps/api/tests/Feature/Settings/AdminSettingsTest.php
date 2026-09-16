<?php

use App\Models\Setting;
use App\Support\ErrorCode;
use App\Support\SettingsAllowlist;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function seedAllowlistSettings(): void
{
    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::SITE_NAME],
        ['value' => ['vi' => 'Watch Shop'], 'group' => 'site'],
    );
    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::CURRENCY_DEFAULT],
        ['value' => ['code' => 'VND'], 'group' => 'currency'],
    );
}

it('allows staff to list settings and forbids writes', function () {
    seedAllowlistSettings();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/settings')
        ->assertOk();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => 'Nope'],
        ])
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_FORBIDDEN]);
});

it('lists only allowlisted keys in key ascending order', function () {
    seedAllowlistSettings();
    Setting::factory()->create(['key' => 'setting.foo', 'value' => ['x' => 1]]);

    $res = $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/settings')
        ->assertOk();

    $keys = collect($res->json('data'))->pluck('key')->all();
    expect($keys)->toBe([SettingsAllowlist::CURRENCY_DEFAULT, SettingsAllowlist::SITE_NAME])
        ->and($res->json('data.0'))->toHaveKeys(['key', 'group', 'value'])
        ->and($keys)->not->toContain('setting.foo');
});

it('patches site name and returns the envelope', function () {
    seedAllowlistSettings();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => 'Cua hang X'],
        ])
        ->assertOk()
        ->assertJsonPath('data.key', SettingsAllowlist::SITE_NAME)
        ->assertJsonPath('data.value.vi', 'Cua hang X');
});

it('stores USD on currency.default without changing checkout', function () {
    seedAllowlistSettings();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::CURRENCY_DEFAULT, [
            'value' => ['code' => 'USD'],
        ])
        ->assertOk()
        ->assertJsonPath('data.value.code', 'USD');
});

it('returns SETTINGS_NOT_FOUND for unknown or missing keys', function () {
    seedAllowlistSettings();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/foo', ['value' => ['vi' => 'X']])
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::SETTINGS_NOT_FOUND]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/cms.pages', ['value' => ['vi' => 'X']])
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::SETTINGS_NOT_FOUND]);

    Setting::query()->where('key', SettingsAllowlist::SITE_NAME)->delete();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => 'X'],
        ])
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::SETTINGS_NOT_FOUND]);
});

it('rejects invalid setting values', function () {
    seedAllowlistSettings();
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => ''],
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => str_repeat('a', 81)],
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => 'A', 'extra' => 1],
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::CURRENCY_DEFAULT, [
            'value' => ['code' => 'US'],
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::CURRENCY_DEFAULT, [
            'value' => ['code' => 'usd1'],
        ])
        ->assertUnprocessable();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::CURRENCY_DEFAULT, [
            'value' => ['code' => 'vnd'],
        ])
        ->assertOk()
        ->assertJsonPath('data.value.code', 'VND');
});

it('does not expose post or delete settings routes', function () {
    seedAllowlistSettings();
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/settings', ['key' => 'site.name', 'value' => ['vi' => 'X']])
        ->assertStatus(405);

    $this->actingAs($admin, 'admin')
        ->deleteJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME)
        ->assertStatus(405);
});
