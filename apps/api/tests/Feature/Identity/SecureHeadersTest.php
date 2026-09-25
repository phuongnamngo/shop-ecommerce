<?php

it('sends nosniff, deny frame, and no-referrer on api responses', function () {
    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});
