<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class CatalogImagePath
{
    public static function thumbnailPath(string $path): string
    {
        $info = pathinfo($path);
        $dir = ($info['dirname'] ?? '.') !== '.' ? $info['dirname'].'/' : '';
        $filename = $info['filename'] ?? $path;
        $extension = isset($info['extension']) && $info['extension'] !== '' ? '.'.$info['extension'] : '';

        return $dir.$filename.'_thumb'.$extension;
    }

    public static function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
