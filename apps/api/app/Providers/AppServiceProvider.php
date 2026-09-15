<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\ShippingGateway;
use App\Contracts\SmsGateway;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Observers\CatalogSearchObserver;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\VnPayGateway;
use App\Services\Shipping\FakeGhnGateway;
use App\Services\Shipping\GhnGateway;
use App\Services\Sms\FakeSmsGateway;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\UnavailableSmsGateway;
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
        $this->app->bind(PaymentGateway::class, function ($app) {
            if ($app->runningUnitTests()) {
                return $app->make(FakePaymentGateway::class);
            }

            return $app->make(VnPayGateway::class);
        });

        $this->app->bind(ShippingGateway::class, function ($app) {
            if ($app->runningUnitTests() || $app['config']->get('commerce.shipping_driver') === 'fake') {
                return $app->make(FakeGhnGateway::class);
            }

            return $app->make(GhnGateway::class);
        });

        $this->app->singleton(SmsGateway::class, function ($app) {
            if ($app->runningUnitTests()) {
                return $app->make(FakeSmsGateway::class);
            }

            return match ($app['config']->get('commerce.sms_driver')) {
                'fake' => $app->make(FakeSmsGateway::class),
                'log' => $app->make(LogSmsGateway::class),
                default => $app->make(UnavailableSmsGateway::class),
            };
        });
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureScramble();
        $this->observeCatalogSearch();
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

        RateLimiter::for('catalog.suggest', function (Request $request) {
            return Limit::perMinute(60)->by((string) $request->ip());
        });

        RateLimiter::for('auth.customer.phone.otp', function (Request $request) {
            $id = $request->user('customer')?->id ?? 'guest';

            return Limit::perMinute(3)->by('customer:'.$id.'|'.$request->ip());
        });

        RateLimiter::for('auth.customer.phone.verify', function (Request $request) {
            $id = $request->user('customer')?->id ?? 'guest';

            return Limit::perMinute(5)->by('customer:'.$id.'|'.$request->ip());
        });
    }

    private function observeCatalogSearch(): void
    {
        $observer = $this->app->make(CatalogSearchObserver::class);
        ProductVariant::observe($observer);
        Brand::observe($observer);
        Category::observe($observer);
        AttributeOption::observe($observer);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower((string) $request->input('email')).'|'.$request->ip();
    }
}
