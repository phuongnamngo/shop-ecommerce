<?php

use App\Support\CatalogNotFound;
use App\Support\CatalogPaginator;
use App\Support\ErrorCode;
use Illuminate\Http\Request;

it('returns CATALOG_NOT_FOUND envelope', function () {
    $response = CatalogNotFound::response();

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true))->toMatchArray([
            'data' => null,
            'errors' => [[
                'code' => ErrorCode::CATALOG_NOT_FOUND,
                'message' => 'Not found.',
                'field' => null,
            ]],
        ]);
});

it('clamps per_page between 1 and 100', function () {
    expect(CatalogPaginator::perPage(Request::create('/', 'GET', ['per_page' => 200])))->toBe(100)
        ->and(CatalogPaginator::perPage(Request::create('/', 'GET')))->toBe(20)
        ->and(CatalogPaginator::perPage(Request::create('/', 'GET', ['per_page' => 5])))->toBe(5);
});
