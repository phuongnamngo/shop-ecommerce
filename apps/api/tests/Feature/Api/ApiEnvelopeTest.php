<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Support\ErrorCode;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('returns success envelope without errors key', function () {
    $this->getJson('/api/__test/envelope-ok')
        ->assertOk()
        ->assertJsonPath('data.ok', true)
        ->assertJsonPath('meta', [])
        ->assertJsonMissing(['errors']);
});

it('returns validation errors in envelope', function () {
    $this->postJson('/api/__test/envelope-validate', [])
        ->assertUnprocessable()
        ->assertJsonPath('data', null)
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED, 'field' => 'email']);
});

it('returns AUTH_UNAUTHENTICATED for guest on auth route', function () {
    $this->getJson('/api/__test/envelope-auth')
        ->assertUnauthorized()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_UNAUTHENTICATED]);
});

it('returns AUTH_THROTTLED when rate limited', function () {
    $this->postJson('/api/__test/envelope-throttle')->assertOk();
    $this->postJson('/api/__test/envelope-throttle')->assertOk();
    $this->postJson('/api/__test/envelope-throttle')
        ->assertStatus(429)
        ->assertJsonFragment(['code' => ErrorCode::AUTH_THROTTLED]);
});

it('maps CSRF HttpException 419 to CSRF_TOKEN_MISMATCH envelope', function () {
    // Laravel skips CSRF middleware when runningUnitTests(); cover renderer mapping instead.
    $request = Request::create('/api/v1/customer/auth/login', 'POST');
    $response = ApiExceptionRenderer::render(
        new HttpException(419, 'CSRF token mismatch.'),
        $request,
    );

    expect($response)->not->toBeNull()
        ->and($response->getStatusCode())->toBe(419)
        ->and($response->getData(true)['errors'][0]['code'])->toBe(ErrorCode::CSRF_TOKEN_MISMATCH);
});

it('defines stable error codes for commerce domains', function () {
    expect([
        ErrorCode::INVENTORY_NOT_FOUND,
        ErrorCode::INVENTORY_INSUFFICIENT_STOCK,
        ErrorCode::CART_NOT_FOUND,
        ErrorCode::CART_INVALID_TOKEN,
        ErrorCode::CHECKOUT_INVALID_CART,
        ErrorCode::ORDER_INVALID_TRANSITION,
        ErrorCode::COUPON_INVALID,
    ])->toBe([
        'INVENTORY_NOT_FOUND',
        'INVENTORY_INSUFFICIENT_STOCK',
        'CART_NOT_FOUND',
        'CART_INVALID_TOKEN',
        'CHECKOUT_INVALID_CART',
        'ORDER_INVALID_TRANSITION',
        'COUPON_INVALID',
    ]);
});
