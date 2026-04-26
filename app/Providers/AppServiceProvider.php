<?php

namespace App\Providers;

use App\Mixins\ResponseFactoryMixin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResponseFactory::mixin(new ResponseFactoryMixin());

        RateLimiter::for('otp-start', function (Request $request) {
            return Limit::perMinutes(10, 3)
                ->by(($request->input('phone') ?? 'unknown') . '|' . $request->ip());
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinutes(10, 5)
                ->by(($request->input('phone') ?? 'unknown') . '|' . $request->ip());
        });
    }
}
