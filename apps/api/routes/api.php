<?php

use App\Http\Controllers\Api\V1\Admin\MeController as AdminMeController;
use App\Http\Controllers\Api\V1\Customer\MeController as CustomerMeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:customer', 'account.active:customer'])
        ->prefix('customer')
        ->group(function () {
            Route::get('me', CustomerMeController::class);
        });

    Route::middleware([
        'auth:admin',
        'account.active:admin',
        'role:super_admin|admin|staff,admin',
    ])
        ->prefix('admin')
        ->group(function () {
            Route::get('me', AdminMeController::class);

            Route::middleware('permission:customers.view,admin')
                ->get('customers-check', function () {
                    return response()->json(['ok' => true]);
                });
        });
});
