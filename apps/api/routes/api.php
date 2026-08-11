<?php

use App\Http\Controllers\Api\V1\Admin\Auth\ForgotPasswordController as AdminForgotPasswordController;
use App\Http\Controllers\Api\V1\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Api\V1\Admin\Auth\LogoutController as AdminLogoutController;
use App\Http\Controllers\Api\V1\Admin\Auth\ResetPasswordController as AdminResetPasswordController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\MeController as AdminMeController;
use App\Http\Controllers\Api\V1\Catalog\ProductController as PublicProductController;
use App\Http\Controllers\Api\V1\Customer\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Api\V1\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Api\V1\Customer\Auth\LogoutController as CustomerLogoutController;
use App\Http\Controllers\Api\V1\Customer\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Api\V1\Customer\Auth\ResetPasswordController as CustomerResetPasswordController;
use App\Http\Controllers\Api\V1\Customer\MeController as CustomerMeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('catalog/products', [PublicProductController::class, 'index']);

    Route::prefix('customer/auth')->group(function () {
        Route::post('register', CustomerRegisterController::class);
        Route::post('login', CustomerLoginController::class)
            ->middleware('throttle:auth.customer.login');
        Route::post('forgot-password', CustomerForgotPasswordController::class)
            ->middleware('throttle:auth.customer.forgot');
        Route::post('reset-password', CustomerResetPasswordController::class);
        Route::post('logout', CustomerLogoutController::class)
            ->middleware(['auth:customer']);
    });

    Route::middleware(['auth:customer', 'account.active:customer'])
        ->prefix('customer')
        ->group(function () {
            Route::get('me', CustomerMeController::class);
        });

    Route::prefix('admin/auth')->group(function () {
        Route::post('login', AdminLoginController::class)
            ->middleware('throttle:auth.admin.login');
        Route::post('forgot-password', AdminForgotPasswordController::class)
            ->middleware('throttle:auth.admin.forgot');
        Route::post('reset-password', AdminResetPasswordController::class);
        Route::post('logout', AdminLogoutController::class)
            ->middleware(['auth:admin']);
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

            Route::middleware('permission:catalog.products.view,admin')
                ->get('catalog/products', [AdminProductController::class, 'index']);
        });
});
