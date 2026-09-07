<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureScramble();
    }

    private function configureScramble(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->components->addSecurityScheme(
                    'csrfHeader',
                    SecurityScheme::apiKey('header', 'X-XSRF-TOKEN')
                        ->setDescription('CSRF token from the XSRF-TOKEN cookie (Sanctum SPA).'),
                );
            });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth.customer.login', function (Request $request) {
            return Limit::perMinute(5)->by($this->throttleKey($request));
        });

        RateLimiter::for('auth.admin.login', function (Request $request) {
            return Limit::perMinute(5)->by($this->throttleKey($request));
        });

        RateLimiter::for('auth.customer.forgot', function (Request $request) {
            return Limit::perMinute(3)->by($this->throttleKey($request));
        });

        RateLimiter::for('auth.admin.forgot', function (Request $request) {
            return Limit::perMinute(3)->by($this->throttleKey($request));
        });
    }

    private function throttleKey(Request $request): string
    {
        return strtolower((string) $request->input('email')).'|'.$request->ip();
    }
}
