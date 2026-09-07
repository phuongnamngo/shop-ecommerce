<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        then: function (): void {
            if (! app()->environment(['local', 'testing'])) {
                return;
            }

            Route::prefix('api/__test')->group(function (): void {
                Route::get('envelope-ok', fn () => ApiResponse::success(['ok' => true]));
                Route::post('envelope-validate', function () {
                    request()->validate(['email' => 'required|email']);
                });
                Route::get('envelope-auth', fn () => ApiResponse::success(['ok' => true]))
                    ->middleware('auth:customer');
                Route::post('envelope-throttle', fn () => ApiResponse::success(['ok' => true]))
                    ->middleware('throttle:2,1');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            return ApiExceptionRenderer::render($e, $request);
        });
    })->create();
