<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CmsMarkdown
{
    public static function html(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
