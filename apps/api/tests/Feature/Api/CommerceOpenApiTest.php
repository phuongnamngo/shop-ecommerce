<?php

use Illuminate\Support\Facades\Artisan;

function commerceOpenApiDocument(): array
{
    $path = storage_path('framework/testing-commerce-openapi.json');
    Artisan::call('scramble:export', ['--path' => $path]);

    return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

function resolveCommerceSchema(array $document, array $schema): array
{
    if (isset($schema['$ref'])) {
        $resolved = $document;
        foreach (explode('/', ltrim($schema['$ref'], '#/')) as $segment) {
            $resolved = $resolved[$segment];
        }

        return resolveCommerceSchema($document, $resolved);
    }

    return $schema;
}

function commerceSuccessDataSchema(array $document, string $method, string $path, string $status = '200'): array
{
    $schema = commerceSuccessEnvelopeSchema($document, $method, $path, $status);

    return resolveCommerceSchema($document, $schema['properties']['data']);
}

function commerceSuccessEnvelopeSchema(array $document, string $method, string $path, string $status = '200'): array
{
    $schema = $document['paths'][$path][strtolower($method)]['responses'][$status]['content']['application/json']['schema'];

    return resolveCommerceSchema($document, $schema);
}

function commerceQueryParameters(array $document, string $path): array
{
    return collect($document['paths'][$path]['get']['parameters'] ?? [])
        ->where('in', 'query')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();
}

function commerceHeaderParameters(array $document, string $method, string $path): array
{
    return collect($document['paths'][$path][strtolower($method)]['parameters'] ?? [])
        ->where('in', 'header')
        ->keyBy('name')
        ->all();
}

it('discovers every inventory cart checkout and order operation', function () {
    $document = commerceOpenApiDocument();
    $expected = [
        '/api/v1/admin/inventory/warehouses' => ['get'],
        '/api/v1/admin/inventory/stock-items' => ['get'],
        '/api/v1/admin/inventory/movements' => ['post'],
        '/api/v1/cart' => ['get', 'post'],
        '/api/v1/cart/items' => ['post'],
        '/api/v1/cart/items/{itemId}' => ['delete', 'patch'],
        '/api/v1/customer/cart' => ['get'],
        '/api/v1/customer/cart/items' => ['post'],
        '/api/v1/customer/cart/items/{itemId}' => ['delete', 'patch'],
        '/api/v1/customer/cart/merge' => ['post'],
        '/api/v1/checkout' => ['post'],
        '/api/v1/customer/orders' => ['get'],
        '/api/v1/customer/orders/{id}' => ['get'],
        '/api/v1/admin/orders' => ['get'],
        '/api/v1/admin/orders/{id}' => ['get'],
        '/api/v1/admin/orders/{id}/status' => ['patch'],
    ];

    foreach ($expected as $path => $methods) {
        expect($document['paths'])->toHaveKey($path);
        foreach ($methods as $method) {
            expect($document['paths'][$path])->toHaveKey($method);
        }
    }
});

it('documents commerce response resource shapes and cardinality', function () {
    $document = commerceOpenApiDocument();

    $cart = commerceSuccessDataSchema($document, 'post', '/api/v1/cart', '201');
    expect($cart['type'])->toBe('object')
        ->and(array_keys($cart['properties']))->toContain('id', 'currency', 'items', 'subtotal');
    $cartItem = resolveCommerceSchema($document, $cart['properties']['items']['items']);
    expect(array_keys($cartItem['properties']))->toContain('id', 'product_variant_id', 'qty', 'unit_price', 'line_total');

    $movement = commerceSuccessDataSchema($document, 'post', '/api/v1/admin/inventory/movements', '201');
    expect($movement['type'])->toBe('object')
        ->and(array_keys($movement['properties']))->toContain('id', 'warehouse_id', 'product_variant_id', 'qty_on_hand', 'qty_reserved', 'available_qty')
        ->and($movement['properties']['available_qty']['type'])->toBe('integer');

    $order = commerceSuccessDataSchema($document, 'get', '/api/v1/customer/orders/{id}');
    expect($order['type'])->toBe('object')
        ->and(array_keys($order['properties']))->toContain('id', 'number', 'status', 'items');

    foreach ([
        ['/api/v1/admin/inventory/warehouses', 'id'],
        ['/api/v1/admin/inventory/stock-items', 'available_qty'],
        ['/api/v1/customer/orders', 'number'],
        ['/api/v1/admin/orders', 'customer_id'],
    ] as [$path, $field]) {
        $list = commerceSuccessDataSchema($document, 'get', $path);
        $item = resolveCommerceSchema($document, $list['items']);

        expect($list['type'])->toBe('array')
            ->and(array_keys($item['properties']))->toContain($field);
    }
});

it('documents commerce pagination and filter query parameters', function () {
    $document = commerceOpenApiDocument();

    expect(commerceQueryParameters($document, '/api/v1/admin/inventory/warehouses'))
        ->toBe(['page', 'per_page'])
        ->and(commerceQueryParameters($document, '/api/v1/admin/inventory/stock-items'))
        ->toBe(['page', 'per_page', 'product_variant_id', 'warehouse_id'])
        ->and(commerceQueryParameters($document, '/api/v1/customer/orders'))
        ->toBe(['page', 'per_page'])
        ->and(commerceQueryParameters($document, '/api/v1/admin/orders'))
        ->toBe(['customer_id', 'page', 'per_page', 'status']);
});

it('documents the guest cart token header contract', function () {
    $document = commerceOpenApiDocument();

    foreach ([
        ['get', '/api/v1/cart'],
        ['post', '/api/v1/cart/items'],
        ['patch', '/api/v1/cart/items/{itemId}'],
        ['delete', '/api/v1/cart/items/{itemId}'],
    ] as [$method, $path]) {
        $headers = commerceHeaderParameters($document, $method, $path);
        expect($headers)->toHaveKey('X-Cart-Token')
            ->and($headers['X-Cart-Token']['required'])->toBeTrue();
    }

    $checkoutHeaders = commerceHeaderParameters($document, 'post', '/api/v1/checkout');
    expect($checkoutHeaders)->toHaveKey('X-Cart-Token')
        ->and($checkoutHeaders['X-Cart-Token']['required'] ?? false)->toBeFalse();
});

it('documents order snapshots and conditional nested detail fields', function () {
    $document = commerceOpenApiDocument();
    $customerOrder = commerceSuccessDataSchema($document, 'get', '/api/v1/customer/orders/{id}');
    $addressTypes = (array) $customerOrder['properties']['shipping_address']['type'];
    expect($addressTypes)->toContain('object', 'null')
        ->and(array_keys($customerOrder['properties']['shipping_address']['properties']))
        ->toContain('recipient_name', 'phone', 'province_code', 'district_code', 'ward_code', 'address_line', 'postal_code');

    $adminList = commerceSuccessDataSchema($document, 'get', '/api/v1/admin/orders');
    $adminListItem = resolveCommerceSchema($document, $adminList['items']);
    expect($adminListItem['required'] ?? [])->not->toContain('items', 'status_history');

    $adminDetail = commerceSuccessDataSchema($document, 'get', '/api/v1/admin/orders/{id}');
    $item = resolveCommerceSchema($document, $adminDetail['properties']['items']['items']);
    $history = resolveCommerceSchema($document, $adminDetail['properties']['status_history']['items']);
    expect($adminDetail['properties']['items']['type'])->toBe('array')
        ->and(array_keys($item['properties']))->toContain('id', 'sku', 'qty', 'unit_price', 'line_total')
        ->and($adminDetail['properties']['status_history']['type'])->toBe('array')
        ->and(array_keys($history['properties']))->toContain('id', 'from_status', 'to_status', 'note');
});

it('documents empty success metadata as an object', function () {
    $document = commerceOpenApiDocument();

    foreach ([
        ['delete', '/api/v1/cart/items/{itemId}', '200'],
        ['delete', '/api/v1/customer/cart/items/{itemId}', '200'],
        ['post', '/api/v1/checkout', '201'],
        ['patch', '/api/v1/cart/items/{itemId}', '200'],
    ] as [$method, $path, $status]) {
        $envelope = commerceSuccessEnvelopeSchema($document, $method, $path, $status);
        expect(resolveCommerceSchema($document, $envelope['properties']['meta'])['type'])->toBe('object');
    }
});
