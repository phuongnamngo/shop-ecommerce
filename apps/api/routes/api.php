<?php

use App\Http\Controllers\Api\V1\Admin\Auth\ForgotPasswordController as AdminForgotPasswordController;
use App\Http\Controllers\Api\V1\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Api\V1\Admin\Auth\LogoutController as AdminLogoutController;
use App\Http\Controllers\Api\V1\Admin\Auth\ResetPasswordController as AdminResetPasswordController;
use App\Http\Controllers\Api\V1\Admin\Catalog\AttributeController as AdminAttributeController;
use App\Http\Controllers\Api\V1\Admin\Catalog\AttributeOptionController as AdminAttributeOptionController;
use App\Http\Controllers\Api\V1\Admin\Catalog\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\Catalog\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ImageUploadController as AdminImageUploadController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\V1\Admin\Catalog\ProductVariantImageController as AdminProductVariantImageController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockItemController as AdminStockItemController;
use App\Http\Controllers\Api\V1\Admin\Inventory\StockMovementController as AdminStockMovementController;
use App\Http\Controllers\Api\V1\Admin\Inventory\WarehouseController as AdminWarehouseController;
use App\Http\Controllers\Api\V1\Admin\MeController as AdminMeController;
use App\Http\Controllers\Api\V1\Admin\Customer\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\Dashboard\MetricsController as AdminDashboardMetricsController;
use App\Http\Controllers\Api\V1\Admin\Order\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\Order\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Catalog\BrandController as PublicBrandController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController as PublicProductController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Geo\GeoController;
use App\Http\Controllers\Api\V1\Order\GuestOrderLookupController;
use App\Http\Controllers\Api\V1\Shipping\ShippingMethodController as PublicShippingMethodController;
use App\Http\Controllers\Api\V1\Customer\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Api\V1\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Api\V1\Customer\Auth\LogoutController as CustomerLogoutController;
use App\Http\Controllers\Api\V1\Customer\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Api\V1\Customer\Auth\ResetPasswordController as CustomerResetPasswordController;
use App\Http\Controllers\Api\V1\Customer\AddressController as CustomerAddressController;
use App\Http\Controllers\Api\V1\Customer\MeController as CustomerMeController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\Payment\VnPayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('cart', [CartController::class, 'createGuest']);
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'storeItem']);
    Route::patch('cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{itemId}', [CartController::class, 'destroyItem']);
    Route::post('checkout', [CheckoutController::class, 'store']);
    Route::get('payments/vnpay/ipn', [VnPayController::class, 'ipn']);
    Route::get('payments/vnpay/return', [VnPayController::class, 'returnUrl']);
    Route::get('catalog/products', [PublicProductController::class, 'index']);
    Route::get('catalog/products/{slug}', [PublicProductController::class, 'show']);
    Route::get('catalog/brands', [PublicBrandController::class, 'index']);
    Route::get('catalog/categories', [PublicCategoryController::class, 'index']);
    Route::get('shipping/methods', [PublicShippingMethodController::class, 'index']);
    Route::get('geo/provinces', [GeoController::class, 'provinces']);
    Route::get('geo/provinces/{code}/districts', [GeoController::class, 'districts']);
    Route::get('geo/districts/{code}/wards', [GeoController::class, 'wards']);
    Route::get('orders/lookup', [GuestOrderLookupController::class, 'show'])->middleware('throttle:60,1');

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
            Route::patch('me', [CustomerMeController::class, 'update']);
            Route::get('addresses', [CustomerAddressController::class, 'index']);
            Route::post('addresses', [CustomerAddressController::class, 'store']);
            Route::patch('addresses/{id}', [CustomerAddressController::class, 'update']);
            Route::delete('addresses/{id}', [CustomerAddressController::class, 'destroy']);
            Route::get('cart', [CartController::class, 'customerShow']);
            Route::post('cart/items', [CartController::class, 'customerStoreItem']);
            Route::patch('cart/items/{itemId}', [CartController::class, 'customerUpdateItem']);
            Route::delete('cart/items/{itemId}', [CartController::class, 'customerDestroyItem']);
            Route::post('cart/merge', [CartController::class, 'merge']);
            Route::get('orders', [CustomerOrderController::class, 'index']);
            Route::get('orders/{id}', [CustomerOrderController::class, 'show']);
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

            Route::middleware('permission:inventory.view,admin')->prefix('inventory')->group(function () {
                Route::get('warehouses', [AdminWarehouseController::class, 'index']);
                Route::get('stock-items', [AdminStockItemController::class, 'index']);
            });
            Route::post('inventory/movements', [AdminStockMovementController::class, 'store'])->middleware('permission:inventory.manage,admin');
            Route::middleware('permission:orders.view,admin')->group(function () {
                Route::get('dashboard/metrics', AdminDashboardMetricsController::class);
                Route::get('orders', [AdminOrderController::class, 'index']);
                Route::get('orders/{id}', [AdminOrderController::class, 'show']);
            });
            Route::patch('orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.manage,admin');
            Route::post('orders/{id}/shipments', [AdminShipmentController::class, 'store'])->middleware('permission:orders.manage,admin');

            Route::middleware('permission:customers.view,admin')->group(function () {
                Route::get('customers', [AdminCustomerController::class, 'index']);
                Route::get('customers/{id}', [AdminCustomerController::class, 'show']);
            });

            Route::middleware('permission:catalog.products.view,admin')->group(function () {
                Route::get('catalog/products', [AdminProductController::class, 'index']);
                Route::get('catalog/products/{id}', [AdminProductController::class, 'show']);
            });

            Route::middleware('permission:catalog.products.manage,admin')->group(function () {
                Route::post('catalog/uploads/images', [AdminImageUploadController::class, 'store']);
                Route::post('catalog/products', [AdminProductController::class, 'store']);
                Route::patch('catalog/products/{id}', [AdminProductController::class, 'update']);
                Route::delete('catalog/products/{id}', [AdminProductController::class, 'destroy']);
                Route::post('catalog/products/{id}/variants', [AdminProductVariantController::class, 'store']);
                Route::patch('catalog/products/{id}/variants/{variantId}', [AdminProductVariantController::class, 'update']);
                Route::delete('catalog/products/{id}/variants/{variantId}', [AdminProductVariantController::class, 'destroy']);
                Route::post('catalog/products/{id}/images', [AdminProductImageController::class, 'store']);
                Route::patch('catalog/products/{id}/images/{imageId}', [AdminProductImageController::class, 'update']);
                Route::delete('catalog/products/{id}/images/{imageId}', [AdminProductImageController::class, 'destroy']);
                Route::post('catalog/products/{id}/variants/{variantId}/images', [AdminProductVariantImageController::class, 'store']);
                Route::patch('catalog/products/{id}/variants/{variantId}/images/{imageId}', [AdminProductVariantImageController::class, 'update']);
                Route::delete('catalog/products/{id}/variants/{variantId}/images/{imageId}', [AdminProductVariantImageController::class, 'destroy']);
            });

            Route::middleware('permission:catalog.brands.view,admin')->group(function () {
                Route::get('catalog/brands', [AdminBrandController::class, 'index']);
                Route::get('catalog/brands/{id}', [AdminBrandController::class, 'show']);
            });

            Route::middleware('permission:catalog.brands.manage,admin')->group(function () {
                Route::post('catalog/brands', [AdminBrandController::class, 'store']);
                Route::patch('catalog/brands/{id}', [AdminBrandController::class, 'update']);
                Route::delete('catalog/brands/{id}', [AdminBrandController::class, 'destroy']);
            });

            Route::middleware('permission:catalog.categories.view,admin')->group(function () {
                Route::get('catalog/categories', [AdminCategoryController::class, 'index']);
                Route::get('catalog/categories/{id}', [AdminCategoryController::class, 'show']);
            });

            Route::middleware('permission:catalog.categories.manage,admin')->group(function () {
                Route::post('catalog/categories', [AdminCategoryController::class, 'store']);
                Route::patch('catalog/categories/{id}', [AdminCategoryController::class, 'update']);
                Route::delete('catalog/categories/{id}', [AdminCategoryController::class, 'destroy']);
            });

            Route::middleware('permission:catalog.attributes.view,admin')->group(function () {
                Route::get('catalog/attributes', [AdminAttributeController::class, 'index']);
                Route::get('catalog/attributes/{id}', [AdminAttributeController::class, 'show']);
            });

            Route::middleware('permission:catalog.attributes.manage,admin')->group(function () {
                Route::post('catalog/attributes', [AdminAttributeController::class, 'store']);
                Route::patch('catalog/attributes/{id}', [AdminAttributeController::class, 'update']);
                Route::delete('catalog/attributes/{id}', [AdminAttributeController::class, 'destroy']);
                Route::post('catalog/attributes/{id}/options', [AdminAttributeOptionController::class, 'store']);
                Route::patch('catalog/attributes/{id}/options/{optionId}', [AdminAttributeOptionController::class, 'update']);
                Route::delete('catalog/attributes/{id}/options/{optionId}', [AdminAttributeOptionController::class, 'destroy']);
            });
        });
});
