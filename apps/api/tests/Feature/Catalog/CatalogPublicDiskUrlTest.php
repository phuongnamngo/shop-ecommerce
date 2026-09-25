<?php

use App\Support\CatalogImagePath;
use Illuminate\Support\Facades\Storage;

it('builds the public disk url from ASSET_URL when set', function () {
    $source = file_get_contents(config_path('filesystems.php'));
    expect($source)->toContain("env('ASSET_URL', env('APP_URL', 'http://localhost'))");

    config(['filesystems.disks.public.url' => 'https://cdn.example/storage']);
    Storage::forgetDisk('public');
    expect(CatalogImagePath::url('catalog/a.webp'))->toBe('https://cdn.example/storage/catalog/a.webp');

    config(['filesystems.disks.public.url' => rtrim((string) config('app.url'), '/').'/storage']);
    Storage::forgetDisk('public');
    expect(CatalogImagePath::url('catalog/a.webp'))->toStartWith(rtrim((string) config('app.url'), '/').'/storage/');
});
