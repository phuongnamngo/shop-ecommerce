<?php

use App\Support\CmsMarkdown;
use Tests\TestCase;

uses(TestCase::class);

it('strips raw html and unsafe links from markdown', function () {
    $html = CmsMarkdown::html("Hello <script>alert(1)</script>\n\n[x](javascript:alert(1))");
    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('javascript:');
});
