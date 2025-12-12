<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = config('bindings');

        foreach($bindings as $category => $interfaces) {
            foreach($interfaces as $interface => $implementation) {
                $this->app->bind($interface, $implementation);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    
        // Onboarding submission rate limit
        RateLimiter::for('onboarding', function (Request $request) {
            return Limit::perHour(3)->by($request->session()->getId());
        });
    
        // Status polling rate limit
        RateLimiter::for('onboarding-status', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
