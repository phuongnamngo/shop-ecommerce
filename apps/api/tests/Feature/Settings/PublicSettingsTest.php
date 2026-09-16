<?php

use App\Models\Setting;
use App\Support\SettingsAllowlist;
use Database\Seeders\RolesAndPermissionsSeeder;

it('returns flattened public settings with Watch and VND fallbacks', function () {
    $this->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.site.name', 'Watch')
        ->assertJsonPath('data.currency.code', 'VND')
        ->assertJsonMissingPath('data.group')
        ->assertJsonMissingPath('data.key');

    expect($this->getJson('/api/v1/settings')->json('data'))->not->toHaveKey('value');
});

it('uses value.vi after an Eloquent update and hides foreign keys', function () {
    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::SITE_NAME],
        ['value' => ['vi' => 'Watch Shop'], 'group' => 'site'],
    )->update(['value' => ['vi' => 'Dong ho ABC']]);

    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::CURRENCY_DEFAULT],
        ['value' => ['code' => 'VND'], 'group' => 'currency'],
    );

    Setting::factory()->create(['key' => 'setting.secret', 'value' => ['x' => 1]]);

    $res = $this->getJson('/api/v1/settings')->assertOk();
    expect($res->json('data.site.name'))->toBe('Dong ho ABC')
        ->and($res->json('data.currency.code'))->toBe('VND')
        ->and(json_encode($res->json('data')))->not->toContain('setting.secret');
});

it('reflects an admin patch on the public payload', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::SITE_NAME],
        ['value' => ['vi' => 'Watch Shop'], 'group' => 'site'],
    );
    Setting::query()->firstOrCreate(
        ['key' => SettingsAllowlist::CURRENCY_DEFAULT],
        ['value' => ['code' => 'VND'], 'group' => 'currency'],
    );

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/settings/'.SettingsAllowlist::SITE_NAME, [
            'value' => ['vi' => 'Cua hang public'],
        ])
        ->assertOk();

    $this->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.site.name', 'Cua hang public');
});
